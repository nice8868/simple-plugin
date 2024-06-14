<?php
namespace app\plugins;

use app\plugins\PluginManager;
use Closure;

/**
 * 插件中间件
 * Author: jokers
 * date: 2024/5/29
 */
class PluginMiddleware
{
    /**
     * 插件中间件
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws \think\Exception
     */
    public function handle($request, Closure $next)
    {
        // 执行插件
        $pluginManager = new PluginManager(app_path() . 'plugins');
        // 执行插件
        $pluginManager->executePlugins();
        // 执行下一个中间件
        return $next($request);
    }
}
