<?php
namespace falkirks\slowtransfer;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use shoghicp\FastTransfer\FastTransfer;
use shoghicp\FastTransfer\PlayerTransferEvent;

class SlowTransfer extends PluginBase implements Listener{
    const PROTOCOL_IDENTIFIER = "SLOWTRANSFER-1";
    /** @var  ServerTask */
    private $serverTask;
    /** @var  TransferStore */
    private $transferStore;
    /** @var  PublishCollectTask */
    private $publishCollectTask;
    public function onEnable(){
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->transferStore = new TransferStore($this);
        $this->publishCollectTask = new PublishCollectTask($this);
        $this->serverTask = new ServerTask($this->getLogger(), $this->getConfig());
    }
    public function onDisable(){
        $this->getLogger()->info(var_export($this->serverTask->getPublishData(), true));
    }
    public function set(Player $player, $value, $namespace){
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            $fullClass = explode("\\", $trace[1]['class']);
            $payload = array_shift($fullClass);
            $this->transferStore->set($player, $namespace, $value);
        }
    }
    public function get(Player $player, $namespace){
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            $fullClass = explode("\\", $trace[1]['class']);
            $payload = array_shift($fullClass);
            return $this->publishCollectTask->collectData($player, $payload);
        }
        return null;

    }
    public function onPlayerChat(PlayerChatEvent $event){
        $this->set($event->getPlayer(), $event->getMessage(), "slowtransfer");
        $fastTransfer = $this->getServer()->getPluginManager()->getPlugin("FastTransfer");
        if($fastTransfer instanceof FastTransfer){
            $fastTransfer->transferPlayer($event->getPlayer(), "192.168.1.66", 19133);
        }
    }
    public function onPlayerJoin(PlayerJoinEvent $event){
        $data = $this->publishCollectTask->collectData($event->getPlayer(), "slowtransfer");
        $this->getLogger()->info(var_export($data, true));
    }
    public function onPlayerTransfer(PlayerTransferEvent $event){
        $data = $this->transferStore->collect($event->getPlayer());
        if($data !== null){
            $event->setCancelled();
            $task = new DataPublishTask($event->getPlayer(), $event->getAddress(), $event->getPort(), $event->getMessage(), $this->getConfig()->get('application-port'), $data);
            $this->getServer()->getScheduler()->scheduleAsyncTask($task);
        }
    }
    /**
     * @return ServerTask
     */
    public function getServerTask(){
        return $this->serverTask;
    }

    /**
     * @return TransferStore
     */
    public function getTransferStore(){
        return $this->transferStore;
    }

    /**
     * @return PublishCollectTask
     */
    public function getPublishCollectTask(){
        return $this->publishCollectTask;
    }

}