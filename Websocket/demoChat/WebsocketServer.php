<?php

class WebsocketServer
{
    protected $server;

    public function __construct()
    {
        $this->server = new \Swoole\WebSocket\Server('swoolefor.test', 9508);
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


    public function onOpen($server, $reuqest)
    {
        $this->server->task(['message' => "路人: " . $reuqest->fd . '上线了', 'type' => 1]);
    }

    public function onMessage($server, $frame)
    {
        $info = json_decode($frame->data);
        $task_id = $this->server->task(['message' => $frame->fd . ' 说' . $info->message, 'type' => $info->type]);
        echo "任务id:{$task_id}投递成功!" . PHP_EOL;
    }

    public function onTask($server, $task_id, $from_id, $data)
    {
        if ($data['type'] == 3) {
            \Swoole\Timer::tick(1000, function () use ($data) {
                $this->sendAll($data);
            });
        } else {
            $this->sendAll($data);

        }
    }


    public function sendAll($data)
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($fd)) {
                $this->server->push($fd, json_encode(['message' => $data['message']]));
            }
        }
        $this->server->finish($data);
    }

    public function onFinish( $server, $task_id, $data)
    {
        echo '任务: ' . $task_id . ' 执行完毕' . PHP_EOL;
    }

    public function onClose($server, $fd)
    {
        $this->server->task(['message' => '路人: ' . $fd . ' 下线了' . PHP_EOL, 'type' => 4]);
    }

}

$demo = new WebsocketServer();
