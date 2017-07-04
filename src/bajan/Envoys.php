<?php

namespace bajan\Envoys;

use pocketmine\plugin\PluginBase;

class Envoys extends PluginBase{

    public function onEnable(){
        $this->getLogger()->info("Envoys has been enabled!");
    }

    public function onDisable(){
        $this->getLogger()->info("Envoys has been disabled!");
    }
}
