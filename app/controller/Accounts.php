<?php
namespace app\controller;

use app\model\User;

use app\BaseController;
use think\facade\View;
use think\Request;

class Accounts {
	/**
	 * @var \think\Request Request实例
	 */
	protected $request;
	
	/**
	 * 构造方法
	 * @param Request $request Request对象
	 * @access public
	 */
	public function __construct(Request $request=null) {
		$this->request = $request;
	}

	public function index() {
		View::assign('menu','accounts');
		
		return View::fetch();
	}

	public function list() {
		$userModel = new User;

		$curPage = $this->request->param('page');
		$limit = $this->request->param('limit');

		$searchParam = array();
		$total = $userModel->getAllCount($searchParam);
		$infos = $userModel->getAllInfos($curPage, $limit, $searchParam);

		$configRoles = config('userRole');

		foreach ($infos as &$info) {
			$userRole = $info['uRole'];
			$userStataus = $info['uStatus'];
			$info['roleDisplay'] = isset($configRoles[$userRole]) ? $configRoles[$userRole] : 'error';
			$info['statusDisplay'] = $info['uStatus'] == 1 ? '启用' : '禁用';
		}

		$return['code'] = 0;
		$return['count'] = $total;
		$return['data'] = $infos;
		echo json_encode($return);
	}
}