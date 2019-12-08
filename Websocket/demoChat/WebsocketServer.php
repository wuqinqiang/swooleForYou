<?php

class WebsocketServer
{
    protected $server;

    public function __construct()
    {
        $this->server = new \Swoole\WebSocket\Server('swoolefor', 9508);
        $this->server->set([
            'worker_num' => 4,
            'task_worker_num' => 3,
            'max_request' => 5,

        ]);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->start();
    }


    public function onOpen(\Swoole\Server $server, $reuqest)
    {
        echo "路人: " . $reuqest->fd . ' 上线了' . PHP_EOL;
        $this->server->push($reuqest->fd, '你已成功连接');
    }

    public function onMessage(\Swoole\Server $server, $frame)
    {
        echo '路人: ' . $frame->fd . "发送消息{$frame->data}" . PHP_EOL;
        $task_id = $this->server->task(json_decode($frame->data));
        echo "任务投递成功!任务id: " . $task_id;
    }

    public function onTask(\Swoole\Server $server, $task_id, $from_id, $data)
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($from_id)) {
                $this->server->push($fd, json_encode($data));
            }
        }
        $this->server->finish($data);
    }

    public function onFinish(\Swoole\Server $server, $task_id, $data)
    {
        echo '任务: ' . $task_id . ' 执行完毕'.PHP_EOL;
    }

    public function onClose(\Swoole\Server $server,$fd)
    {
        echo '游客: '.$fd.' 下线了'.PHP_EOL;
    }

}

$demo=new WebsocketServer();
