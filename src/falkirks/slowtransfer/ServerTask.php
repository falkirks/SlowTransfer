<?php
namespace falkirks\slowtransfer;


use pocketmine\Thread;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ServerTask extends Thread{
    private $sock;
    private $logger;
    private $config;
    public $stop;
    private $publishData;

    public function __construct(\Logger $logger, Config $config) {
        $this->stop = false;
        $this->logger = $logger;
        $this->config = $config;
        $this->publishData = serialize([]);
        try {
            $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            //socket_set_option ($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
            socket_bind($this->sock, "0.0.0.0", $this->config->get("application-port"));
            socket_listen($this->sock, 5);
            socket_set_block($this->sock);

            $this->getLogger()->info("Server started on port " .  $this->config->get("application-port") . ".");
            $this->start();
        }
        catch(\RuntimeException $e){
            $this->getLogger()->critical("Failed to start server.");
            $this->stop();
        }
    }
    public function stop() {
        $this->getLogger()->info("Server stopped.");
        $this->stop = true;
    }
    public function run() {
        $this->registerClassLoader();
        while($this->stop === false) {
            $publishData = [];
            if (($msgsock = @socket_accept($this->sock)) === false) {
                continue;
            }
            socket_getpeername($msgsock, $address);
            if(($protocol = rtrim(@socket_read($msgsock, 64, PHP_NORMAL_READ))) === SlowTransfer::PROTOCOL_IDENTIFIER){
                socket_write($msgsock, SlowTransfer::PROTOCOL_IDENTIFIER . "\n");
                $intent = rtrim(@socket_read($msgsock, 64, PHP_NORMAL_READ));
                switch(strtoupper($intent)){
                    case 'PUBLISH':
                        $player = rtrim(socket_read($msgsock, 2048, PHP_NORMAL_READ));
                        if($player){
                            while(($namespace = rtrim(@socket_read($msgsock, 2048, PHP_NORMAL_READ))) && $namespace != "STOP") {
                                $length = rtrim(@socket_read($msgsock, 32, PHP_NORMAL_READ));
                                if ($length) {
                                    $buf = @socket_read($msgsock, $length, PHP_BINARY_READ);
                                    $publishData[$address][$player][$namespace][] = unserialize($buf);
                                }
                                else{
                                    break;
                                }
                            }
                            socket_write($msgsock, "WAITING\n");
                        }
                        else{
                            socket_write($msgsock, "ERROR\n");
                            socket_write($msgsock, "13\n");
                        }
                        break;
                    case 'PING':
                        socket_write($msgsock, "PONG\n");
                        break;
                    default:
                        socket_write($msgsock, "ERROR\n");
                        socket_write($msgsock, $intent . "\n");
                        //$this->getLogger()->warning(strtoupper($intent) . " isn't supported by this plugin version.");
                        break;
                }
            }
            else{
                if(strpos($protocol, "SLOWTRANSFER-") === 0) {
                    socket_write($msgsock, "ERROR\n");
                    socket_write($msgsock, "12\n");
                }
                else{
                    socket_write($msgsock, SlowTransfer::PROTOCOL_IDENTIFIER . "\n");
                    socket_write($msgsock, "ERROR\n");
                    socket_write($msgsock, "11\n");
                }
            }
            $this->publishData = serialize(array_merge(unserialize($this->publishData), $publishData));
            socket_write($msgsock, "STOP\n");
            @socket_shutdown($msgsock);
            socket_close($msgsock);
        }
        @socket_shutdown($this->sock);
        $arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
        socket_set_block($this->sock);
        socket_set_option($this->sock, SOL_SOCKET, SO_LINGER, $arrOpt);
        socket_close($this->sock);
        exit(0);
    }
    /**
     * @return \Logger
     */
    public function getLogger(){
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getPublishData(){
        return unserialize($this->publishData);
    }
    public function collectPublishData(){
        $data = $this->getPublishData();
        $this->synchronized(function(ServerTask $thread){
            $thread->publishData = serialize([]);
        }, $this);
        return $data;
    }
}