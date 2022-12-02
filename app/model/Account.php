<?php
namespace app\model;

use think\Model;

class Account extends Model {
	// 主键
	protected $pk = 'uId';

	public static function getUsernameById($id) {
		$userInfo = self::where('uId', $id)->find()->toArray();

		if ($userInfo) {
			return $userInfo['uName'];
		} else {
			return 'unknown';
		}
	}

	public static function getAllCount($searchParam=array()) {
		$infos = self::whereRaw('1=1');

		foreach ($searchParam as $dbField => $searchInfo) {
			$infos->where($dbField, $searchInfo['with'], $searchInfo['value']);
		}

		$total = $infos->select()->count();

		return $total;
	}

	public static function getAllInfos($curPage, $limit, $searchParam=array()) {
		$infos = self::whereRaw('1=1');
		foreach ($searchParam as $dbField => $searchInfo) {
			$infos->where($dbField, $searchInfo['with'], $searchInfo['value']);
		}

		// 查看sql，在select前 fetchSql(true)
		$infos = $infos->order('pId', 'desc')->page($curPage, $limit)->select()->toArray();

		return $infos;
	}
}