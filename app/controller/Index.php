<?php
namespace app\controller;

use app\model\User;

use app\BaseController;
use think\Request;
use think\facade\View;
use think\facade\Session;

class Index extends BaseController {
	 /**
	 * @var \think\Request Request实例
	 */
	protected $request;
	
	/**
	 * 构造方法
	 * @param Request $request Request对象
	 * @access public
	 */
	public function __construct(Request $request) {
		$this->request = $request;
	}

	public function login() {
		return View::fetch();
	}

	public function dologin() {
		$return = array(
			'res' => 0,
			'msg' => ''
		);
		$username = $this->request->param('username');
		$password = $this->request->param('password');

		// 用户名验证
		$userInfo = User::where('uName', $username)->find();
		if ($userInfo) {
			if ($userInfo->uPassword == $password) {
				// 记录session
				session('name', $userInfo->uName);
				session('role', $userInfo->uRole);
				session('id', $userInfo->uId);
				$return['res'] = 1;
			} else {
				$return['msg'] = '密码错误';
			}
		} else {
			$return['msg'] = '未发现用户';
		}

		return json_encode($return);
	}

	public function dologout() {
		Session::clear();
	}

    public function index() {
    	View::assign('menu','index');
		return View::fetch();
    }
}
