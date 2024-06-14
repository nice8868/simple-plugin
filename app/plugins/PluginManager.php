<?php
namespace app\plugins;
use think\Exception;
use think\facade\Config;
use think\facade\Db;

/**
 * 插件管理器
 * @author jokers
 * @version 1.0
 * @package app\plugins
 * @access public
 * @date 2024-5-29
 * @email 279451209@qq.com
 * @link https://github.com/nice_11
 * @see PluginInterface
 * @see PluginMiddleware
 * @see PluginTrait
 * @see Plugin
 * @see PluginManager
 */
class PluginManager
{
    protected $pluginsPath;//路径
    // 构造函数
    public function __construct($pluginsPath)
    {
        // 获取插件目录
        $this->pluginsPath = $pluginsPath;

    }
    /**
     * 加载插件
     * @return array
     * @throws Exceptiona
     */
    public function loadPlugins()
    {
        try {
            //初始化插件
            $plugins = [];
            //获取插件目录
            $pluginDirs = scandir($this->pluginsPath);
            // 加载插件
            foreach ($pluginDirs as $dir) {
                // 过滤目录
                if ($dir === '.' || $dir === '..') {
                    // 继续循环
                    continue;
                }
                // 加载插件
                $pluginClass = "app\\plugins\\$dir\\Plugin";
                // 检测类是否存在
                if (class_exists($pluginClass)) {
                    // 实例化
                    $plugin = new $pluginClass();
                    //  检测是否实现插件接口
                    if ($plugin instanceof PluginInterface) {
                        // 保存插件
                        $plugins[$dir] = $plugin;

                        // 加载插件配置
                        $configFile = $this->pluginsPath .'/'. $dir . '/config.php';
                        // 检测配置文件是否存在
                        if (file_exists($configFile)) {
                            // 读取插件配置
                            $config = include $configFile;
                            // 保存插件配置
                            Config::set($config , lcfirst($dir));
                            //Config::set($config, $pluginInfo['name']);
                        }
                    } else {
                        // 抛出异常
                        throw new Exception('Plugin '.$dir.' does not implement PluginInterface');
                    }
                }
            }
            //}
        }catch (\Throwable $e){
            // 抛出异常
            throw new Exception($e->getMessage());
        }
        // 返回插件
        return $plugins;
    }

    /**
     * 安装
     * @param $plugin
     * @return void
     * @throws Exception
     */
    public function installPlugin($plugin)
    {

        try {
            // 检测是否实现插件接口
            $pluginClass = "app\\plugins\\$plugin\\Plugin";
            // 检测类是否存在
            if (class_exists($pluginClass)) {
                // 实例化
                $plugin = new $pluginClass();

                // 检测是否实现插件接口
                if ($plugin instanceof PluginInterface) {

                      $plugin->install();

                } else {
                    // 抛出异常
                    throw new Exception("Plugin $plugin does not implement PluginInterface");
                }
            } else {
                // 抛出异常
                throw new Exception("Plugin $plugin does not exist");
            }
        }catch (\Throwable $e){
            // 抛出异常
            throw new Exception($e->getMessage());
        }

    }

    /**
     * 卸渣
     * @param $plugin
     * @return void
     */
    public function uninstallPlugin($plugin)
    {
        try {
            // 检测是否实现插件接口
            $pluginClass = "app\\plugins\\$plugin\\Plugin";
            if (class_exists($pluginClass)) {
                $plugin = new $pluginClass();
                if ($plugin instanceof PluginInterface) {

                    $plugin->uninstall();
                } else {
                    throw new Exception("Plugin $plugin does not implement PluginInterface");
                }
            } else {
                throw new Exception("Plugin $plugin does not exist");
            }
        }catch (\Throwable $e){
            @throw new Exception($e->getMessage());
        }

    }

    /**
     * 插件执行逻辑
     * @return void
     * @throws Exception
     */
    public function executePlugins()
    {

        // 加载插件
        $plugins = $this->loadPlugins();

        // 执行插件
        foreach ($plugins as $plugin) {
            // 执行
            $plugin->execute();
        }

    }
}