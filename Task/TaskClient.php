<?php


go(function () {
    $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    // 尝试与指定 TCP 服务端建立连接（IP和端口号需要与服务端保持一致，超时时间为0.5秒）
    if ($client->connect("127.0.0.1", 9505, 0.5)) {
        // 建立连接后发送内容
        $client->send("hello world\n");
        // 打印接收到的消息
        echo $client->recv() . PHP_EOL;
        sleep(2);
        // 关闭连接
        $client->close();
    } else {
        echo "connect failed.";
    }
});


/**
 * @param Integer[] $height
 * @return Integer
 */
function trap($height)
{
    $res = 0;
    $size = count($height);
    $left[0] = $height[0];
    for ($i = 1; $i < $size; $i++) {
        $left[$i] = max($left[$i - 1], $height[$i]);
    }
    $right[$size - 1] = $height[$size - 1];
    for ($i = $size - 2; $i >= 0; $i--) {
        $right[$i] = max($right[$i + 1], $height[$i]);
    }

    for ($i = 1; $i < $size - 1; $i++) {
        $res += min($left[$i], $right[$i]) - $height[$i];
    }
    return $res;

}
