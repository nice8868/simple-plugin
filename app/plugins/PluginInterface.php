<?php
namespace app\plugins;
/**
 * PluginInterface
 * 插件接口类
 * @author jokers
 * @date 2020-05-29
 * @package app\plugins
 */
interface PluginInterface
{
    /**
     * 必须实现安装 install方法
     * @return mixed
     */
    public function install();

    /**
     * 必须实现 卸载方法
     * @return mixed
     */
    public function uninstall();

    /**
     * 必须实现执行方法
     * @return mixed
     */
    public function execute();
}
