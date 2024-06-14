<?php

namespace app\plugins\Test\controller;

use app\Request;
use think\App;

use app\BaseController;
/**
 *  Test
 * @author jokers
 * @date 2020-05-29
 * @package app\plugins
 * @version 1.0
 */
class Index extends BaseController
{
    /**
     * 多态实现公用增删改查
     * 引入traits
     * traits内实现了index、add、edit、read、delete 等方法,基于资源路由匹配,符合thinkphp6 资源路由 ,restful 风格
     */
    use \app\plugins\PluginTrait;
    protected $modelValidate = true;//true开启自动验证,false 关闭验证 , 自动匹配 app\plugins\模块\validate\控制器

    public function __construct(App $app , \app\plugins\Test\model\Test $model)
    {

        parent::__construct($app);
         $this->model = $model; // PluginTrait 复用
    }

    public function hello()
    {
        return 'Hello, this is a Test plugin!';
    }

    /**
     * 这是走快速构建查询代码  ---------- 这个控制器接受转发 组装 查询条件
     * 请求说明: 所有参数参数都放在filters中, 例子如下 (后台表单序列化)
     * 请求示例: /plugin/test/lists?filters[t.name][op]=like&filters[t.name][value]=53
     * @param Request $request
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function lists(Request $request){

        $this->success('',app()->make(\app\plugins\Test\services\TestServices::class)->lists($request->param()));
    }

    /**
     * 这是走快速构建查询代码 示例三
     * 请求说明:  所有参数通过 $searchable 配置映射 构建查询条件
     * 请求示例: /plugin/test/lists?log_search=joker&test_search=53
     * @param Request $request
     * @param int $id
     * @param string $name
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function listsByDao(Request $request){
        $this->success('',app()->make(\app\plugins\Test\services\TestServices::class)->listsByDao($request->param()));
    }

    /**
     * 这是走快速构建查询代码 示例二 ,  这个控制器接收组装 查询条件
     * 请求说明: 所有参数都重新 放在 filters中, 例子如下
     * 请求示例: /plugin/test/lists?name=joker&id=53
     * @param Request $request
     * @param int $id
     * @param string $name
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function lists2(Request $request){

        $where['filters']['t.delete_at']['operator']        = 'eq';  // operator 或 op 都行
        $where['filters']['t.delete_at']['value']           =  0;

        $where['filters']['t.name']['operator']             = 'like'; // operator 或 op 都行
        $where['filters']['t.name']['value']                =  $this->request->param('test_name' , '');

        $where['filters']['t.id']['op']                     = 'in';  // operator 或 op 都行
        $where['filters']['t.id']['value']                  =  $this->request->param('test_id' , '');

        $where['filters']['l.name']['op']                   = 'like'; // operator 或 op 都行
        $where['filters']['l.name']['value']                =  $this->request->param('log_name' , '');


        $this->success('',app()->make(\app\plugins\Test\services\TestServices::class)->lists($where));
    }

}
