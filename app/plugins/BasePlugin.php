<?php
namespace app\plugins;
/**
 * 插件基类
 * @author jokers
 * @date 2020-05-29
 * @package app\plugins
 * @version 1.0
 */
abstract class BasePlugin implements PluginInterface
{
    /**
     * 必须实现安装
     * @return mixed
     */
    abstract public function install();

    /**
     *  必须实现 卸载
     * @return mixed
     */
    abstract public function uninstall();

    /**
     *  必须实现执行
     * @return mixed
     */
    abstract public function execute();
}
