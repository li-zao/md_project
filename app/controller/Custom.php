<?php
namespace app\controller;

use app\model\CustomList;

use app\BaseController;
use think\facade\View;
use think\Request;

class Custom {
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
    	View::assign('menu','custom');
        View::assign('li','projectlog');
        return View::fetch();
    }

    public function list() {
    	$cusListModel = new CustomList;

    	$curPage = $this->request->param('page');
    	$limit = $this->request->param('limit');

    	$searchParam = array();
    	$total = $cusListModel->getAllCount($searchParam);
    	$infos = $cusListModel->getAllInfos($curPage, $limit, $searchParam);

    	foreach ($infos as &$info) {

    	}

    	$return['code'] = 0;
    	$return['count'] = $total;
    	$return['data'] = $infos;
    	echo json_encode($return);
    }
}