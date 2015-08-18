<?php
namespace falkirks\slowtransfer;

use pocketmine\plugin\PluginBase;

class SlowTransfer extends PluginBase{
    const PROTOCOL_IDENTIFIER = "SLOWTRANSFER-1";
    /** @var  ServerTask */
    private $serverTask;
    public function onEnable(){
        $this->saveDefaultConfig();
        $this->serverTask = new ServerTask($this->getLogger(), $this->getConfig());
    }
    public function onDisable(){
        $this->getLogger()->info(var_export($this->serverTask->getPublishData(), true));
    }
}