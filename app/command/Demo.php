<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\model\Mails;

class Demo extends Command
{   
    // 参考 https://www.kancloud.cn/manual/thinkphp6_0/1037651
    protected function configure()
    {
        // 指令配置
        $this->setName('Demo')
             ->addArgument('name', Argument::OPTIONAL, "your name")
             ->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
             ->setDescription('Say Hello');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        if ($input->getArgument('name')) {
            $name = trim($input->getArgument('name'));
        } else {
            $name = 'thinkphp';
        }

        if ($input->hasOption('city')) {
            $city = PHP_EOL . 'From ' . $input->getOption('city');
        } else {
            $city = '';
        }
        
        $output->writeln("Hello," . $name . '!' . $city);
    }
}
