<?php

namespace CrazierDevotee0\Envoys;

use pocketmine\plugin\PluginBase;

class Envoys extends PluginBase{

public $location = [];
public $ramdomX = mt_rand(-140, 208);
public $ramdomY = mt_rand(-140, 208);

    public function onEnable(){
        $this->getLogger()->info("Envoys has been enabled!");
    }
         public function randomChest(Level $level){
              $chest = Block::get(Block::CHEST);
         }
    public function onDisable(){
        $this->getLogger()->info("Envoys has been disabled!");
    }
}
