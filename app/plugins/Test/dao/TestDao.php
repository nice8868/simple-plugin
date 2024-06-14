<?php
namespace app\plugins\Test\dao;


use app\plugins\PluginQueryBuilder;
use app\plugins\Test\model\Test as Model;



/**
 * 示例服务层
 * Class TestServices
 * @package app\plugins\Test\services
 * @version 1.0
 * @description
 */
class TestDao
{
    //搜索映射条件   dao层主要定义联表 查询查询条件
    public array $searchable = [
        'log_id'            => ['eq'    , 'l.id'],
        'log_search'        => ['like'  , 'l.name|l.id'],
        'test_search'       => ['like'  , 't.name'],
        'delete_at'         => ['eq'    , 't.delete_at'],
        'test_id'           => ['eq'    , 't.id'],
    ];

    /**
     * 配置数组定义搜索查询
     * @param $params
     * @param $user
     * @param $buildPower
     * @param $sort
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function lists($params = [] , $user = [] , $buildPower = true , $sort = ['id' => 'desc']){
        // 构建查询条件和排序方式
        $query = PluginQueryBuilder::buildQuery(
            self::buildInitSql(), //查询培训记录及其关联的信息
            PluginQueryBuilder::buildWhereParams($params , $this->searchable ,  1),//dao层联表，配置数组定义查询方式
            $sort //排序
        );

        // 数据权限范围
        //if ($buildPower) $query = PluginQueryBuilder::buildPower($query, $user);

        // 执行查询并分页
        return $query->paginate($param['page_size'] ?? $param['pageSize'] ?? 20, false)->each(function ($item) {
            $item['create_at']      = date('Y-m-d H:i:s', $item['create_at']);
            return $item;
        }) ;
    }

    /**
     * 初始化查询对象,目标记录及其关联的信息
     *  为什么这么写...........最近项目联表太多了...........
     * @return mixed
     */
    public static function buildInitSql(){
        return \think\facade\Db::name('plugin_test')
            ->alias('t')
            ->leftJoin('plugin_log l', 'l.test_id = t.id')
            //->leftJoin('plugin_log2 l2', 'l2.test_id = t.id')
            //->leftJoin('plugin_log3 l3', 'l3.test_id = t.id')
            ->field(['t.*', 'l.name as log_name','l.id as log_id','t.*' , 't.name as test_name' , 't.id as test_id' ]);
    }
}
