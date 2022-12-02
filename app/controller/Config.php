<?php
namespace app\controller;

use app\model\Product;
use app\model\Package;

use app\BaseController;
use think\facade\View;
use think\Request;

class Config {
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

	public function product() {
		View::assign('menu','product');
		View::assign('li','config');
		return View::fetch();
	}

	public function pdlist() {
		$curPage = $this->request->param('page');
		$limit = $this->request->param('limit');

		$stmt = Product::select();
		$total = $stmt->count();
		$infos = $stmt->toArray();

		$return['code'] = 0;
		$return['count'] = $total;
		$return['data'] = $infos;
		echo json_encode($return);
	}

	public function version() {
		View::assign('menu','version');
		View::assign('li','config');
		return View::fetch();
	}

	public function pklist() {
		// 加载产品类型
		$dbProducts = Product::select()->column('pdName', 'pdId');

		$curPage = $this->request->param('page');
		$limit = $this->request->param('limit');
		
		$stmt = Package::select();
		$total = $stmt->count();
		$infos = $stmt->toArray();

		foreach ($infos as &$info) {
			$pdId = $info['pdId'];
			$info['pdDisplay'] = isset($dbProducts[$pdId]) ? $dbProducts[$pdId] : '---';
		}

		$return['code'] = 0;
		$return['count'] = $total;
		$return['data'] = $infos;
		echo json_encode($return);
	}
}