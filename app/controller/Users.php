<?php
namespace app\controller;

use app\model\Profile;

use app\BaseController;
use think\facade\View;
use think\Request;

class Users {
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
    	View::assign('menu','users');
        return View::fetch();
    }

    public function list() {
    	$profileModel = new Profile;

    	$curPage = $this->request->param('page');
    	$limit = $this->request->param('limit');

    	$searchParam = array();
    	$total = $profileModel->getAllCount($searchParam);
    	$infos = $profileModel->getAllInfos($curPage, $limit, $searchParam);

    	foreach ($infos as &$info) {

    	}

    	$return['code'] = 0;
    	$return['count'] = $total;
    	$return['data'] = $infos;
    	echo json_encode($return);
    }
}