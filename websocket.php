<?php
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Swoole\Coroutine\Http\Server;
use function Swoole\Coroutine\run;

run(function () {
    //实例化一个websocket服务
    $server = new Server('0.0.0.0', 7777, false);

    $server->set([
        'socket_timeout' => -1,
    ]);

    //定义一个路由处理器
    $server->handle('/websocket', function (Request $request, Response $ws) {

        //向客户端发送websocket握手消息
        $ws->upgrade();

        //定义全局变量
        global $wsObjects;

        $objectId = spl_object_id($ws);

        $wsObjects[$objectId] = $ws;

        //循环处理消息的接收和发送
        while (true) {

            //接受websocket的消息帧
            $frame = $ws->recv(-1);

            if ($frame === '') {

                unset($wsObjects[$objectId]);
                $ws->close();
                break;

            } else if ($frame === false) {

                echo 'errorCode: ' . swoole_last_error() . "\n";
                $ws->close();
                break;

            } else {

                if ($frame->data == 'close' || get_class($frame) === CloseFrame::class) {

                    unset($wsObjects[$objectId]);
                    $ws->close();
                    break;

                }

                //群发
                foreach ($wsObjects as $obj) {

                    //每个连接个客户端都发
                    $obj->push($frame->data);

                }

                //向对端发送数据帧
//                $ws->push($frame->data);

            }
        }
    });


    $server->start();
});
