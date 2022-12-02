<?php 
namespace app\model;

use think\facade\Session;

class CommonUtil {
	// 获取当前登录人id
	public static function getCurrentUid() {
		return Session::get('id');
	}

	// 获取当前登录人name
	public static function getCurrentUname() {
		return Session::get('name');
	}
}
?>