<?php
namespace app\controller;

use app\model\ProjectList;
use app\model\Profile;
use app\model\Product;
use app\model\Package;
use app\model\Device;
use app\model\CommonUtil;
use app\model\User;

use app\BaseController;
use think\facade\View;
use think\Request;

class Dialog {
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

	// 项目增改弹窗
	public function project() {
		$pId = $this->request->param('pid');
		View::assign('pId', $pId);

		$projectInfo = array();
		$defaultPfid = 0;
		if (is_numeric($pId)) {
			$projectInfo = ProjectList::find($pId)->toArray();
			$defaultPfid = $projectInfo['pfId'];
		}
		View::assign('projectInfo', $projectInfo);

		// 查询所有用户及对应产品
		// $dbProfiles = Profile::alias('pf')
		// 			->join('product pd', 'pf.pdId = pd.pdId')
		// 			->select()->toArray();
		$dbProfiles = Profile::select()->toArray();
		$profilesInfos = array();
		foreach ($dbProfiles as $dbProfile) {
			$xmOption = ['name' => $dbProfile['pfDisplay'], 'value' => $dbProfile['pfId']];
			if ($defaultPfid == $dbProfile['pfId']) {
				$xmOption['selected'] = true;
			}
			$profilesInfos[] = $xmOption;
		}

		View::assign('profilesInfos', json_encode($profilesInfos));

		// 问题类型
		View::assign('configProjectType', config('projectType'));

		// 所有账号，指派用
		$dbUsers = User::select()->column('uName', 'uId');
		View::assign('dbUsers', $dbUsers);


		return View::fetch();
	}

	// 增改项目DB
	public function updateproject() {
		$pId = $this->request->param('pId');

		$data = array(
			"pfId"		=> $this->request->param('pfId'),
			"pType"		=> $this->request->param('pType'),
			"pTitle"	=> addslashes($this->request->param('pTitle')),
			"pContent"	=> addslashes($this->request->param('pContent')),
			'pNowUID'	=> $this->request->param('pnId')
		);

		if (is_numeric($pId)) {
			// update
			$projectInfo = ProjectList::find($pId)->toArray();
			switch ($projectInfo['pType']) {
				case '0':
					$data['subValue'] = $this->request->param('bugFrom');
					$data['subIn'] = $this->request->param('bugIn');
					$data['subDate'] = $this->request->param('bugDate');
					$data['subContent'] = $this->request->param('bugContent');
					$data['pStatus'] = $this->request->param('bugStatus');
					break;
				case '1':
					$data['pStatus'] = $this->request->param('configStatus');
					$data['subContent'] = $this->request->param('configContent');
					break;
				case '2':
					$data['subValue'] = $this->request->param('customDays');
					$data['subIn'] = $this->request->param('customIn');
					$data['subDate'] = $this->request->param('customDate');
					$data['subContent'] = $this->request->param('customContent');
					$data['pStatus'] = $this->request->param('customStatus');
					break;
				case '3':
					break;
			}


			unset($data['pfId'], $data['pType']);
			$data['pId'] = $pId;
			ProjectList::update($data);
		} else {
			// add
			$data['pStatus'] = $data['pType'] * 10;
			$data['pCreateUID'] = CommonUtil::getCurrentUid();
			$data['pCreateTime'] = date('Y-m-d H:i:s');
			$pId = ProjectList::add($data);
		}

		if (is_numeric($pId)) {
			echo 1;
		} else {
			echo 0;
		}
	}

	// 用户管理弹窗
	public function profile() {
		$pfId = $this->request->param('pfid');

		// 加载用户、设备信息
		$pfInfo  = array();
		$devices = array(["dName"=>'', "dIp"=>'', "dUser"=>'', "dPass"=>'', "dContent"=>'']);
		if (is_numeric($pfId)) {
			$pfInfo = Profile::find($pfId)->toArray();
			$dbDevices = Device::where('pfId', $pfId)->select()->toArray();
			if (!empty($dbDevices)) {
				$devices = $dbDevices;
			}
		}
		View::assign('pfInfo', $pfInfo);
		View::assign('devices', $devices);
		View::assign('deviceIndex', count($devices));


		// 加载产品类型
		$dbProducts = Product::select()->toArray();
		View::assign('dbProducts', $dbProducts);

		// 加载包
		$dbPackages = Package::select()->toArray();
		View::assign('dbPackages', $dbPackages);

		return View::fetch();
	}

	// 增改用户DB
	public function updateprofile() {
		$pfId = $this->request->param('pfId');

		$data = array(
			"pfName"	=> addslashes($this->request->param('pfName')), // 用户名称
			"pdId"		=> $this->request->param('pdId'), // 产品id
			"pfDisplay"	=> addslashes($this->request->param('pfDisplay')), // 显示名称
			"pkId"		=> $this->request->param('pkId'), // 包id
		);

		if (is_numeric($pfId)) {
			// update
			$data['pfId'] = $pfId;
			Profile::update($data);
		} else {
			// add
			$pfId = Profile::add($data);
		}

		// 根据pfId，重置device信息
		Device::deleteByPfId($pfId);

		$deviceInfos = $this->request->param('device');
		foreach ($deviceInfos as $dInfo) {
			$deviceData = array(
				'pfId'	=>	$pfId,
				'dName'	=>	$dInfo[0],
				'dIp'	=>	$dInfo[1],
				'dUser'	=>	$dInfo[2],
				'dPass'	=>	$dInfo[3],
				'dContent'	=>	$dInfo[4],
			);
			Device::add($deviceData);
		}

		if (is_numeric($pfId)) {
			echo 1;
		} else {
			echo 0;
		}
	}

	// 用户摘要弹窗
	public function pfsummary() {
		$pfId = $this->request->param('pfid');

		$pfInfo = Profile::find($pfId)->toArray();
		$pfInfo['product'] = Product::find($pfInfo['pdId'])->toArray()['pdName'];
		$pfInfo['package'] = Package::find($pfInfo['pkId'])->toArray()['pkName'];
		$pfInfo['devices'] = Device::where('pfId', $pfId)->select()->toArray();

		// 加载清单类型
		$configProjectType = config('projectType');
		// 加载清单状态
		$configProjectStatus = config('projectStatus');
		
		$tasks = ProjectList::where('pfId', $pfId)->select()->toArray();
		foreach ($tasks as &$task) {
			$task['pTypeDisplay'] = $configProjectType[$task['pType']];
			$task['pStatusDisplay'] = $configProjectStatus[$task['pStatus']];
		}
		$pfInfo['tasks']   = $tasks;
		
		View::assign('pfInfo', $pfInfo);

		return View::fetch();
	}

	// 版本增改弹窗
	public function package() {
		$pkId = $this->request->param('pkid');

		$packageInfo = array();
		if (is_numeric($pkId)) {
			$packageInfo = Package::find($pkId)->toArray();
		}
		View::assign('packageInfo', $packageInfo);

		// 加载产品类型
		$dbProducts = Product::select()->toArray();
		View::assign('dbProducts', $dbProducts);

		// 加载所有main包
		$mainPackages = Package::where('pkType', 0)->select()->toArray();
		View::assign('mainPackages', $mainPackages);

		return View::fetch();
	}

	// 增改版本DB
	public function updatepackage() {
		$pkId = $this->request->param('pkId');

		$data = array(
			"pkName"	=> $this->request->param('pkName'),
			"pdId"		=> $this->request->param('pdId'),
		);

		$pkRadio = $this->request->param('pkRadio');
		if ($pkRadio == 0) {
			$data['pkType'] = 0;
		} else {
			$data['pkType'] = $this->request->param('parentId');
		}

		if (is_numeric($pkId)) {
			// update
			unset($data['pdId']);

			$data['pkId'] = $pkId;
			Package::update($data);
		} else {
			// add

			// 新增时完善一次包名
			if ($data['pkType'] > 0) {
				$parentInfo = Package::find($data['pkType'])->toArray();
				$data['pkName'] = $parentInfo['pkName'] . "[". $data['pkName'] ."]";
			}

			$pkId = Package::add($data);
		}

		if (is_numeric($pkId)) {
			echo 1;
		} else {
			echo 0;
		}
	}

	// 账号增改弹窗
	public function accounts() {
		$uId = $this->request->param('uid');
		View::assign('uId', $uId);

		$userInfo = array();
		if (is_numeric($uId)) {
			$userInfo = User::find($uId)->toArray();
			$uAuth = $userInfo['uAuth'];
			if ($uAuth&1) {
				$userInfo[1] = 1;
			}
			if ($uAuth&2) {
				$userInfo[2] = 2;
			}
			if ($uAuth&4) {
				$userInfo[4] = 4;
			}
		}
		View::assign('userInfo', $userInfo);

		$configRoles = config('userRole');
		View::assign('configRoles', $configRoles);

		return View::fetch();
	}

	// 增改账号DB
	public function updateaccounts() {
		$uId = $this->request->param('uId');

		$data = array(
			"uName"		=> $this->request->param('uName'),
			"uPassword"	=> $this->request->param('uPassword'),
			"uMail"		=> $this->request->param('uMail'),
			'uRole'		=> $this->request->param('uRole'),
			'uStatus'	=> $this->request->param('uStatus'),
		);

		$uAuth = 0;
		$formAuth = $this->request->param('uAuth');
		if ($data['uRole'] == 'dev') {
			if (is_array($formAuth)) {
				foreach ($formAuth as $authVal) {
					$uAuth += $authVal;
				}
			}
		}
		$data['uAuth'] = $uAuth;

		if (is_numeric($uId)) {
			// update
			$data['uId'] = $uId;
			User::update($data);
		} else {
			// add
			$uId = User::add($data);
		}

		if (is_numeric($uId)) {
			echo 1;
		} else {
			echo 0;
		}
	}
}