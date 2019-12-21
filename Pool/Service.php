<?php
$workNumber = 5;

$pool = new \Swoole\Process\Pool($workNumber);

$pool->on('WorkerStart', function ($pool, $workerId) {
    echo "worker#{$workerId} is start" . PHP_EOL;

    $redis = new \Redis();
    $redis->pconnect('127.0.0.1', 6379);
    $key = 'key1';
    while (true) {
        $msg = $redis->brPop($key, 2);
        if ($msg == null) continue;

        var_dump($msg);
        echo "Process by Wokrer #{$workerId}".PHP_EOL;
    }
});

$pool->on('WorkerStop', function ($pool, $workerId) {
    echo "Worker{$workerId} is stop" . PHP_EOL;
});
$pool->start();

//客户端redis-cli 启动redis  LPUSH 推送数据