<?php

$server = new \Swoole\Server('127.0.0.1', '9509');

$server->on('receive', function ($ser, $fd, $from_id, $data) {
    $ser->send($fd, 'Swoole ' . $data);
    $ser->close($from_id);
});


$server->start();

