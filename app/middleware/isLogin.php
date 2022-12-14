<?php
declare (strict_types = 1);

namespace app\middleware;

use app\Request;
use Closure;
use think\Response;
use think\response\Redirect;

class isLogin extends Common
{
    public const IP_WHITE_LIST = [
        '192.168.100.',
        '192.168.32.',
        '192.168.31.',
        '192.168.10.',
        '192.168.20.',
        '192.168.30.',
        '192.168.40.',
        '192.168.50.',
        '192.168.50.',
        '127.0.0.',
    ];

    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return mixed|Redirect|void
     */
    public function handle(Request $request, Closure $next)
    {
        $requestUrl = $request->pathinfo();
        // Api 无需登录验证
        if (strpos($requestUrl, 'api') === 0) {
            return $next($request);
        }
        $ipCheck = false;
        foreach (self::IP_WHITE_LIST as $ip) {
            if (stripos($_SERVER['REMOTE_ADDR'], $ip) !== false) {
                $ipCheck = true;
                break;
            }
        }
        if (!$ipCheck) {
            return Response::create('', 'html', 403);
        }

        if (strpos($requestUrl, 'dialog') !== false) {
            return $next($request);
        }
        if (preg_match('/login/', $requestUrl)) {
            return $next($request);
        }
        if (!$this->checkStatus()) {
            return redirect((string)url('/index/login'));
        }
        return $next($request);
    }
}
