<?php

$num=1;
$timer1=\Swoole\Timer::tick(2000,function () use ($num){
    echo 'after 2000ms'.PHP_EOL;
});

$timer=\Swoole\Timer::after(5000,function () use ($timer1){
    \Swoole\Timer::clear($timer1);
});

//var_dump(\Swoole\Timer::info(1));
