<?php
declare (strict_types = 1);

namespace app\middleware;

use app\Request;
use Closure;
use think\response\Redirect;

class Common
{
    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return mixed|Redirect|void
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * 检查是否登录
     * @return bool
     */
    public static function checkStatus(): bool
    {
        if (session('?name')) {
            return true;
        }
        return false;
    }
}
