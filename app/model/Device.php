<?php
namespace app\model;

use think\Model;

class Device extends Model {
	// 主键
	protected $pk = 'dId';

	public static function deleteByPfId($pfId) {
		self::where('pfId', $pfId)->delete();
	}

	public static function add($data) {
		$lastInsertId = self::insertGetId($data);
		return $lastInsertId;
	}
}