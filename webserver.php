<?php
set_time_limit(0);

$host = "127.0.0.1";
$port = 2048;
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create    socket
"); // 创建一个Socket

$connection = socket_connect($socket, $host, $port) or die("Could not connet server
");    //  连接

socket_write($socket, "hello socket dfhgfh dfghffh gdtghfhfh  dghfh  dfghf  gfhfh fghsd ghsghdsfgsdfg单方事故电饭锅电饭锅鬼地方个第三方跟第三方") or die("Write failed"); // 数据传送 向服务器发送消息
while ($buff = @socket_read($socket, 1024, PHP_BINARY_READ)) {
    echo "Response was:" . $buff . " ";
}
socket_close($socket);