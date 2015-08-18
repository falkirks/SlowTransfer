<?php
$address = "127.0.0.1";
$service_port = 8181;
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
} else {
    echo "OK.\n";
}

echo "Attempting to connect to '$address' on port '$service_port'...";
$result = socket_connect($socket, $address, $service_port);
if ($result === false) {
    echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
} else {
    echo "OK.\n";
}
socket_write($socket, "SLOWTRANSFER-1\n");
socket_write($socket, "PUBLISH\n");
socket_write($socket, "Falkirks-127.0.0.1\nfart\n12\nHello World!stink\n5\nHell!STOP\n");
while ($out = @socket_read($socket, 2048, PHP_NORMAL_READ)) {
    echo $out;
}

echo "Closing socket...";
socket_close($socket);
echo "OK.\n\n";