<?php
namespace app\controller;

use app\common\Common;
use app\model\User;
use app\BaseController;
use Exception;
use think\facade\View;
use think\facade\Session;
use think\response\Json;

class Index extends BaseController
{
    /**
     * 登录页面
     * @return string
     */
    public function login() {
        return View::fetch();
    }

    /**
     * 登录动作
     * @return Json
     */
    public function dologin() {
        $username = $this->request->param('username');
        $password = $this->request->param('password');
        try {
            // 用户名验证
            $userInfo = User::where('uName', $username)->find();
            if (empty($userInfo->uPassword) || $userInfo->uPassword != $password) {
                return self::jsonAPI([], Common::CODE_NO, '密码错误');
            }
            if (empty($userInfo->uStatus)) {
                return self::jsonAPI([], Common::CODE_NO, '账户不可用');
            }
            session('name', $userInfo->uName);
            session('role', $userInfo->uRole);
            session('id', $userInfo->uId);
            return self::jsonAPI();
        } catch (Exception $e) {
            return self::jsonAPI([], Common::CODE_NO, '系统错误');
        }
    }

    /**
     * 登出
     */
    public function dologout() {
        Session::clear();
    }

    /**
     * 首页
     * @return string
     */
    public function index() {
        View::assign('menu','index');
        return View::fetch();
    }
}
