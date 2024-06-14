<?php
// 示例插件路由
use think\facade\Route;
// 示例插件路由

//Route::rule('plugin/install/test', '\\app\\plugins\\Test\\controller\\Install@install')->method('post,get');//如安装 test 插件模块
//Route::rule('plugin/uninstall/test', '\\app\\plugins\\Test\\controller\\Install@uninstall');//如卸载 test 插件模块
Route::rule('plugin/install/[:name]', '\\app\\plugins\\Test\\controller\\Install@install')->method('post,get');//如安装 test 插件模块
Route::rule('plugin/uninstall/[:name]', '\\app\\plugins\\Test\\controller\\Install@uninstall')->method('post,get');//如卸载 test 插件模块


Route::get('plugin/test/hello', '\\app\\plugins\\Test\\controller\\Index@hello');//简单示例路由
Route::rule('plugin/test/lists', '\\app\\plugins\\Test\\controller\\Index@lists')->method('post,get');//简单构建快速查询
Route::rule('plugin/test/lists2', '\\app\\plugins\\Test\\controller\\Index@lists2')->method('post,get');//构建快速查询
Route::rule('plugin/test/listsByDao', '\\app\\plugins\\Test\\controller\\Index@listsByDao')->method('post,get');//构建 查询
// 示例访问插件控制器插件路由
Route::resource('plugin/test', '\\app\\plugins\\Test\\controller\\Index');//请求plugin/test  访问app\plugins\Test\controller\Index@index 方法
