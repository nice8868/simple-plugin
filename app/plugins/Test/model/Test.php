<?php
namespace app\plugins\Test\model;
use think\Model;
/**
 *  日志
 * @author jokers
 * @date 2020-05-29
 */
class Test extends Model
{
    protected $table = 'plugin_test';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $deleteTime = 'delete_at';

    protected $type = [
        'create_at' => 'timestamp:Y-m-d H:i:s',
        'update_at' => 'timestamp:Y-m-d H:i:s',
    ];
    protected $json = ['files'];
    protected $readonly = ['id'];

    protected $hidden = ['delete_at'];


    public function getStatusText($value)
    {
        $status = [ 0=>'待审核',1=>'已通过',2=>'未通过' , '3'=>'未登记'];
        return $status[$value];
    }
    /**
     * 远程1对1
     * @return \think\model\relation\HasOneThrough
     */
//    public function orgs()
//    {
//        return $this->hasOneThrough(\app\plugins\Sub\model\Organization::class , \app\plugins\Test\model\Test::class,'id','id','test_id','orgs_id') ;
//    }
}
