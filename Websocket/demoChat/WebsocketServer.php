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
        $this->server->task(['message' => "路人: " . $reuqest->fd . '上线了']);
    }

    public function onMessage(\Swoole\Server $server, $frame)
    {
        $task_id = $this->server->task(['message' => $frame->fd . ' 说' . $frame->data]);
        echo "任务id:{$task_id}投递成功!".PHP_EOL;
    }

    public function onTask(\Swoole\Server $server, $task_id, $from_id, $data)
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($fd)) {
                $this->server->push($fd, json_encode(['message' => $data['message']]));
            }
        }
        $this->server->finish($data);
    }

    public function onFinish(\Swoole\Server $server, $task_id, $data)
    {
        echo '任务: ' . $task_id . ' 执行完毕' . PHP_EOL;
    }

    public function onClose(\Swoole\Server $server, $fd)
    {
        $this->server->task(['message' => '路人: ' . $fd . ' 下线了' . PHP_EOL]);
    }

}

$demo = new WebsocketServer();
