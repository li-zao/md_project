<?php
declare (strict_types = 1);

namespace app\middleware;

class isLogin
{
	/**
	 * 处理请求
	 *
	 * @param \think\Request $request
	 * @param \Closure       $next
	 * @return Response
	 */
	public function handle($request, \Closure $next) {
		$requestUrl = $request->pathinfo();
		// Api 无需登录验证
		if (strpos($requestUrl, 'api') === 0) {
			return $next($request);
		}

		if (strpos($_SERVER["REMOTE_ADDR"], '192.168.100.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.32.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.31.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.10.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.20.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.30.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.40.') !== 0 && strpos($_SERVER["REMOTE_ADDR"], '192.168.50.') !== 0) {
			header('HTTP/1.1 403 Forbidden');die;
		}

		if (strpos($requestUrl, 'dialog') === false) {
			if (!preg_match('/login/', $requestUrl)){
				//没有登录 跳转到登录页面
				if (!$this->checkStatus()) {
					return redirect((string)url('/index/login'));
				}
			}
		}

		return $next($request);
	}

	// 检查是否登录
	public function checkStatus() {
		if (session('?name')) {
			return true;
		} else {
			return false;
		}
	}
}
