<?php

//
//go(function () {
//    $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
//
//    if ($client->connect('127.0.0.1', '9509', 0.1)) {
//        $client->send("hello world\n");
//        echo $client->recv() . PHP_EOL;
//        //关闭连接
//        $client->close();
//
//    } else {
//        echo "connect error" . PHP_EOL;
//    }
//});


go(function () {
    $client = new Swoole\Coroutine\Http\Client('127.0.0.1', 9509);
    $client->setHeaders([
        'Host' => "localhost",
        "User-Agent" => 'Chrome/49.0.2587.3',
        'Accept' => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $client->push('ddd');
});
