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
        if ($info->type == 3) {
            $task_id = \Swoole\Timer::tick(200, function () use ($info, $frame) {
                return $this->server->task(['message' => $frame->fd . ' 说' . $info->message]);
            });
        } else {
            $task_id = $this->server->task(['message' => $frame->fd . ' 说' . $info->message]);
        }
        echo "任务id:{$task_id}投递成功!" . PHP_EOL;
    }

    public function onTask($server, $task_id, $from_id, $data)
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($fd)) {
                $this->server->push($fd, json_encode(['message' => $data['message']]));
            }
        }
        $this->server->finish($data);
    }

    public function onFinish($server, $task_id, $data)
    {
        echo '任务: ' . $task_id . ' 执行完毕' . PHP_EOL;
    }

    public function onClose($server, $fd)
    {
        $this->server->task(['message' => '路人: ' . $fd . ' 下线了' . PHP_EOL, 'type' => 4]);
    }

}

$demo = new WebsocketServer();
