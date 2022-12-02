<?php
namespace app\model;

use think\Model;

class Project extends Model {
	// 主键
	protected $pk = 'pId';

	public static function addUploadData($data) {
		$lastInsertId = self::insertGetId($data);
		return $lastInsertId;
	}

	public static function checkIsUpload($fileMd5) {
		$total = self::where('uploadMd5', $fileMd5)->select()->count();

		if ($total > 0) {
			return true;
		} else {
			return false;
		}
	}

	public static function getMailsByScanStatus($scanStatus) {
		$infos = self::where('scanStatus', $scanStatus)->select()->toArray();

		return $infos;
	}

	public static function updateScanStatusById($id, $scanStatus) {
		self::find($id)->save(['scanStatus' => $scanStatus]);
	}

	public static function getAllCount($from='', $searchParam=array()) {
		if ($from == '') {
			return 0;
		} else {
			if ($from == 'todo') {
				// 待办 未扫描或者扫描完成但是不符合预期
				$infos = self::whereRaw('(scanStatus in (0,1,3) or (scanStatus = 2 and decisionStatus = 0))');
			} else if ($from == 'normal') {
				// 正常 扫描结果为正常邮件 且 符合预期
				$infos = self::whereRaw('scanStatus = 2 and actualStatus = 0 and decisionStatus = 1');
			} else if ($from == 'bad') {
				// 可疑 扫描结果为可疑邮件 且 符合预期
				$infos = self::whereRaw('scanStatus = 2 and actualStatus = 1 and decisionStatus = 1');
			} else if ($from == 'virus') {
				// 病毒 扫描结果为病毒邮件 且 符合预期
				$infos = self::whereRaw('scanStatus = 2 and actualStatus = 2 and decisionStatus = 1');
			} else if ($from == 'waiting') {
				// 待审核队列
				$infos = self::whereRaw('scanStatus = 8');
			}

			foreach ($searchParam as $dbField => $searchInfo) {
				$infos->where($dbField, $searchInfo['with'], $searchInfo['value']);
			}

			$total = $infos->select()->count();
		}

		return $total;
	}

	public static function getAllInfos($curPage, $limit, $from='', $searchParam=array()) {
		if ($from == '') {
			return array();
		} else {
			if ($from == 'todo') {
				// 待办 未扫描或者扫描完成但是不符合预期
				$infos = self::whereRaw('(scanStatus in (0,1,3) or (scanStatus = 2 and decisionStatus = 0))');
			} else if ($from == 'normal') {
				// 正常 扫描结果为正常邮件 且 符合预期
				$infos = self::whereRaw('scanStatus = 2 and actualStatus = 0 and decisionStatus = 1');
			} else if ($from == 'bad') {
				// 可疑 扫描结果为可疑邮件 且 符合预期
				$infos = self::whereRaw('scanStatus = 2 and actualStatus = 1 and decisionStatus = 1');
			} else if ($from == 'virus') {
				// 病毒 扫描结果为病毒邮件 且 符合预期
				$infos = self::whereRaw('scanStatus = 2 and actualStatus = 2 and decisionStatus = 1');
			} else if ($from == 'waiting') {
				// 待审核队列
				$infos = self::whereRaw('scanStatus = 8');
			}

			foreach ($searchParam as $dbField => $searchInfo) {
				$infos->where($dbField, $searchInfo['with'], $searchInfo['value']);
			}

			// 查看sql，在select前 fetchSql(true)
			$infos = $infos->order('mId', 'desc')->page($curPage, $limit)->select()->toArray();
		}

		return $infos;
	}
}