<?php

$server = new \Swoole\Server('luochat.test', 9509,SWOOLE_BASE);


$server->on('receive', function ($ser, $fd, $from_id, $data) {
    $ser->send($fd, 'Swoole ' . $data);
});

$server->on('Request', function ($request, $response) {
    echo "hava Request".PHP_EOL;
    $mysql = new \Swoole\Coroutine\MySQL();
    $res = $mysql->connect([
        'host' => '127.0.0.1',
        'username' => 'homestead',
        'password' => 'secret',
        'database' => 't',
    ]);
    if (false === $res) {
        $response->end("mysql connect error\n");
    }

    $ret = $mysql->query('show tables', 2);
    $response->end("swoole response is ok, result=" . var_export($ret, true));

});

$server->start();