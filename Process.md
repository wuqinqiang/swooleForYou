## :pencil2:swoole process 进程管理模块
  
  **我们都知道,PHP有它自带的进程控制 `pcntl`,`Swoole` 中的 `process` 提供了更强大的功能,直接截取了官网的一张图**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/process-1.png" >

**下面我们模拟一个TCP服务器,演示一下基于 `process` 的多进程服务.**

**接下来,先来看我们服务器的简易代码部分**
## :pencil2:task 

**我们设置子进程数为3个,在下面这段代码中,主进程启动之后,会额外启动3个子进程,处理客户端连接以及请求的信息,当子进程退出后,主进程会重新创建新的子进程.如果主进程退出,那么子进程在处理完当前请求之后也会退出**


## :pencil2:Server

```php
<?php
class Server
{
    private $mpid; //主进程id
    private $pids = []; //子进程数组
    private $socket; //网络套间字

    const Max_PROCESS = 3; //子进程数

    //主进程逻辑
    public function run()
    {
        $process = new \Swoole\Process(function () {

            //获取主进程id
            $this->mpid = posix_getpid();
            echo time() . " master process pid: {$this->mpid}" . PHP_EOL;
            //创建TCP服务器并获取套间字
            $this->socket = stream_socket_server("tcp://127.0.0.1:9505", $errno, $errstr);
            if (!$this->socket) {
                exit("service start error:$errstr --- $errno");
            }

            //启动子进程
            for ($i = 1; $i <= self::Max_PROCESS; $i++) {
                $this->startWorkerProcess();
            }

            echo "Waiting client connect" . PHP_EOL;

            //主进程等待子进程退出 必须是死循环
            while (1) {
                if(count($this->pids)){
                        //回收结束运行的子进程,默认为阻塞,false 表示非阻塞模式,如果失败返回 false
                        $ret = \Swoole\Process::wait(false);
                        if ($ret) {
                            echo time() . " worker process: {$ret['pid']} exit ,then new process..." . PHP_EOL;
                            //新创建一个子进程
                            $this->startWorkerProcess();
                            
                            //从数组中删除这个已不存在的子进程 pid
                            $index=array_search($ret['pid'],$this->pids);
                            unset($this->pids[$index]);
                        }
                }
                sleep(1);  //
            }
        }, false, false);  //第三个参数默认为true,表示启用管道,这里暂时不实现进程中的通信改为false
        //把当前进程升级为守护进程
        \Swoole\Process::daemon();

        $process->start();
    }


    //创建子进程
    public function startWorkerProcess()
    {
        $process = new \Swoole\Process(function (\Swoole\Process $work) {
            //接收客户端请求
            $this->acceptClient($work);

        }, false, false);
        $pid = $process->start();
        $this->pids[] = $pid;
    }

    //接收客户端请求
    public function acceptClient(&$worker)
    {
        //子进程等待客户端连接,不能退出
        while (1) {
            //接收由 stream_socket_server()创建的的套间字连接,
            $conn = stream_socket_accept($this->socket, -1);
            //如果定义了连接回调的地址,就调用
            if ($this->onConnect) {
                call_user_func($this->onConnect, $conn);
            }
            //开始循环读取客户端信息

            $recv = ''; //实际接收数据
            $buffer = ''; //缓冲数据

            while (1) {
                //检查主进程是否正常,如果不正常,退出子进程
                $this->checkmPid($worker);

                //读取客户端发送信息
                $buffer = fread($conn, 20);

                //如果没有消息

                if ($buffer === false || $buffer === '') {
                    //如果设置连接关闭的回调函数,那么执行关闭回调
                    if ($this->onClose) {
                        call_user_func($this->onClose, $conn);
                    }
                    //等待下一个连接消息
                    break;
                }

                //消息结束的位置
                $pos = strpos($buffer, "\n");

                //没有结束符,说明还没有读完
                if (false === $pos) {
                    $recv .= $buffer; //拼接消息
                } else {              //消息读完了,处理消息
                    $recv .= trim(substr($buffer, 0, $pos + 1));
                    //如果定义了处理消息回调的函数,那就直接调用回调
                    if ($this->onMessage) {
                        call_user_func($this->onMessage, $conn, $recv);
                    }

                    //如果接收到quit 表示退出,那么关闭这个链接,等待下一个客户端连接

                    if ($recv === 'quit') {
                        echo 'client close' . PHP_EOL;
                        fclose($conn);
                        break;
                    }

                }
                //清空消息
                $recv = '';
            }
        }
    }

    public function checkmPid(&$worker)
    {
        //说明主进程不存在,子进程此时是僵尸进程,需要退出
        if (!\Swoole\Process::kill($this->mpid, 0)) {
            $worker->exit();
            echo "worker: {$worker->pid} exit" . PHP_EOL;
        }
    }
}


$server = new Server();


//连接回调
$server->onConnect = function ($conn) {
    echo "onConnect -- accepted " . stream_socket_get_name($conn, true) . PHP_EOL;
};
//接收消息回调
$server->onMessage = function ($conn, $msg) {
    echo "message is ------ $msg" . PHP_EOL;

    fwrite($conn, "received: " . $msg . "\n");
};

//关闭回调
$server->onClose = function ($conn) {
    echo "close---" . stream_socket_get_name($conn, true) . PHP_EOL;
};

$server->run();
```

**开启服务之后,你可以通过命令查看进程 `pstree -p 主进程id` 或者通过命令查看运行的更多信息 `ps -ef | grep Server.php`,可以看到对应进程的 pid**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/process.gif" >

**现在让我们创建一个简单的客户端连接服务器.**

## :pencil2:Client
```php

go(function () {
    $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    // 尝试与指定 TCP 服务端建立连接（IP和端口号需要与服务端保持一致，超时时间为0.5秒）
    if ($client->connect("127.0.0.1", 9505, 0.5)) {
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

​<img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/process.gif" >

**客户端请求,服务端接收数据,服务端响应数据,客户端接收数据,然后我们来手动的kill掉其中的一个子进程**

​<img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/process-2.gif" >

**和预期一样,主进程又重新创建了一个新的子进程**


### 联系
<a href="https://github.com/wuqinqiang/">
公众号
​    <img src="https://github.com/wuqinqiang/Lettcode-php/blob/master/qrcode_for_gh_c194f9d4cdb1_430.jpg" width="200px" height="200px">
个人微信  
​    <img src="https://github.com/wuqinqiang/Lettcode-php/blob/master/images/Wechat.png" width="200px" height="200px">
****
 