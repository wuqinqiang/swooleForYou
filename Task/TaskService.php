<?php


$ser = new \Swoole\Server('127.0.0.1', 9504);
$ser->set([
    'worker_num' => 3,
    'task_worker_num' => 4,
]);

$ser->on('connect', function (\Swoole\Server $server, $fd) {
    echo '一个新的连接: ' . $fd . PHP_EOL;
});

$ser->on('Receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
    echo '接收到新的数据' . PHP_EOL;
    $data=[
      'meeage'=>'sss',
      'code'=>200,
    ];
    $server->task(json_encode($data));
    $server->send($fd, json_encode($data));
    echo '投递任务成功' . PHP_EOL;
});

$ser->on('task', function (\Swoole\Server $server, $task_id, $data) {
    echo 'task任务id: ' . $task_id . PHP_EOL;
    sleep(2);
    $server->finish($data);
});

$ser->on('finish', function (\Swoole\Server $server, $task_id) {
    echo 'task任务id: ' . $task_id . ' 处理完毕';

});


$ser->start();
