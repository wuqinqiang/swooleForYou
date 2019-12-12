## 谈谈Websocket

对于 `Websocket` 协议，这位老哥Dennis_Ritchie这篇文章就写的很好，[老司机带你用 PHP 实现 Websocket 协议](https://learnku.com/articles/36471)，文章不能只看，要动手实验，不然你哪知道别人是不是在坑你哈哈哈，其实学习是从模仿开始的，就像社区的课程，不得不说，质量很高，我今年入门  `Laravel` 的时候就是买的课程，本质上不也是一个模仿学习的过程吗，最后自己再进行一些扩展，简直完美。扯远了...... ,另外为了方便下文，对于 `Websocket` 我统称 `ws`。


`ws` 是 H5 规范中的一个部分,为 web 应用程序客户端和服务器端提供全双工的通信方式，它也是新的一种应用层协议。通常它的表现为: `ws://echo.websocket.org/?encoding=text HTTP/1.1` 可以看出除了最前面的协议名和 `HTTP` 不同，其他看起来就是一个url地址。

`ws`和`http` 之间的联系。

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/http.jpeg" >


`ws` 的目的是取代 `HTTP` 在双向通信场景下的使用。首先他们都位于OSI模型中的最高层:应用层。`ws` 借助 `HTTP` 完成连接，客户端的握手消息就是一个「普通的，带有Upgrade头的，HTTP Request消息」。对于 `HTTP` 来说，就是一个你问我答的模式: `Request/Response`,就算实现了 `HTTP` 的长连接，它的底层依旧是你问我答。只是保持住了长连接的一条线，是阻塞的 `I/O`，但是 `ws` 就不一样了，握手成功之后就是全双工的 TCP 通道，服务器端可以主动的发送消息给客户端。在 `ws` 出来之前，很多都是使用的轮训来实现实时交互一些业务场景。

### Websocket 和 HTTP 相同点和不同点

> 相同点
1. > 都是基于应用层的协议
2. >  都使用 Request/Response 模式建立连接
3. >  在连接建立过程中，对错误的处理方式相同
4. >  都可以在网络中传输数据

> 不同点
1. > ws 使用 HTTP 建立连接，但是定义了新的一系列头域，这些域在 HTTP 中不会使用
2. > ws的连接不能通过中间人来进行转发，必须是一个点对点的连接
3. > ws连接之后，通信的双方都可以随时给另一方发送数据
4. > ws连接之后，数据的传输是通过桢的形式发送的，不再需要Request


### 弹幕

直播不用我说了吧。都懂的吧，在看直播的时候，你可以看到屏幕前各种...好吧，暂且用一个不堪入目来形容评论emmm。用了 `ws` 那就简单了。客户端发送弹幕，转交给 `ws` 服务器，`ws` 服务器做了一些处理，然后再广播给所有在这个直播间的人。也就实现了，边看直播边看评论的这一幕。当然，市面上一些直播应用，基本上在安全方面做了很多功夫，毕竟一个用于生产上的 `im`，背后一点都不简单。插一句，如果像是聊天室这种对应可能对应到指定的人，可以用 `redis` 做一些 `uid` 和 `fd` 的双向绑定即可。

### 效果图

既然开始了，还是先看看效果图吧,gif文件可能有点大，加载会慢一点。然后再具体说一下实现过程。

发送弹幕(单条)

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/one.gif" >



发送弹幕(批次)

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/many.gif" >

Task

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/task.gif" >


#### 服务端代码
```
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

```

其实就是一个很简单的 demo。先看构造函数这一块吧

```
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
```

首先实例化一个 `ws` 服务，`Swoole` 内置了对 `ws` 这个服务的支持。通过几行代码就能写出一个异步非阻塞的多进程 `ws` 服务。简单的介绍一下其他的，一开始的 `open` 事件名，它的回调是 `onOpen`，当 `ws` 客户端与服务器建立连接并完成握手后会回调此函数 。`WebSocket\Server` 继承自 `Http\Server`，所以 `WebSocket\Server` 也可以同时作为 `HTTP` 服务器。另外，使用了 `WebSocket\Server` 服务器，那么 `onMessge` 回调是必须的。即上面的 `$this->server->on('message', [$this, 'onMessage'])`。

我们先来看看握手这一块。
​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/woshou.png" >


握手成功，最终服务端的响应吗是 101,这里我主要说下在握手过程中起作用的几个 `header` 域：
> Upgrade：upgrade是HTTP1.1中用于定义转换协议的header域。它表示，如果服务器支持的话，客户端希望使用> > 现有的「网络层」已经建立好的这个「连接（此处是TCP连接）」，切换到另外一个「应用层」（此处是WebSocket）协议。
> Connection：HTTP1.1中规定Upgrade只能应用在「直接连接」中，所以带有Upgrade头的HTTP1.1消息必须含有Connection头，因为Connection头的意义就是，任何接收到此消息的人（往往是代理服务器）都要在转发此消息之前处理掉Connection中指定的域（不转发Upgrade域）。如果客户端和服务器之间是通过代理连接的，那么在发送这个握手消息之前首先要发送CONNECT消息来建立直接连接。
> Sec-WebSocket-＊：` Sec-WebSocket-Version` 告诉服务器所使用的 `WebSocket Draft`(版本协议) ，`Sec-WebSocket-Key` 用来发送给服务器使用（服务器会使用此字段组装成另一个key值放在握手返回信息里发送客户端。
> Origin：作安全使用，防止跨站攻击，浏览器一般会使用这个来标识原始域。
   

至于底下的两个事件，其实就是我们的异步任务，这些以及上面的设置参数，我会在后续的文章中说明。所以这里的整个流程就是，不管是连接成功 `onOpen`，还是发送消息 `onMessage`, 再或者是关闭连接 `onClose`，我们的向所有连接的用户推送消息。`$this->server->connections` 就是遍历所有 `ws` 连接的用户，至于下面的 `isEstablished` 就是进一步判断是否是正确的 `ws` 连接，否则可能会推送失败。至于推送的操作，`push($fd,$data)` ，第一个参数就是客户端的 `fd`,如果此连接并非 `ws` 客户端，那么推送将失败。第二个参数就是推送的内容，格式化了数据。第三个参数可以指名发送内容的格式，默认是文本，如果想发送二进制的格式，可以使用 `WEBSOCKET_OPCODE_BINARY`。然后你可以看到，我们的批量弹幕的实现：
```

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
```

首先，我是以客户端提交数据的 `type` 值来确定消息类型的，当 `type=3` 的时候，就是批量弹幕，那么这里我使用了 `Swoole` 中提供的牛逼的毫秒精度定时器，所以上面的意思就是当收到客户端的消息，每0.2秒投放一个队列任务，把消息广播给所有的人。至于 `Timer`，我也会在后续文章中以例题+思考题的形式介绍。并不在本篇文章的范围内。



至于客户端的代码，我就不贴了，代码全上传到 `github` 了，后续所有的文章都会放到这个仓库里，你可以自己 `clone` 一份运行，地址在:[Swoole-for-you](https://github.com/wuqinqiang/swooleForYou)。




