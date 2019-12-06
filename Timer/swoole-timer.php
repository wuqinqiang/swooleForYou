<?php


//demo

$num=1;
\Swoole\Timer::tick(2000,function () use (&$num){

    echo ++$num.PHP_EOL;

    echo date('Y-m-d H:i:s'). ' 每隔两秒我会执行一次 '.PHP_EOL;
});



//demo
//请求一个外部接口 超时失败,重试机制 简易版

$url = 'http://xx.test';
$excel = 0;
$timer = \Swoole\Timer::tick(1000 * 10, function ($timer_id) use ($url, &$excel) {
    ++$excel;
    echo "请求接口中" . PHP_EOL;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    $post_data = array(
        "title" => "1290800466",
        "content" => "3424243243"
    );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $data = curl_exec($curl);
    curl_close($curl);
    if ($data) {
        echo '请求成功'.PHP_EOL;
        \Swoole\Timer::clear($timer_id);
    } else {
        if ($excel > 5) {
            echo '请求失败已达5次,停止请求接口' . PHP_EOL;
            \Swoole\Timer::clear($timer_id);
        }else{
            echo "请求失败,10秒之后重试".PHP_EOL;
        }
    }

});



//demo
$num = 10;
$timer = \Swoole\Timer::tick(2000, function () use (&$num) {
    echo ++$num . PHP_EOL;
    \Swoole\Timer::after(5000, function () use (&$num) {
        $num -= 2;

        echo date('Y-m-d H:i:s') . ' 时间管理 ' . PHP_EOL;

        echo $num . PHP_EOL;
    });
});


\Swoole\Timer::after(8000, function () use ($timer) {
    \Swoole\Timer::clear($timer);
});





//demo
$num=1;
$timer1=\Swoole\Timer::tick(2000,function () use ($num){
    echo '每隔两秒我会执行一次'.PHP_EOL;
});


var_dump(\Swoole\Timer::info(1));
