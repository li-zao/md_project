<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
    	'demo' 		=> 'app\command\Demo',
    	'scan' 		=> 'app\command\ScanEml',
    	'import'	=> 'app\command\ImportEml',
    	'pull'		=> 'app\command\pullEml',
    ],
];
