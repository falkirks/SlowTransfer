<?php
namespace falkirks\slowtransfer;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;

class TransferStore implements Listener{
    private $store;
    /** @var  SlowTransfer */
    private $plugin;
    public function __construct(SlowTransfer $slowTransfer){
        $this->store = [];
        $this->plugin = $slowTransfer;
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
    }
    public function set(Player $player, $namespace, $value){
        if(!isset($this->store[$player->getName()])){
            $this->store[$player->getName()] = [];
        }
        $this->store[$player->getName()][$namespace] = $value;
    }
    public function get(Player $player, $namespace){
        if(isset($this->store[$player->getName()]) && isset($this->store[$player->getName()][$namespace])){
            return $this->store[$player->getName()][$namespace];
        }
        return null;
    }
    public function collect($player){
        if($player instanceof Player){
            $player = $player->getName();
        }
        if(isset($this->store[$player])){
            $data = $this->store[$player];
            unset($this->store[$player]);
            return $data;
        }
        return null;
    }
    public function onPlayerQuit(PlayerQuitEvent $event){
        if(isset($this->store[$event->getPlayer()->getName()])){
            unset($this->store[$event->getPlayer()->getName()]);
        }
    }
}