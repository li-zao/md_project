<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class pullEml extends Command
{

	public $apiUrl = "http://123.207.138.99:33333";
	public $listApi = "/download/getmailidlist";
	public $fileApi = "/download/downloademl";
	public $defaultUid = "7"; //用户名是manage
	public $importCmd = "php /var/www/md_scan/think import '#status#' '#dir#' --desc #desc# --uid 7 --type mv";

	// key网关机器状态 value本系统预期状态
	public $status = array(
			1   =>  array('zh' => '未识别的垃圾邮件', 'val' => 1 ), // 未识别的垃圾邮件 期望垃圾邮件
			2   =>  array('zh' => '误识别的垃圾邮件', 'val' => 0 ), // 误识别的垃圾邮件 期望正常邮件
			3   =>  array('zh' => '未识别的病毒邮件', 'val' => 2 ), // 未识别的病毒邮件 期望病毒邮件
			4   =>  array('zh' => '误识别的病毒邮件', 'val' => 0 ), // 误识别的病毒邮件 期望正常邮件
	);

	protected function configure()
	{
		// 指令配置
		$this->setName('app\command\pulleml')
			->addArgument('startdate', Argument::OPTIONAL, "拉取邮件的日志，eg:yyyy-mm-dd 或 yesterday")
			->setDescription("在网关中心机器拉取数据，不能拉取当日的，输入日期表示拉取输入如期至昨天的数据，yesterday直接拉取昨天数据");
	}

	protected function execute(Input $input, Output $output)
	{
		if ($input->getArgument('startdate')) {
			$rootDir = config('filesystem')['disks']['maildata']['root'] . '/temp/download';

			$startdate = trim($input->getArgument('startdate'));
			$apiToken = md5(date('Ymd').'maildata');

			if ($startdate == "yesterday") {
				$enddate = $startdate = date('Y-m-d', strtotime('-1 day'));
			} else {
				$enddate = date('Y-m-d', strtotime('-1 day'));
			}

			$output->writeln('准备拉取:' . $startdate);

			// 循环拉取不同状态的邮件
			foreach ($this->status as $originStatus => $eachInfo) {
				$output->writeln("\t获取【" . $eachInfo["zh"] . "】队列");
				$post_data = array(
					'token' => $apiToken,
					'start' => $startdate,
					'end'	=> $enddate,
					'type'  => $originStatus
				);

				$returnIds = $this->doPost(0, $post_data);
				if ($returnIds) {
					$idsArray = json_decode($returnIds, true);
					$output->writeln("\t\t查询到【" . count($idsArray) . "】条数据");
					
					foreach ($idsArray as $originId) {
						$itemLog = "\t\tid【" . $originId . "】，";
						$downloadData = array(
							'token' => $apiToken,
							'id'	=> $originId
						);
						$fileInfo = $this->doPost(1, $downloadData);
						if ($fileInfo != "") {
							$itemLog .= "下载成功。";
							$filePath = $rootDir . '/' . $originId;
							exec('touch '.$filePath);
							file_put_contents($filePath, $fileInfo);

							// 执行导入
							$this->cmdimport($eachInfo['val'], $filePath, "管理中心导入【" . $eachInfo["zh"] . "】");
						} else {
							$itemLog .= "下载失败，文件内容为空";
						}

						$output->writeln($itemLog);
					}
				} else {
					$output->writeln("\tid列表获取失败");
				}

				// $expectedStatus
			}
		} else {
			$output->writeln('参数错误');
		}
	}

	// type 0获取id 1下载文件
	protected function doPost($type, $post_data) {
		if ($type == 0) {
			$url = $this->apiUrl . $this->listApi;
		} else {
			$url = $this->apiUrl . $this->fileApi;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$return_data = curl_exec($ch);
		curl_close($ch);

		return $return_data;
	}

	/**
	 * Eml上传
	 * 
	 * @return json
	 */
	public function cmdimport($type, $path, $desc) {
		$cmd = str_replace("#status#", $type, $this->importCmd);
		$cmd = str_replace("#dir#", $path, $cmd);
		$cmd = str_replace("#desc#", $desc, $cmd);

		exec($cmd, $a, $b);
	}
}
