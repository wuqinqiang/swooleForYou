## :pencil2:swoole 中异步任务处理
  
  **php本身的代码执行是同步阻塞的,所以在一些对于发邮件或者处理订单这一类的耗时业务中,如果还是直接在当前进程中阻塞运行,那么将会导致服务器的响应越来越慢,通常情况下,我们会借助队列这种第三方的服务来解决此类问题.swoole 自然也对异步任务提供了支持**

**在 swoole 中,投递一个异步的任务到 task_worker 中,这个函数是非阻塞的,worker 进程可以继续处理新的请求,使用 task 功能必须先配置 task_worker_num,表示的是开启多少个task 进程**


**j接下来,先来看我们服务器的简易代码部分**
## :pencil2:tick 

```php
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

$ser->close('close',function (\Swoole\Server $server){

});

$ser->start();

```


**首先我们先开启了一个服务,并监听9504这个端口,其次我们设置了 worker_num,表示开启3个 worker 进程,接着开启4个 task 进程.接下来设置一些回调函数,值得注意的是,使用 task 必须用服务设置 onTask 和 onFinish回调,否则服务启动的时候会报错**

**$server->task 就是投递任务到 task 的操作,参数必须是可序列化的 php 变量.如果投递成功,那么此函数返回的将是整数的 task_id,表示当前任务的 id**


**我们再来思考一些,根据上面的设置,服务开启的时候,会产生多少个进程.**

**答案是9个**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/task-1.png" >

**图上展示的,2209是 master 进程, 2210是 manager 进程,剩下的就是 task 进程和 worker 进程和了,你可以看到他们的父进程id 都是2210,在swoole 文档中有过介绍,manager 进程负责创建,回收以及管理 task 和 master 进程,从官方文档盗的图**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/swoole-1.png" >



**接下来我们来看客户端代码**

```php

    <?php
    
    
    go(function () {
        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        // 尝试与指定 TCP 服务端建立连接
        if ($client->connect("127.0.0.1", 9504, 0.5)) {
            // 建立连接后发送内容
            $client->send("hello world\n");
            // 打印接收到的消息
            echo $client->recv().PHP_EOL;
    
            sleep(2);
            // 关闭连接
            $client->close();
        } else {
            echo "connect failed.";
        }
    });
```

**客户端通过协程来实现异步的通信**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/task-2.png" >


### 联系
<a href="https://github.com/wuqinqiang/">
公众号
​    <img src="https://github.com/wuqinqiang/Lettcode-php/blob/master/qrcode_for_gh_c194f9d4cdb1_430.jpg" width="200px" height="200px">
个人微信  
​    <img src="https://github.com/wuqinqiang/Lettcode-php/blob/master/images/Wechat.png" width="200px" height="200px">
****
 