<?php
namespace app\plugins\Test;
use app\plugins\BasePlugin;
use think\facade\Db;
/**
 * 示例插件
 * @author jokers
 * @date 2020-05-29
 * @package app\plugins
 */
class Plugin extends BasePlugin
{
    public string $path     = ROOT_PATH.'\\app\\plugins\\Test\\rules.txt';// 插件路由权限记录文件
    public function install()
    {
        // 插件安装逻辑
        // 如创建一个示例数据表
        Db::execute("
            CREATE TABLE IF NOT EXISTS `plugin_test` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `admin_id` INT(11) NOT NULL DEFAULT 0,
                `create_at` INT(11) NOT NULL DEFAULT 0,
                `update_at` INT(11) NOT NULL DEFAULT 0,
                `delete_at` INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        Db::execute("
            CREATE TABLE IF NOT EXISTS `plugin_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `test_id` INT(11) NOT NULL DEFAULT 0,
                `admin_id` INT(11) NOT NULL DEFAULT 0,
                `create_at` INT(11) NOT NULL DEFAULT 0,
                `update_at` INT(11) NOT NULL DEFAULT 0,
                `delete_at` INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $name = rand(1000,9999);
        Db::transaction(function () use ($name) {
            $write[] = Db::name( 'plugin_test')->insertGetId([
                'name' => $name,
                'create_at' => time(),
            ]);
            $write[] = Db::name( 'plugin_log')->insertGetId([
                'name' => $name,
                'test_id' => $write[0],
                'create_at' => time(),
            ]);
            // 将菜单id 字符串写入文件，如果文件不存在则创建 方便以后自动删除 // 指定文件路径 // 要写入的字符串
            file_put_contents($this->path, serialize($write ?? []));
        });
    }

    public function uninstall()
    {
        // 插件卸载逻辑
        // 删除示例数据表
        Db::execute("DROP TABLE IF EXISTS `plugin_test`");
        if (file_exists($this->path)) {
            // 打开文件
            $file = fopen($this->path, 'r');

            // 读取文件内容
            $content = fread($file, filesize($this->path));

            // 关闭文件
            fclose($file);

            if($content && !empty($content) ){
                $content = unserialize($content);
                //删除菜单路由
            }
            //删除菜单路由文件
            unlink($this->path);
            //删除插件代码目录
            //rmdir(dirname($this->path));
        }

    }

    public function execute()
    {

        // 插入菜单
//        Db::transaction(function () use (    $timer) {
//              Db::name( 'plugin_test')->insertGetId([
//                'type' => 'file',
//                'pid' => 0,
//                'name' => 'plugin/plan',
//                'title' => '培训模块',
//                'icon' => 'fa fa-circle-o',
//                'condition' => '',
//                'remark' => '培训模块',
//                'ismenu' => 0,
//                'create_at' => $timer,
//                'update_at' => $timer,
//                'weigh' => 0,
//                'status' => 'normal',
//
//            ]);
//        });
    }
}
