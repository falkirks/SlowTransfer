<?php
namespace falkirks\slowtransfer;


use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class PublishCollectTask extends PluginTask{
    private $data;
    public function __construct(SlowTransfer $slowTransfer){
        parent::__construct($slowTransfer);
        $this->data = [];
        $slowTransfer->getServer()->getScheduler()->scheduleRepeatingTask($this, $slowTransfer->getConfig()->get('publish-collect-task-int'));

    }
    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     */
    public function onRun($currentTick){
        /** @var SlowTransfer $slowTransfer */
        $slowTransfer = $this->getOwner();
        $data = $slowTransfer->getServerTask()->collectPublishData();
        if(is_array($data) && !empty($data)){
            $this->data = array_merge($this->data, $data);
        }
    }
    public function collectData(Player $player, $namespace){
        $out = [];
        foreach($this->data as $address => $players){
            if(isset($players[$player->getName()]) && isset($players[$player->getName()][$namespace])){
                $out[$address] = $players[$player->getName()][$namespace];
                unset($this->data[$address][$player->getName()][$namespace]);
            }
        }
        return $out;
    }
}