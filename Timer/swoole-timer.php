<?php

//
//$num=1;
//\Swoole\Timer::tick(2000,function () use (&$num){
//
//    echo ++$num.PHP_EOL;
//
//  //  echo date('Y-m-d H:i:s'). ' 每隔两秒我会执行一次 '.PHP_EOL;
//});


$num = 10;
$timer = \Swoole\Timer::tick(2000, function () use (&$num) {
    echo ++$num . PHP_EOL;
    \Swoole\Timer::after(5000, function () use (&$num) {
        $num -= 2;

          echo date('Y-m-d H:i:s'). ' 时间管理 '.PHP_EOL;

        echo $num.PHP_EOL;
    });
});

\Swoole\Timer::after(8000, function () use ($timer) {
    \Swoole\Timer::clear($timer);
});






//$num=1;
//$timer1=\Swoole\Timer::tick(2000,function () use ($num){
//    echo '每隔两秒我会执行一次'.PHP_EOL;
//});


//var_dump(\Swoole\Timer::info(1));
