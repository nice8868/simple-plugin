<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');


/********************jokers****************************/
// 插件动态加载插件路由,, 自动识别 在app\plugins\插件目录\route.php 路由文件
$pluginPath = app_path() . 'plugins/';
$pluginDirs = scandir($pluginPath);//拿到目录信息
//将目录下的路由文件加载
foreach ($pluginDirs as $dir) {
    // 过滤目录
    if ($dir === '.' || $dir === '..') {
        continue;
    }

    // 加载插件路由
    $routeFile = $pluginPath . $dir . '/route.php';
    // 检测文件是否存在
    if (file_exists($routeFile)) {
        // 加载插件路由
        include $routeFile;
    }
}
/********************jokers****************************/