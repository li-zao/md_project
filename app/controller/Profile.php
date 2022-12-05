<?php
namespace app\controller;

use app\model\Profile as ProfileModel;

use app\BaseController;
use think\facade\View;
use think\Request;

class Profile extends BaseController
{

    public function index() {
        View::assign('menu','users');
        return View::fetch();
    }

    public function list() {
        $profile = new ProfileModel;
        [$page, $limit] = $this->pagination();
        $searchParam = [];
        $total = $profile->getAllCount($searchParam);
        $infos = $profile->getAllInfos($page, $limit, $searchParam);

        foreach ($infos as &$info) {

        }

        $return['code'] = 0;
        $return['count'] = $total;
        $return['data'] = $infos;
        echo json_encode($return);
    }
}