<?php
namespace app\controller;

use app\model\ProjectList;
use app\model\Profile;

use app\BaseController;
use think\facade\View;
use think\Request;

class Project {
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
		// 加载所有项目
		$dbProfiles = Profile::select()->column('pfDisplay', 'pfId');
		View::assign('dbProfiles', $dbProfiles);

		// 加载清单类型
		$configProjectType = config('projectType');
		View::assign('configProjectType', $configProjectType);

		View::assign('menu','project');
		return View::fetch();
	}

	public function list() {
		$proListModel = new ProjectList;

		$curPage = $this->request->param('page');
		$limit = $this->request->param('limit');

		$searchParam = array();

		// 所属项目
		if ($this->request->has('pfid') && $this->request->param('pfid') !== "") {
			$searchParam['pfId'] = ['with' => '=', 'value' => $this->request->param('pfid')];
		}

		// 所属项目
		if ($this->request->has('ptype') && $this->request->param('ptype') !== "") {
			$searchParam['pType'] = ['with' => '=', 'value' => $this->request->param('ptype')];
		}
		
		$total = $proListModel->getAllCount($searchParam);
		$infos = $proListModel->getAllInfos($curPage, $limit, $searchParam);

		// 加载清单类型
		$configProjectType = config('projectType');

		// 加载清单状态
		$configProjectStatus = config('projectStatus');

		foreach ($infos as &$info) {
			$pfDisplay = Profile::find($info['pfId'])->pfDisplay;
			$info['pfName'] = $pfDisplay;
			$info['pTypeDisplay'] = $configProjectType[$info['pType']];
			$info['pStatusDisplay'] = $configProjectStatus[$info['pStatus']];
		}

		$return['code'] = 0;
		$return['count'] = $total;
		$return['data'] = $infos;
		echo json_encode($return);
	}
}