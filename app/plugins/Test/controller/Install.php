<?php

namespace app\plugins\Test\controller;

use app\plugins\PluginManager;
use think\exception\ValidateException;
use think\facade\App;
use think\facade\Config;
use think\Request;
use app\BaseController;
/**
 * 培训插件安装与卸载
 * @author jokers
 * @date 2020-05-29
 * @package app\plugins
 * @version 1.0
 */
class Install extends BaseController
{
    /**
     *  插件安装
     * @param $name  string 插件标识名称
     * @return void
     */
    public function install(Request $request)
    {

        if(!$request->param('name') || empty($request->param('name'))) {
            //throw new ValidateException('参数错误');
            $this->error( '安装参数错误');
        }
        $pluginManager = new PluginManager(App::getAppPath() . 'plugins/');
        $pluginManager->installPlugin($request->param('name'));
        $this->success(  (Config::get('Test')['name']?? ' ').'插件安装成功 Plugin installed successfully!');

    }

    /**
     * 插件卸载
     * @param $name  string 插件标识名称
     * @return void
     */
    public function uninstall(Request $request)
    {
        if(!$request->param('name') || empty($request->param('name'))) {
            $this->error( '卸载参数错误');
        }
        $pluginManager = new PluginManager(App::getAppPath() . 'plugins/');
        $pluginManager->uninstallPlugin($request->param('name'));
        $this->success((Config::get('Test')['name']?? ' '). '插件卸载成功 Plugin uninstalled successfully!');

    }

    public function execute(){

    }
}
