<?php
class WebSocket {

    private $socket;

    private $accept;

    private $isHand = array();

    public function __construct($host, $port, $max) {

//        创建网络节点
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, TRUE);
echo 'setting...';
        socket_bind($this->socket, $host, $port);
echo 'ok
Binding the socket';
        socket_listen($this->socket, $max);
echo '...ok
Listening on the socket...ok';
    }

    public function start() {

        while(true) {
echo '
start...ok';
            $cycle = $this->accept;
            $cycle[] = $this->socket;
echo'
    
';
            /**
             * 获取$cycle数组中活动的socket，并且把不活跃的从$cycle数组中删除,具体的看文档。。
             * 这是一个同步方法，必须得到响应之后才会继续下一步,常用在同步非阻塞IO
             * 新连接到来时,被监听的端口是活跃的,如果是新数据到来或者客户端关闭链接时,活跃的是对应的客户端socket而不是服务器上被监听的端口
             * 如果客户端发来数据没有被读走,则socket_select将会始终显示客户端是活跃状态并将其保存在readfds数组中
             * 如果客户端先关闭了,则必须手动关闭服务器上相对应的客户端socket,否则socket_select也始终显示该客户端活跃(这个道理跟"有新连接到来然后没有用socket_access把它读出来,导致监听的端口一直活跃"是一样的)
             */

            socket_select($cycle, $write, $except, null);    //当没有套字节可以读写继续等待， 第四个参数为null为阻塞， 为0位非阻塞， 为 >0 为等待时间
echo 'socket_select...ok';
            foreach($cycle as $sock) {
                if($sock === $this->socket) {
                    $client = socket_accept($this->socket);  //它将返回一个新的套接字文 件描述符
                    $this->accept[] = $client;
                    $key = array_keys($this->accept);
                    $key = end($key);
                    $this->isHand[$key] = false;
                    echo '
                    ---------------------';
                } else {
                    /**
                     * 接受消息
                     * int socket_recv ( resource $socket , string &$buf , int $len , int $flags )
                     * 函数 socket_recv() 从 socket 中接受长度为 len 字节的数据，并保存在 buf 中。 socket_recv() 用于从已连接的socket中接收数据。
                     * 除此之外，可以设定一个或多个 flags 来控制函数的具体行为。
                     */
                    $length = socket_recv($sock, $buffer, 204800, 0);  //socket_recv() 返回一个数字，表示接收到的字节数。
                    $key = array_search($sock, $this->accept);
echo '
onmessages...ok:';
                    if($length < 7) {
                        $this->close($sock);
                        continue;
                    }

                    if(!$this->isHand[$key]) {
                        $this->dohandshake($sock, $buffer, $key);
echo '
$key...ok
';
                    } else {
                        // 先解码，再编码
                        $data = $this->decode($buffer);
                        $data = $this->encode($data);

                        // 判断断开连接（断开连接时数据长度小于10）
                        if(strlen($data) > 10) {
                            foreach($this->accept as $client) {
                                socket_write($client, $data, strlen($data));
                            }
                        }
                    }
                }

            }

        }

    }

    /**
     * 首次与客户端握手
     * WebSocket protocol
     * 客户端请求web socket连接时，会向服务器端发送握手请求
     * 请求包说明：
     * 必须是有效的http request 格式；
     * HTTP request method 必须是GET，协议应不小于1.1 如： Get / HTTP/1.1；
     * 必须包括Upgrade头域，并且其值为”websocket”;
     * 必须包括”Connection” 头域，并且其值为”Upgrade”;
     * 必须包括”Sec-WebSocket-Key”头域，其值采用base64编码的随机16字节长的字符序列;
     * 如果请求来自浏览器客户端，还必须包括Origin头域 。 该头域用于防止未授权的跨域脚本攻击，服务器可以从Origin决定是否接受该WebSocket连接;
     * 必须包括”Sec-webSocket-Version” 头域，当前值必须是13;
     * 可能包括”Sec-WebSocket-Protocol”，表示client（应用程序）支持的协议列表，server选择一个或者没有可接受的协议响应之;
     * 可能包括”Sec-WebSocket-Extensions”， 协议扩展， 某类协议可能支持多个扩展，通过它可以实现协议增强;
     * 可能包括任意其他域，如cookie.
     */

    public function dohandshake($sock, $data, $key) {

                        /*$data
                         "GET / HTTP/1.1
                        Host: 127.0.0.1:8008
                        Connection: Upgrade
                        Pragma: no-cache
                        Cache-Control: no-cache
                        Upgrade: websocket
                        Origin: http://localhost
                        Sec-WebSocket-Version: 13
                        User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like
                        Gecko) Chrome/53.0.2785.104 Safari/537.36 Core/1.53.2372.400 QQBrowser/9.5.10551
                        .400
                        Accept-Encoding: gzip, deflate, sdch
                        Accept-Language: zh-CN,zh;q=0.8
                        Sec-WebSocket-Key: uoZSEj4foq9ENI6MzWTAXg==
                        Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits"
                        */

        /*
         *服务器端响应如下：
         *HTTP/1.1 101 Web Socket Protocol Handshake
         *Upgrade: websocket
         *Connection: Upgrade
         *Sec-WebSocket-Accept: Y+Te7S7wQJC0FwXumEdGbv9/Mek=
         */


        /*******************************************************************/


        /*应答包说明：
        *必须包括Upgrade头域，并且其值为”websocket”;
        *必须包括Connection头域，并且其值为”Upgrade”;
        *必须包括Sec-WebSocket-Accept头域，其值是将请求包“Sec-WebSocket-Key”的值，与”258EAFA5- E914-47DA-95CA-C5AB0DC85B11″这个字符串进行拼接，然后对拼接后的字符串进行sha-1运算，再进行base64编码，就是 “Sec-WebSocket-Accept”的值；
        *应答包中冒号后面有一个空格；
        *最后需要两个空行作为应答包结束。
        *请注意:258EAFA5- E914-47DA-95CA-C5AB0DC85B11 这一串加密的字串是固定的，不可更改，否则会握手失败。客户端的请求头实际上是一个http请求，我们只要从头部匹配出 Sec-WebSocket-Key 并且按照固定加密返回即可。
        */

        /*******************************************************************/

        /*
         * 获取 Sec-WebSocket-Key 方法:
         * preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)
         * $key = $match[1];
         */

        /*******************************************************************/

        /*
         * 加密返回方法:
         *$mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
         *return base64_encode(sha1($key . $mask, true));
         */
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $data, $match)) {
            $response = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Accept: " . $response . "\r\n\r\n";

            // 写入socket
            socket_write($sock, $upgrade, strlen($upgrade));
            $this->isHand[$key] = true;
        }
    }

    /**
     * 关闭一个客户端连接
     */
    public function close($sock) {
        $key = array_search($sock, $this->accept);
        socket_close($sock);
        unset($this->accept[$key]);
        unset($this->handshake[$key]);
    }

    /**
     * 解码过程
     */
    public function decode($buffer) {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        }
        else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        }
        else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 编码过程
     */
    public function encode($buffer) {
        $length = strlen($buffer);
        if($length <= 125) {
            return "\x81".chr($length).$buffer;
        } else if($length <= 65535) {
            return "\x81".chr(126).pack("n", $length).$buffer;
        } else {
            return "\x81".char(127).pack("xxxxN", $length).$buffer;
        }
    }


}

$webSocket = new WebSocket('127.0.0.1', 8008, 100000);
$webSocket->start();

?>