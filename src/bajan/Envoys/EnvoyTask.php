<?php
namespace bajan\Envoys;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class EnvoyTask extends PluginTask {

  public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

  public function onRun($currenttick){
		$this->plugin->runEnvoyEvent();
		return true;
  }

}
