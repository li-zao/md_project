<?php
declare (strict_types = 1);

namespace app\controller;

use app\model\Mails;
use app\model\MimeMailParser;

use think\Request;

class Api
{
	public $importCmd = "php /var/www/md_scan/think import '#status#' '#dir#' --uid 7 --type mv";
	public $pathArray = array(
			0 => 'normal',
			1 => 'spam',
			2 => 'bad',
		);

	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
	public function index()
	{
		//
		
	}

	/**
	 * Eml上传
	 * 
	 * @param 	type 文件预期类型
	 *				notspam 误识别的垃圾邮件 期望正常邮件 type=0
	 *				notvirus 误识别的病毒邮件 期望正常邮件 type=0
	 *	      		spam 未识别的垃圾邮件 期望垃圾邮件 type=1
	 * 		  		virus 未识别的病毒邮件 期望病毒邮件 type=2
	 * @return json
	 */
	public function uploader(Request $request) {
		$fileObj = $request->file('eml');
		$type = $request->post('type');
		$fileMd5 = $fileObj->md5();

		if (!array_key_exists($type, $this->pathArray)) {
			$return = array(
				'code'	=>	0,
				'path'	=>	'',
				'md5'	=>	'',
				'subject'=> '',
				'msg'	=>	'非法参数',
			);
		} else {
			// 已存在文件不允许上传
			$alreadyUpload = Mails::checkIsUpload($fileMd5);
			if (!$alreadyUpload) {
				$saveDir = $this->pathArray[$type];

				try {
					// dump(config('filesystem')['disks']['public']['root']); 文件存储位置
					$savename = \think\facade\Filesystem::disk('maildata')->putFile( 'temp/'. $saveDir, $fileObj); //将文件上传至对应文件夹

					// 解析邮件内容
					$Parser = new MimeMailParser();
					$realPath = config('filesystem')['disks']['maildata']['root'] .'/'.$savename;
					$Parser->setPath ( $realPath );
					$temp = $Parser->getHeader('subject');
					$mail_subject = $Parser->decode_mime_subject($temp);

					// 执行导入
					$this->cmdimport($type, $realPath);

					$return = array(
						'code'	=>	0,
						'path'	=>	$savename,
						'md5'	=>	$fileMd5,
						'subject'=> $mail_subject,
						'msg'	=>	'ok',
					);
				} catch (Exception $e) {
					$return = array(
						'code'	=>	1,
						'path'	=>	'',
						'subject'=> '',
						'md5'	=>	'',
						'msg'	=>	$e->getMessage(),
					);
				}
			} else {
				$return = array(
						'code'	=>	1,
						'path'	=>	'',
						'subject'=> '',
						'md5'	=>	'',
						'msg'	=>	'存在相同MD5文件',
					);
			}
		}

		
		echo json_encode($return);
	}

	/**
	 * Eml上传
	 * 
	 * @return json
	 */
	public function cmdimport($type, $path) {
		if (array_key_exists($type, $this->pathArray)) {
			$cmd = str_replace("#status#", $type, $this->importCmd);
			$cmd = str_replace("#dir#", $path, $cmd);

			exec($cmd, $a, $b);
		}
	}

	/**
	 * 保存新建的资源
	 *
	 * @param  \think\Request  $request
	 * @return \think\Response
	 */
	public function save(Request $request)
	{
		//
	}

	/**
	 * 显示指定的资源
	 *
	 * @param  int  $id
	 * @return \think\Response
	 */
	public function read($id)
	{
		//
	}

	/**
	 * 保存更新的资源
	 *
	 * @param  \think\Request  $request
	 * @param  int  $id
	 * @return \think\Response
	 */
	public function update(Request $request, $id)
	{
		//
	}

	/**
	 * 删除指定资源
	 *
	 * @param  int  $id
	 * @return \think\Response
	 */
	public function delete($id)
	{
		//
	}
}
