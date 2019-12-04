## :pencil2:Timer 
  
  **Swoole提供了异步高精度的定时器,底层基于 epoll_wait 和 setitimer 实现,数据结构采用的最小堆,可以支持添加大量的定时器.对应定时器的添加和删除,全部都是内存中的操作,因此性能极高.同时支持的时间粒度是毫秒级别的.现在,让我们脱离掉理论,开始实践吧**
****


**我们先从一个简单的demo开始,开始之前请确保安装了swoole**


## :pencil2:tick 

```php

\Swoole\Timer::tick(2000,function (){
    echo '每隔两秒我会执行一次'.PHP_EOL;
});
```

**我们设置了一个间隔时间的定时器,tick 会持续的触发,因为是以毫秒为单位的,所以这里的2000表示2秒执行一次,后面跟着一个匿名函数,表示时间到执行的逻辑部分,不用我多说了吧,看结果**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/timer-1.png" >
****

**和预期的结果一样,现在我们使用匿名函数的 use 传点参数**

```php
$num=1;
\Swoole\Timer::tick(2000,function () use ($num){
    echo ++$num.PHP_EOL;
  //  echo date('Y-m-d H:i:s'). ' 每隔两秒我会执行一次 '.PHP_EOL;
});
```

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/timer-2.png" >


**可能初学者会有点奇怪,结果咋么都是2不加上去,很简单,每2秒执行一次闭包的逻辑,每次调用的结果和下一次没有任何关系,num 依然是被初始赋值的1,你想有关系也很简单,在$num 前面加&**

```php
$num=1;
\Swoole\Timer::tick(2000,function () use (&$num){
    echo ++$num.PHP_EOL;
  //  echo date('Y-m-d H:i:s'). ' 每隔两秒我会执行一次 '.PHP_EOL;
});
```
​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/timer-3.png" >

## :pencil2:after 


**之前运行的是间隔性的定时器,其实定时器还有一种形式,即指定时间后执行的定时器,它和上面那个不一样,只执行一次**

```php
echo 'start_time: ' . date('Y-m-d H:i:s') . PHP_EOL;

$timer = \Swoole\Timer::after(5000, function () {
    echo 'end_time: ' . date('Y-m-d H:i:s') . PHP_EOL;
    echo '我执行一次就凉了'.PHP_EOL;
});

```


​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/timer-4.png" >

**可以看到,过了五秒歇菜了.**


## :pencil2:clear 

**那么我们咋么删除定时器呢.官方提供了  Swoole\Timer::clear(定时器id),函数风格的别名是 swoole_timer_clear 当然了,上面提到的也有函数别名**

```php
$timer = \Swoole\Timer::after(5000, function () {
    echo 'end_time: ' . date('Y-m-d H:i:s') . PHP_EOL;
    echo '我执行一次就凉了'.PHP_EOL;
});

var_dump(\Swoole\Timer::clear($timer));
var_dump('高冷的我只打印这一句话').PHP_EOL;
```

**可以看到定时器将不会去执行,但是请注意,这个函数并不能清除其他进程的定时器,只作用于当前进程,什么意思呢,让我们开启两个窗口,代表两个进程,然后先设置一个间隔2秒执行一次的定时器,先执行,执行完之后补上 clear,然后再另一个窗口中执行程序**

​    <img src="https://github.com/wuqinqiang/swooleForYou/blob/master/image/timer-5.png" >

**可以看到先执行窗口1 ,然后加上删除定时器的clear代码,再执行窗口2,可以看到窗口2的定时任务清除了,所以不执行,但是 clear 的作用也仅仅在当前进程内有效,影响不了其他进程.**

**我们再来一点操作**

```php
$num = 10;
$timer = \Swoole\Timer::tick(2000, function () use (&$num) {
    echo ++$num . PHP_EOL;
    \Swoole\Timer::after(5000, function () use (&$num) {
        $num -= 2;

        echo $num.PHP_EOL;
    });
});

\Swoole\Timer::after(8000, function () use ($timer) {
    \Swoole\Timer::clear($timer);
});

```
**思考一下,屏幕上每个阶段的值各是多少,我不给出答案了,这一部分留给你们**

## :pencil2:应用场景

**比如跑的一些脚本任务,如果还需要请求第三方接口,接口超时或者请求失败,设置多久重试,并且多少次请求失败停止请求接口** 



### 联系
<a href="https://github.com/wuqinqiang/">
公众号
​    <img src="https://github.com/wuqinqiang/Lettcode-php/blob/master/qrcode_for_gh_c194f9d4cdb1_430.jpg" width="200px" height="200px">
个人微信  
​    <img src="https://github.com/wuqinqiang/Lettcode-php/blob/master/images/Wechat.png" width="200px" height="200px">
****
 