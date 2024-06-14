<?php
namespace app\plugins;

use think\db\Query;
use think\exception\ValidateException;

/**
 * 插件 通用构建生成复杂 查询条件和排序方式
 * @author jokers
 * @date 2020-05-29
 * @package app\plugins
 * @version 1.0
 //* @method static PluginQueryBuilder buildQuery(Query $query, array $filters, array $sort )
 //* @method static PluginQueryBuilder build(Query $query, array $filters, array $sort )
 //* @method static PluginQueryBuilder buildPower(Query $query, array $user , $keys = 'm.organization_id' )
 */
class PluginQueryBuilder
{

    // SQL注入过滤规则
    private static $getfilter = "/'|(and|or|having)\\b.+?(>|<|=|in|like|having)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?BSELECT|SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)/i";
    private static $postfilter = "/\\b(and|or|having)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b|having\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?BSELECT|SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)/i";
    private static $cookiefilter = "/\\b(and|or|having)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b|having\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?BSELECT|SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)/i";

    /**
     * 过滤输入，防止SQL注入和特殊字符
     *
     * @param mixed $value 输入值
     * @return mixed 过滤后的值
     */
    private static function sanitize($value)
    {
        try {
            if (is_array($value)) {
                return array_map([self::class, 'sanitize'], $value);
            }

            // 去除特殊字符，但允许 <, >, =, [], %, 和 ()
            $value = preg_replace('/[^\w\s\-_,.<>=\[\]%()]/u', '', $value);

            //$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            // SQL注入检测
            if (preg_match(self::$getfilter, $value) || preg_match(self::$postfilter, $value) || preg_match(self::$cookiefilter, $value)) {
                throw new \Exception('Invalid input detected');
            }
        }catch (\Throwable $e){
            throw new ValidateException($e->getMessage());
        }

        return $value;
    }


    /**
     * @param Query $query 查询对象
     * @param array $filters 过滤条件
     * @param array $sort 排序条件
     * @return Query 生成后的查询对象
     * @return Query
     * @author jokers
     * @date 2020-05-31
     * 插件生成复杂 查询条件和排序方式
     * GET /training_plan/index?filters[plan_name][operator]=like&filters[plan_name][value]=test&filters[mc_id][operator]=in&filters[mc_id][value]=1,2,3&sort[start_time]=desc&page=1&page_size=10
     *

     */
    public static function buildQuery(Query $query, array $filters, array $sort = [])
    {
        // 处理过滤条件
        foreach ($filters as $field => $condition) {
            if (!empty($condition)) {
                $operator   =  $condition['op'] ?? $condition['operator'] ?? '=';
                $value      = $condition['value'] ?? null;
                $sanitizedValue = self::sanitize($value);

                switch (strtolower($operator)) {
                    case 'like':
                        if($sanitizedValue != '') $query->where($field, 'LIKE', "%{$sanitizedValue}%");
                        break;
                    case 'in':
                        if($sanitizedValue != '') $query->whereIn($field, (array)$sanitizedValue);
                        break;
                    case 'between':
                    case 'range':
                     if($sanitizedValue != '') $query->whereBetween($field, $sanitizedValue);
                        break;
                    case 'between_time':
                        if($sanitizedValue != '') $query->whereBetweenTime($field, $sanitizedValue[0], $sanitizedValue[1]);
                        break;
                    case 'not_between':
                    case 'not_range':
                    if($sanitizedValue != '') $query->whereNotBetween($field, $sanitizedValue);
                        break;
                    case 'not_between_time':
                        if($sanitizedValue != '') $query->whereNotBetweenTime($field, $sanitizedValue[0], $sanitizedValue[1]);
                        break;
                    case 'not_in':
                        if($sanitizedValue != '') $query->whereNotIn($field, (array)$sanitizedValue);
                        break;
                    case 'not':
                        if($sanitizedValue != '') $query->where($field, '!=', $sanitizedValue);
                        break;
                    case 'or':
                        if($sanitizedValue != '') $query->whereOr($field, $sanitizedValue);
                        break;
                    case 'not_null':
                        if($sanitizedValue != '') $query->whereNotNull($field);
                        break;
                    case 'null':
                        if($sanitizedValue != '') $query->whereNull($field);
                        break;
                    case 'not_exists':
                        if($sanitizedValue != '') $query->whereNotExists($sanitizedValue);
                        break;
                    case 'find_in_set':
                    case 'find_in':
                    case 'in_set':
                        if($sanitizedValue != '') $query->whereFindInSet($field, $sanitizedValue);
                        break;
                    case 'eq':
                    case 'gt':
                    case 'egt':
                    case 'elt':
                    case 'lt': //
                    case 'ne': //
                        if($sanitizedValue != '') $query->where($field, self::getOperatorByAlias($operator), $sanitizedValue);
                        break;
                    case 'date_elt': // 小于等于
                    case 'date_lt': // 小于
                    case 'date_gt': //  大于
                    case 'date_egt': // 大于等于
                        if($sanitizedValue != '') $query->where($field, self::getOperatorByAlias(str_replace('date_' ,'',$operator)), strtotime($sanitizedValue));
                        break;
                    default:
                        //$query->where($field, $sanitizedValue);
                        break;
                }
            }
        }

        if(!empty($sort)){
            // 处理排序条件
            foreach ($sort as $fie => $direction) {
                $query->order($sort);
            }
        }
        return $query;
    }
    /**
     * 根据配置数组方式 构建查询参数格式
     * @param array $searchable 配置查询项
     * @param array $params 参数

     * @return array
     */
    public static function buildWhereParams( array $params  ,array $searchable ){
        $selectFields = [];

        if(!empty($params) && !empty($searchable)){
            foreach ($params as $key => $value) {
                if (isset($searchable[$key])) {
                    [$operator, $field] = $searchable[$key];
                    $field = $field ?? $key;
                    $selectFields[$field]['op'] = $operator;
                    $selectFields[$field]['value'] = $value;

                }
            }
        }
        return $selectFields;
    }
    /**
     * 插件复用 数据权限
     * @author jokers
     * @date 2020-05-29
     * @param Query $query 查询对象
     * @param array $user 用户,必须有id 和 orgs_id
     * @param string $keys 数据范围自动名啊
     * @return Query
     */
    public static function buildPower(Query $query, array $user , $keys = 'orgs_id' ){

        $wherePower = [];
        if(isset($user['id']) && isset($user['orgs_id'])){
            $power = 3;
            switch ($power) {
                case 1: //所有权限
                    break;
                case 2: //部门及以下
                    $wherePower[] = [$keys,'in', $power['orgs_id']];
                    break;
                case 3: //个人
                    $wherePower[] = [$keys,'=',$user['orgs_id']];
                    break;
            }
            if(!empty($wherePower)){
                $query->where($wherePower);
            }
        }

        return $query;
    }

    /**
     * 插件 生成快捷查询条件和排序方式
     * GET /training_plan/index?filters[plan_name]=test&filters[mc_id]=1&sort[start_time]=desc&page=1&page_size=10
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
     * 从别名获取原始的逻辑运算符
     * @param string $operator 逻辑运算符别名
     * @return string 原始的逻辑运算符，无别名则原样返回
     */
    protected static function getOperatorByAlias(string $operator): string
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
}
