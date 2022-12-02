<?php
namespace app\model;

use think\Model;

class Package extends Model {
	// 主键
	protected $pk = 'pkId';

	public static function add($data) {
		$lastInsertId = self::insertGetId($data);
		return $lastInsertId;
	}
}