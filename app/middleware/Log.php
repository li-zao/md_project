<?php
declare (strict_types = 1);

namespace app\middleware;

use app\Request;
use Closure;
use think\response\Redirect;

class Log extends Common
{
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
        if (strpos($requestUrl, 'dialog') !== false) {
            return $next($request);
        }
        if (preg_match('/login/', $requestUrl)) {
            return $next($request);
        }
        if (!$this->checkStatus()) {
            return redirect((string)url('/index/login'));
        }
        if ($request->isPost()) {
            // @TODO: insert log
        }
        return $next($request);
    }
}
