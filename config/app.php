<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    'app_map'          => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],

    // 异常页面的模板文件
    'exception_tmpl'   => env('local_env') ? app()->getThinkPath() . 'tpl/think_exception.tpl' : app()->getConfigPath() .'tpl/product_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'    => 'Internal error! Please try again later.',
    // 显示错误信息
    'show_error_msg'   => false,
    // 自定义日志文件根目录
    'log_server_root_path' => app()->getRuntimePath() . 'log',
];
