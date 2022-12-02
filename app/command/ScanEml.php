<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\model\Mails;
use app\model\Scanlog;
use app\model\Desc;
use app\model\CommonUtil;

class ScanEml extends Command {
	protected $cmd = "/opt/bin/scoreMail.sh '##path'";

	// 小于minScore是正常，大于等于0.7为垃圾
	protected $minScore = 0.7; 
	protected $scanType = array(
		0	=>	'正常邮件',
		1	=>	'可疑邮件',
	);
	protected $scanDir = array(
		0	=>	'normal',
		1	=>	'bad',
		2	=>	'virus',
	);
	protected $errorDir = array(
		0	=> 	'error_normal', // 期望为正常的
		1	=>	'error_bad',  // 期望为垃圾的
		2	=>	'error_virus' // 期望为垃圾的
	);

	protected function configure() {
		// 指令配置
		$this->setName('Scan eml')
			 ->addArgument('source', Argument::OPTIONAL, "扫描类型 todo未扫描 mId指定id重新扫描 path单纯扫描一封邮件，不操作db改状态")
			 ->addOption('type', null, Option::VALUE_REQUIRED, '按邮件类型重新扫描所有邮件 todo待办 normal正常 bad可疑 virus病毒') //TODO
			 ->setDescription('MailData Scan Eml');
	}

	protected function execute(Input $input, Output $output) {
		exec("ps -ef |grep 'think scan todo'|grep -v grep", $return_array, $status);
		$running_count = count($return_array);
		if ($running_count > 1) {
			exit($output->writeln("Script is running"));
		}
		// 指令输出
		if ($input->getArgument('source')) {
			$source = trim($input->getArgument('source'));
			if ($source == 'todo') {
				// 扫描所有待审核邮件
				$infos = Mails::getMailsByScanStatus(0);
				foreach ($infos as $info) {
					$output->writeln("Running mId:" . $info['mId']);
					$this->doScan($info);
				}
			} else if (is_numeric($source)) {
				// 传入mId，扫描指定邮件 大概率用于重新扫描
				$info = Mails::find($source)->toArray();
				$output->writeln("Running mId:" . $info['mId']);
				$this->doScan($info);
			} else if (is_file($source)) {
				// 单封指定路径扫描
				exit($output->writeln("TODO"));
			} else {
				exit($output->writeln("Invalid Eml Path"));
			}
		} else if ($input->hasOption('type')) {
			// 按类型，全部重扫 TODO
			$flushType = $input->getOption('type');

			$validInput = ['todo', 'normal', 'bad', 'virus'];
			if (!in_array($flushType, $validInput)) {
				exit($output->writeln("Invalid type, use --help"));
			}
			// 生成控制器对象
			$queueController = new \app\controller\Queue();

			$total = Mails::getAllCount($flushType);
			$output->writeln("Found " . $total . " Mails");

			// 暂不做分页
			$infos = Mails::getAllInfos(1, $total, $flushType);
			foreach ($infos as $info) {
				$output->writeln("Running mId:" . $info['mId']);

				// 调用重新扫描方法
				$newPath = $queueController->retry($info['mId']);
				$info['uploadDir'] = $newPath;

				$this->doScan($info, '1');
			}
		} else {
			exit($output->writeln("Invalid Eml Path"));
		}

		exec("chmod 777 -R /var/www/md_scan/runtime/log");
		$output->writeln("All Done");
	}

	// 扫描邮件方法， 传入邮件详情
	protected function doScan($info, $saveLastResult=0) {
		$rootDir = config('filesystem')['disks']['maildata']['root'];
		Mails::updateScanStatusById($info['mId'], 1);
		Scanlog::addScanlog($info['mId'], '【system】准备开始扫描');
		$emlRealPath = $rootDir . '/' . $info['uploadDir'];
		$newDbPath = $info['uploadDir'];
		if (is_file($emlRealPath)) {
			// 是否记录上次扫描预期
			if ($saveLastResult == 1) {
				$lastDecisionStatus = $info['decisionStatus'];
			} else {
				$lastDecisionStatus = '';
			}
			// 预期为病毒，暂时直接扔进病毒库
			if ($info['expectedStatus'] == 2) {
				$actualStatus = 2; // 扫描结果病毒
				$decisionStatus = 1; // 符合预期
				$newDbPath = $this->scanDir[2] . '/' .date('Ymd') . '/' . basename($emlRealPath);
				CommonUtil::moveToNewDir($rootDir, $emlRealPath, $newDbPath);
				$finalLog = "【system】病毒邮件暂不扫描，文件已移动至:" . $rootDir . '/' . $newDbPath;
				Mails::find($info['mId'])->save([
					'actualStatus'		=>	$actualStatus,
					'decisionStatus'	=>	$decisionStatus,
					'lastDecisionStatus'=>	$lastDecisionStatus,
					'scanStatus'		=>	2,
					'uploadDir'			=>	$newDbPath
				]);
				Scanlog::addScanlog($info['mId'], $finalLog);
				return;
			}

			// 扫描是否为垃圾邮件
			$cmd = str_replace('##path', $emlRealPath, $this->cmd);
			exec($cmd, $cmdReturn, $cmdStatus);
			if (isset($cmdReturn[0])) {
				$parseCmdReturn = explode(' ', end($cmdReturn));
				$oldScore = $score = (float)$parseCmdReturn[0];
				if ($score <= 1) {
					if ($score < $this->minScore) {
						$score = 0;
					} else {
						$score = 1;
					}

					$finalLog = "【system】扫描完成，结果为：" . $this->scanType[$score] . "，分数：" . $oldScore;
					$actualStatus = $score;

					$decisionStatus = 0; 
					if ($info['expectedStatus'] == $score) {
						// 移动至新文件夹
						$newDbPath = $this->scanDir[$score] . '/' .date('Ymd') . '/' . basename($emlRealPath);

						// 与预期相符
						$decisionStatus = 1;
						$finalLog .= "，与预期相符，文件已移动至:" . $rootDir . '/' . $newDbPath;
					} else {
						$newDbPath = $this->errorDir[$info['expectedStatus']] . '/' .date('Ymd') . '/' . basename($emlRealPath);
						

						$finalLog .= "，与预期不符。";
					}
					$errMsg = CommonUtil::moveToNewDir($rootDir, $emlRealPath, $newDbPath);
					if ($errMsg != "") {
						// 文件移动失败
						Mails::updateScanStatusById($info['mId'], 3);
						Scanlog::addScanlog($info['mId'], '【system】扫描失败。文件移动失败，错误信息：' . $errMsg);
					} else {
						// 扫描完成
						Mails::find($info['mId'])->save([
							'actualStatus'		=>	$actualStatus,
							'decisionStatus'	=>	$decisionStatus,
							'lastDecisionStatus'=>	$lastDecisionStatus,
							'scanStatus'		=>	2,
							'uploadDir'			=>	$newDbPath
						]);
						Scanlog::addScanlog($info['mId'], $finalLog);
					}
				} else {
					// 分数不是1或0，扫描失败
					Mails::updateScanStatusById($info['mId'], 3);
					Scanlog::addScanlog($info['mId'], '【system】扫描失败。cmd扫描分数异常，返回值：' . $score);
				}
			} else {
				// 未按约定获取扫描结果，扫描失败
				Mails::updateScanStatusById($info['mId'], 3);
				Scanlog::addScanlog($info['mId'], '【system】扫描失败。cmd执行结果异常，返回值：' . implode('', $cmdReturn));
			}
		} else {
			// 未找到文件，扫描失败
			Mails::updateScanStatusById($info['mId'], 3);
			Scanlog::addScanlog($info['mId'], '【system】扫描失败。未发现邮件，路径：' . $emlRealPath);
		}
	}
}
