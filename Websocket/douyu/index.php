<?php

//这个项目暂时搁浅,当做后面的任务完成

require __DIR__.'/vendor/autoload.php';

use App\TestCmd;
use Symfony\Component\Console\Application;

$app=new Application();

//$app->register('test1')
//    ->addArgument('name',\Symfony\Component\Console\Input\InputArgument::OPTIONAL,'你输入的名称')
//    ->setCode(function (\Symfony\Component\Console\Input\InputInterface $input,\Symfony\Component\Console\Output\Output $output){
//       $name=$input->getArgument('name');
//       $message='欢饮'.$name;
//       $output->writeln('你好啊');
//    });


$app->add(new TestCmd());

$app->run();