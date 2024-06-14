<?php
// 应用公共文件

use think\facade\Lang;

if (!function_exists('__')) {

    /**
     * 语言翻译
     * @param string $name 被翻译字符
     * @param array  $vars 替换字符数组
     * @param string $lang 翻译语言
     * @return mixed
     */
    function __(string $name, array $vars = [], string $lang = ''): mixed
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        return Lang::get($name, $vars, $lang);
    }
}