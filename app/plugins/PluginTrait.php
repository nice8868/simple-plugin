<?php
namespace app\plugins;


use app\Request;
use think\db\Query;

use think\exception\ValidateException;
use think\facade\Env;
use Throwable;


/**
 * 插件后台基础控制器
 * @author jokers
 * @date 2020-05-29
 * 后台控制器trait类 php多继承
 * 若需修改此类方法：请复制方法至对应控制器后进行重写
 */
trait PluginTrait
{
    /**
     * 无需登录的方法，访问本控制器的此方法，无需管理员登录
     * @var array
     */
    //protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected array $noNeedPermission = [];

    /**
     * 新增/编辑时，对前端发送的字段进行排除（忽略不入库）
     * @var array|string
     */
    protected array|string $preExcludeFields = [];



    /**
     * 模型类实例
     * @var object
     * @phpstan-var Model
     */
    protected object $model;

    /**
     * 权重字段
     * @var string
     */
    protected string $weighField = 'weigh';

    /**
     * 默认排序
     * @var string|array
     */
    protected string|array $defaultSortField = 'id,desc';

    /**
     * 表格拖拽排序时,两个权重相等则自动重新整理
     * 注意：只有在表格拖拽排序
     * null=取默认值,false=关,true=开
     * @var null|bool
     */
    protected null|bool $autoSortEqWeight = null;

    /**
     * 快速搜索字段
     * @var string|array
     */
    protected string|array $quickSearchField = 'id';

    /**
     * 是否开启模型验证
     * @var bool
     */
    protected $modelValidate = true;


    /**
     * 是否开启模型场景验证
     * @var bool
     */
    protected $modelSceneValidate = false;

    /**
     * 模型验证场景名
     * @var bool |string
     */
    protected $sceneValidate = false ;

    /**
     * 关联查询方法名，方法应定义在模型中
     * @var array
     */
    protected array $withJoinTable = [];

    /**
     * 关联查询JOIN方式
     * @var string
     */
    protected string $withJoinType = 'LEFT';

    /**
     * 开启数据限制
     * false=关闭
     * personal=仅限个人
     * allAuth=拥有某管理员所有的权限时
     * allAuthAndOthers=拥有某管理员所有的权限并且还有其他权限时
     * parent=上级分组中的管理员可查
     * 指定分组中的管理员可查，比如 $dataLimit = 2;
     * 启用请确保数据表内存在 admin_id 字段，可以查询/编辑数据的管理员为admin_id对应的管理员+数据限制所表示的管理员们
     * @var bool|string|int
     */
    protected $dataLimit = false;

    /**
     * 数据限制字段
     * @var string
     */
    protected $dataLimitField = 'admin_id';

    /**
     * 数据限制开启时自动填充字段值为当前管理员id
     * @var bool
     */
    protected $dataLimitFieldAutoFill = true;

    /**
     * 查看请求返回的主表字段控制
     * @var string|array
     */
    protected string|array $indexField = ['*'];

    /**
     * 查看
     * @throws Throwable
     */
    public function index(): void
    {


        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->field($this->indexField)
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);



        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),

        ]);
    }

    /**
     * 添加
     * @author jokers
     * @date 2024-05-30
     * @param array $data
     * @return void
     */
    public function save(): void
    {
        if ($this->request->isPost()) {

            $data = $this->request->post();
            if (!$data) {
                $this->error(__('参数错误', ['']));
            }
            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));

                    if (class_exists($validate)) {
                        $validate = new $validate;

                        $validate->scene( isset($this->sceneValidate) && $this->sceneValidate ?$this->sceneValidate : 'save');
                        $validate->check($data);
                    }
                }
                $data[$this->dataLimitField] = $this->auth->id ?? 0;
                $result = $this->model->save($data);
                $this->model->commit();

            } catch (Throwable $e) {
                $this->model->rollback();
                $this->success($e->getMessage());
            }
            if ($result !== false) {
                //return json
                $this->success(__('添加成功'));
            } else {
                $this->error(__('添加失败'));
            }
        }

        $this->error(__('请求错误'));
    }
    /**
     * 详情
     * @author jokers
     * @date 2024-05-30
     * @param integer $id
     * @return void
     * @throws Throwable
     */
    public function read( ): void
    {
        if (!$this->request->isPost() && !$this->request->isGet()) {
            throw new ValidateException(__('请求方式错误', ['']));
        }
        $pk  = $this->model->getPk();

        $id  = $this->request->param($pk);
        $where[] = [$pk, '=', $id];
        $where[] = ['delete_at' , '=', 0];
        $row = $this->model->where($where)->find();

        if (!$row) {
            throw new ValidateException(__('目标数据不存在', ['']));

        }



        $this->success('', [
            'row' => $row
        ]);
    }
    /**
     * 编辑
     * @author jokers
     * @date 2024-05-30
     * @param integer $id
     * @param string $key
     * @param string $val
     * @throws Throwable
     */
    public function update( ): void
    {
        $pk  = $this->model->getPk();

        $id  = $this->request->param($pk);

        $row = $this->model->find($id);

        if (!$row) {
            throw new ValidateException(__('目标数据不存在', ['']));
        }

        if ($this->request->isPost() || $this->request->isPut()) {
            $data = $this->request->post();
            if (!$data) {
                throw new ValidateException(__('参数错误', ['']));

            }

            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));

                    if (class_exists($validate)) {
                        $validate = new $validate;
                        $validate->scene(isset($this->sceneValidate) && $this->sceneValidate ?$this->sceneValidate : 'update');
                        $data[$pk] = $row[$pk];
                        $validate->check($data);
                    }
                }
                $result = $row->save($data);


                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('修改成功'));
            } else {
                $this->error(__('修改失败'));
            }
        }

        $this->success('', [
            'row' => $row
        ]);
    }
    /**
     * 批量编辑
     * @author jokers
     * @date 2024-05-30
     * @param array $ids
     * @throws Throwable
     */
    public function edit( ): void
    {
        $pk  = $this->model->getPk();
        $id  = $this->request->param($pk);
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('目标数据不存在'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('参数错误', ['']));
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));

                    if (class_exists($validate)) {
                        $validate = new $validate;
                        $validate->scene(isset($this->sceneValidate) && $this->sceneValidate ?$this->sceneValidate :'edit');
                        $validate->check($data);
                    }
                }
                // 模型验证
                $result = $row->save($data);
                $this->model->commit();

            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('修改成功'));
            } else {
                $this->error(__('修改失败'));
            }
        }

        $this->success('', [
            'row' => $row
        ]);
    }
    /**
     * 删除单条
     * @author jokers
     * @date 2024-05-30
     * @throws Throwable
     */
    public function delete( )
    {
        if (!$this->request->isDelete() || $this->request->isPost() ) {
            $this->error(__('请求方式错误'));
        }

        $pk  = $this->model->getPk();
        $id  = $this->request->param($pk);

        $pk      = $this->model->getPk();
        $where[] = [$pk, '=', $id];
        $where[] = ['delete_at', '=', 0];
        $row = $this->model->where($where)->find($id);
        if (!$row) {
            $this->error(__('目标数据不存在'));
        }
        $timer = time();

        $result = false;
        $this->model->startTrans();
        try {
            // 模型验证
            $result = $row->save(['delete_at' => $timer]);
            $this->model->commit();

        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($result !== false) {

            $this->success(__('删除成功'));
        } else {
            $this->error(__('删除失败'));
        }

        $this->error(__('删除失败'));
    }
    /**
     * 批量删除 真是删除
     * @author jokers
     * @date 2024-05-30
     * @param array $ids
     * @throws Throwable
     */
    public function del($ids = [] ): void
    {
        if (!$this->request->isDelete() && !$ids ) {
            $this->error(__('参数错误'));
        }

        $where             = [];


        $pk      = $this->model->getPk();
        $where[] = [$pk, 'in', $ids];

        $count = 0;
        $data  = $this->model->where($where)->select();
        $this->model->startTrans();
        try {
            foreach ($data as $v) {
                $count += $v->delete();
            }

            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(__('删除成功'));
        }
        $this->error(__('修改失败'));
    }


    /**
     * 加载为select(远程下拉选择框)数据，默认还是走$this->index()方法
     * 必要时请在对应控制器类中重写
     */
    public function select(): void
    {

    }

    /**
     * 构建查询参数
     * @throws Throwable
     */
    public function queryBuilder(): array
    {
        if (empty($this->model)) {
            return [];
        }
        // 快速搜索
        $pk           = $this->model->getPk();
        // 快速搜索
        $quickSearch  = $this->request->get("quickSearch/s", '');
        // 分页
        $limit        = $this->request->get("limit/d", 10);
        // 排序
        $order        = $this->request->get("order/s", '');
        // 搜索
        $search       = $this->request->get("search/a", []);
        // 初始化key
        $initKey      = $this->request->get("initKey/s", $pk);
        // 初始化val
        $initValue    = $this->request->get("initValue", '');
        // 初始化  Operator
        $initOperator = $this->request->get("initOperator/s", 'in');
        // 查询条件
        $where              = [];
        // 别名
        $modelTable         = strtolower($this->model->getTable());

        // 别名
        $alias[$modelTable] = parse_name(basename(str_replace('\\', '/', get_class($this->model))));

        // 别名
        $mainTableAlias     = $alias[$modelTable] . '.';


        // 快速搜索
        if ($quickSearch) {
            // 快速搜索字段
            $quickSearchArr = is_array($this->quickSearchField) ? $this->quickSearchField : explode(',', $this->quickSearchField);

            // 快速搜索字段
            foreach ($quickSearchArr as $k => $v) {
                // 快速搜索字段
                $quickSearchArr[$k] = str_contains($v, '.') ? $v : $mainTableAlias . $v;
            }

            // 快速搜索
            $where[] = [implode("|", $quickSearchArr), "LIKE", '%' . str_replace('%', '\%', $quickSearch) . '%'];

        }

        // 初始化
        if ($initValue) {
            // 初始化
            $where[] = [$initKey, $initOperator, $initValue];
            // 初始化
            $limit   = 999999;
        }

        // 排序
        if ($order) {
            // 排序
            $order = explode(',', $order);
            if (!empty($order[0]) && !empty($order[1]) && ($order[1] == 'asc' || $order[1] == 'desc')) {
                $order = [$order[0] => $order[1]];
            }
        } else {
            if (is_array($this->defaultSortField)) {
                $order = $this->defaultSortField;
            } else {
                $order = explode(',', $this->defaultSortField);
                if (!empty($order[0]) && !empty($order[1])) {
                    $order = [$order[0] => $order[1]];
                } else {
                    $order = [$pk => 'desc'];
                }
            }
        }



        // 通用搜索组装
        foreach ($search as $field) {
            if (!is_array($field) || !isset($field['operator']) || !isset($field['field']) || !isset($field['val'])) {
                continue;
            }

            $field['operator'] = $this->getOperatorByAlias($field['operator']);

            $fieldName = str_contains($field['field'], '.') ? $field['field'] : $mainTableAlias . $field['field'];

            // 日期时间
            if (isset($field['render']) && $field['render'] == 'datetime') {
                if ($field['operator'] == 'RANGE') {
                    $datetimeArr = explode(',', $field['val']);
                    if (!isset($datetimeArr[1])) {
                        continue;
                    }
                    $datetimeArr = array_filter(array_map("strtotime", $datetimeArr));
                    $where[]     = [$fieldName, str_replace('RANGE', 'BETWEEN', $field['operator']), $datetimeArr];
                    continue;
                }
                $where[] = [$fieldName, '=', strtotime($field['val'])];
                continue;
            }

            // 范围查询
            if ($field['operator'] == 'RANGE' || $field['operator'] == 'NOT RANGE') {
                $arr = explode(',', $field['val']);
                // 重新确定操作符
                if (!isset($arr[0]) || $arr[0] === '') {
                    $operator = $field['operator'] == 'RANGE' ? '<=' : '>';
                    $arr      = $arr[1];
                } elseif (!isset($arr[1]) || $arr[1] === '') {
                    $operator = $field['operator'] == 'RANGE' ? '>=' : '<';
                    $arr      = $arr[0];
                } else {
                    $operator = str_replace('RANGE', 'BETWEEN', $field['operator']);
                }
                $where[] = [$fieldName, $operator, $arr];
                continue;
            }

            switch ($field['operator']) {
                case '=':
                case '<>':
                    $where[] = [$fieldName, $field['operator'], (string)$field['val']];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                    $where[] = [$fieldName, $field['operator'], '%' . str_replace('%', '\%', $field['val']) . '%'];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$fieldName, $field['operator'], intval($field['val'])];
                    break;
                case 'FIND_IN_SET':
                    if (is_array($field['val'])) {
                        foreach ($field['val'] as $val) {
                            $where[] = [$fieldName, 'find in set', $val];
                        }
                    } else {
                        $where[] = [$fieldName, 'find in set', $field['val']];
                    }
                    break;
                case 'IN':
                case 'NOT IN':
                    $where[] = [$fieldName, $field['operator'], is_array($field['val']) ? $field['val'] : explode(',', $field['val'])];
                    break;
                case 'NULL':
                case 'NOT NULL':
                    $where[] = [$fieldName, strtolower($field['operator']), ''];
                    break;
            }
        }

        // 数据权限
//        $dataLimitAdminIds = $this->getDataLimitAdminIds();
//        if ($dataLimitAdminIds) {
//            $where[] = [$mainTableAlias . $this->dataLimitField, 'in', $dataLimitAdminIds];
//        }

        return [$where, $alias, $limit, $order];
    }

    /**
     * 设置字段
     * @param integer $id
     * @param string $key
     * @param string $val
     * @author jokers
     * @date 2020-05-30
     * @throws Throwable
     * @return void
     */
    public function setField(){

        $pk  = $this->model->getPk();
        $id  = $this->request->param($pk);
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('目标数据不存在'));
        }
        $result = false;
        $this->model->startTrans();
        try {

            // 模型验证
            $result = $row->save([$this->request->param('key')=>$this->request->param('val')]);

            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($result !== false) {
            $this->success(__('修改成功'));
        }
        $this->error(__('修改失败'));
    }

    /**
     * 从别名获取原始的逻辑运算符
     * @param string $operator 逻辑运算符别名
     * @return string 原始的逻辑运算符，无别名则原样返回
     */
    protected function getOperatorByAlias(string $operator): string
    {
        $alias = [
            'ne'  => '<>',
            'eq'  => '=',
            'gt'  => '>',
            'egt' => '>=',
            'lt'  => '<',
            'elt' => '<=',
        ];

        return $alias[$operator] ?? $operator;
    }

    /**
     * 数据权限控制-获取有权限访问的管理员Ids
     * @throws Throwable
     */
  //  protected function getDataLimitAdminIds(): array
    //{
//        if (!$this->dataLimit || $this->auth->isSuperAdmin()) {
//            return [];
//        }
//        $adminIds = [];
//        if ($this->dataLimit == 'parent') {
//            // 取得当前管理员的下级分组们
//            $parentGroups = $this->auth->getAdminChildGroups();
//            if ($parentGroups) {
//                // 取得分组内的所有管理员
//                $adminIds = $this->auth->getGroupAdmins($parentGroups);
//            }
//        } elseif (is_numeric($this->dataLimit) && $this->dataLimit > 0) {
//            // 在组内，可查看所有，不在组内，可查看自己的
//            $adminIds = $this->auth->getGroupAdmins([$this->dataLimit]);
//            return in_array($this->auth->id, $adminIds) ? [] : [$this->auth->id];
//        } elseif ($this->dataLimit == 'allAuth' || $this->dataLimit == 'allAuthAndOthers') {
//            // 取得拥有他所有权限的分组
//            $allAuthGroups = $this->auth->getAllAuthGroups($this->dataLimit);
//            // 取得分组内的所有管理员
//            $adminIds = $this->auth->getGroupAdmins($allAuthGroups);
//        }
//        $adminIds[] = $this->auth->id;
        //return array_unique($adminIds) ;
    //}

    /**
     * 生成查询条件和排序方式
     *
     * @param Query $query 查询对象
     * @param array $filters 过滤条件
     * @param array $sort 排序条件
     * @return Query
     */
    public static function build(Query $query, array $filters, array $sort)
    {
        // 处理过滤条件
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                // 根据具体需求处理不同类型的过滤条件
                if (is_array($value)) {
                    // 数组类型的过滤条件，使用 'IN' 查询
                    $query->whereIn($field, $value);
                } else {
                    // 默认使用 '=' 查询
                    $query->where($field, $value);
                }
            }
        }

        // 处理排序条件
        foreach ($sort as $field => $direction) {
            $query->order($field, $direction);
        }

        return $query;
    }

    /**
     * 获取模型表名称去除前缀并转换为大写
     *
     * @param $model
     * @return string
     */
    public function getConstantsId($model = false)
    {
        $prefix = Env::get('database.prefix');
        $table  = (!$model ) ? $this->model->getTable() : $model->getTable();
        $tableWithoutPrefix = strtoupper(str_replace($prefix, '', $table));

        // 通过反射获取所有常量
       // return self::getAllConstants(Constant::class)[$tableWithoutPrefix] ?? self::getAllConstants(Constant::class)['PLUGIN_UNKNOWN'] ?? 0;
    }

    /**
     * 通过反射获取所有常量
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