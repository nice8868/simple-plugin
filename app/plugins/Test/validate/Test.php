<?php
namespace app\plugins\Test\validate;

use think\Validate;

/**
 *  验证层
 */
class Test extends Validate
{
    protected $failException = true;

    protected $rule =   [
        'name|名称'   => 'require',
    ];

    protected $scene = [
        'save'  =>  ['name'],
    ];

}