<?php
namespace app\plugins\Test\services;


use app\plugins\PluginQueryBuilder;
use app\plugins\Test\model\Test as Model;
use app\plugins\Test\dao\TestDao as Dao;
use think\exception\ValidateException;
use think\facade\Db;

use think\facade\Env;

/**
 * 示例服务层
 * Class TestServices
 * @package app\plugins\Test\services
 * @version 1.0
 * @description
 */
class TestServices
{

    public mixed $timer = 0;
    private Model $Model; // 模型
    private mixed $test_data ; // 测试数据

    public function __construct(Model $model , Dao $dao )
    {
        // 初始化统一请求时间
        $this->timer            = $_SERVER['REQUEST_TIME'] ?? time();
        $this->model            = $model;
        $this->dao              = $dao;
    }

    /**
     * 设置数据
     * @param $where
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setTest($where = [])
    {
        $this->test_data = $this->model->where($where)->find();
        return $this;
    }

    /**
     * 获取数据
     * @param $key
     * @return Model|array|mixed|\think\Model|null
     */
    public function getTest($key = null){
        if($key !== null){
            return $this->test_data[$key] ?? null;
        }
        return $this->test_data;
    }

    // 获取ID
    public function getTestId()
    {
        return $this->test_data['id'] ?? 0;
    }

    // 获取名称
    public function getTestName()
    {
        return $this->test_data['name'] ?? 'none';
    }

    /**
     * 构建各种联表等复杂语句列表
     * @param $param array  查询参数
     * @param $user array 用户
     * @param bool $buildPower 是否构建数据权限
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function lists($param, $user = [], $buildPower = true)
    {
        // 获取请求中的过滤条件和排序方式
        $filters    = $param['filters']    ?? [];
        $sort       = $param['sort']       ?? ['id' => 'desc'];

        // 初始化查询对象并 构建查询条件和排序方式
        $query = PluginQueryBuilder::buildQuery($this->dao::buildInitSql(), $filters, $sort);

        //如果分组
        //$query->group('log.test_id');

        // 构建数据权限范围
        //if ($buildPower) {
            //$query = PluginQueryBuilder::buildPower($query, $user);
        //}

        // 执行查询并分页
        $list = $query->paginate($param['limit'] ?? $param['page_size'] ?? $param['pageSize'] ?? 20, false)->each(function ($item) {
            //没走模型，所以处理下时间戳
            //$item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            return $item;
        });

        return ['list' => $list->items(), 'total' => $list->total()];
    }

    /**
     * 通过配置数组形式 构建查询参数
     * @param $params
     * @param $user
     * @param $buildPower
     * @return array
     */
    public function listsByDao($params = [] , $user = [] , $buildPower = true ){
        $list = $this->dao->lists($params , $user , $buildPower , $params['sort'] ?? ['id' => 'desc']);
        return ['list' => $list->items() ?? [], 'total' => $list->total() ?? 0 ];
    }


    /**
     * 导出
     * @param $list array
     * @return void|bool
     * @throws \think\db\exception\DbException
     */
    public function export($list  ){

        if(!empty($list)){
//            $spreadsheet = new Spreadsheet();
//            $sheet       = $spreadsheet->getActiveSheet();
//            $sheet->setCellValue([1, 1], '编号');
//            $sheet->setCellValue([2, 1], '名称');
//
//
//            $h = 2;
//            foreach ($list as $v) {
//                $sheet->setCellValue([1, $h], $v['id']);
//                $sheet->setCellValue([2, $h], $v['name']  ?? '-');
//                $h++;
//            }

//            $writer = new Xlsx($spreadsheet);
//            $file   = '记录'.date('YmdHis',$this->timer) . '.xlsx';
//            ob_end_clean();
//            header('Content-Type: application/vnd.ms-excel');
//            header('Access-Control-Expose-Headers:Content-Disposition');
//            header('Content-Disposition: attachment;filename=' . $file);
//            header('Cache-Control: max-age=0');
//            $writer->save('php://output');
//            $spreadsheet->disconnectWorksheets();
        }
        return true;
    }

    /**
     * 通过反射获取某些类所有常量
     * 为什么这么写....因为项目有些文件配置了 各种日志类私有变量，方法,等，这样能快速拿到数据写入日志
     * @param $Constant
     * @return array
     * @throws \ReflectionException
     */
    public static function getAllConstants($Constant)
    {
        $reflector = new \ReflectionClass($Constant);
        return $reflector->getConstants();
    }
}
