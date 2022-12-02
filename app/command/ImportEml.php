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
use app\model\MimeMailParser;

class ImportEml extends Command {
	protected $success = 0;
	protected $repeat  = 0;
	protected $insertArray = array(
		'scanStatus'	=>	8, // 扫描状态 8待人工审核 0待扫描
		'expectedStatus'=>	0, // 预期扫描状态
		'actualStatus'	=>	99, // 实际扫描状态 99其他
		'uploadTime'	=>	'', // 上传时间
		'uploadDir'		=>	'', // 文件路径（不包含根目录）
		'uploadMd5'		=>	'', // 文件MD5
		'operateUid'	=>	1, // 上传人uid
		'subject'		=>	'', // 邮件主题
		'tag'			=>	'', // 邮件标记 目前纯文本
		'mailDesc'		=>	'', // 邮件描述
	);
	protected $moveType = 'mv';

	protected function configure() {
		// 指令配置
		$this->setName('Import eml')
			 ->addArgument('expectedStatus', Argument::REQUIRED, "预期类型\n0\t正常邮件\n1\t可疑邮件\n2\tVirus")
			 ->addArgument('emlPath', Argument::REQUIRED, "存储邮件目录的绝对路径，请提前解压缩，eg:/root/emls")
			 ->addOption('type', null, Option::VALUE_REQUIRED, '文件复制方式, default:mv mv|cp')
			 ->addOption('uid', null, Option::VALUE_REQUIRED, '上传人uid')
			 ->addOption('desc', null, Option::VALUE_REQUIRED, '邮件标记')
			 ->addOption('skip', null, Option::VALUE_REQUIRED, '是否跳过审核 1是 0否 default0')
			 ->setDescription('MailData Scan Eml');
	}

	protected function execute(Input $input, Output $output) {
		// 是否存在邮件标记
		if ($input->hasOption('desc')) {
			// 存在，使用参数用的标记
			$this->insertArray['tag'] = $input->getOption('desc');
		} else {
			// 不存在，md5当前时间
			$this->insertArray['tag'] = md5(date('YmdHis'));
		}

		// 是否跳过审核
		if ($input->hasOption('skip')) {
			$skipAudit = $input->getOption('skip');
			if ($skipAudit == 1) {
				$this->insertArray['scanStatus'] = 0;
			}
		}

		// 指令输出
		$expectedStatus = $input->getArgument('expectedStatus');
		if (!is_numeric($expectedStatus)) {
			exit($output->writeln("Invalid Status"));
		}
		if ($expectedStatus > 2 || $expectedStatus < 0) {
			exit($output->writeln("Invalid Status"));
		}
		$this->insertArray['expectedStatus'] = $expectedStatus;

		$emlPath = rtrim($input->getArgument('emlPath'), '/');

		if ($input->hasOption('type')) {
			if ($input->getOption('type') == 'cp') {
				$this->moveType = 'cp';
			}
		}

		if ($input->hasOption('uid')) {
			$cmdUid = $input->getOption('uid');
			if (is_numeric($cmdUid)) {
				$this->insertArray['operateUid'] = $cmdUid;
			}
		}

		$output->writeln("Do import, dir:" . $emlPath . ", use:" . $this->moveType);
		
		$this->scanEmlDir($emlPath);

		$output->writeln("导入完成， 本次导入标记：".$this->insertArray['tag']."， 成功:" . $this->success . ", 已存在:" . $this->repeat);
	}

	protected function scanEmlDir($path) {
		if (is_file($path)) {
 			$this->addToDb($path);
		} else {
			$dh = opendir($path);

			while(($file = readdir($dh)) !== false){
				// 先要过滤掉当前目录'.'和上一级目录'..'
				if($file == '.' || $file == '..') {
					continue;
				}
				$tempPath = $path.'/'.$file;
				// 如果该文件仍然是一个目录，进入递归
				if(is_dir($tempPath)){
					$this->scanEmlDir($tempPath);
				} else {
					$this->addToDb($tempPath);
				}
			}
		}
	}

	protected function addToDb($path) {
		$resultStatus = config('resultStatus');
		$rootDir = config('filesystem')['disks']['maildata']['root'];
		$insertArray = $this->insertArray;

		$insertArray["uploadTime"] = date('Y-m-d H:i:s');
		$fileMD5 = md5_file($path);
		$insertArray["uploadMd5"] = $fileMD5;

		// 已存在文件不允许上传
		$alreadyUpload = Mails::checkIsUpload($fileMD5);
		if (!$alreadyUpload) {
			// 解析邮件内容
			$Parser = new MimeMailParser();
			$Parser->setPath ( $path );
			$temp = $Parser->getHeader('subject');
			$mail_subject = $Parser->decode_mime_subject($temp);
			$insertArray["subject"] = $mail_subject;

			// 新存储路径
			$newDbPath = "todo/" . date('Ymd') . '/' . $fileMD5 . '.eml';
			$insertArray["uploadDir"] = $newDbPath;

			$lastInsertId = Mails::addUploadData($insertArray);
			if (is_numeric($lastInsertId)) {
				$log = '【system】后台导入邮件样本，预期状态为：' . $resultStatus[$insertArray['expectedStatus']];
				if ($insertArray['scanStatus'] == 0) {
					$log .= "，命令行跳过审核";
				}
				Scanlog::addScanlog($lastInsertId, $log);
			}
			CommonUtil::moveToNewDir($rootDir, $path, $newDbPath, $this->moveType);
			$outputStr = "\tsuccess:" . $path;
			$this->success ++;
		} else {
			$outputStr = "\trepeat:" . $path;
			$this->repeat ++;
		}


		$this->output->writeln($outputStr);
	}
}
