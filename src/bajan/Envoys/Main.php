<?php

namespace bajan\Envoys;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener{
	//minutes
	public $spawntime = 5;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->items = (new Config($this->getDataFolder() ."resources\Items.yml", Config::YAML))->getAll();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $this->spawntime*60*20);
		$this->saveResource("Config.yml");
                $this->saveResource("Enovys.yml");
                $this->saveResource("Items.yml");
	}

	public function runEnvoyEvent(){
		$cfg = (new Config($this->getDataFolder() ."resources\Envoys.yml", Config::YAML))->getAll();
		foreach($this->getServer()->getOnlinePlayers() as $player){
                        $player->sendMessage(TF::AQUA."WORLD EVENT");
			$player->sendMessage(TF::GREEN."Envoys are being spawned in the warzone!");
		}
		foreach($cfg as $data => $level){
			$data = explode(":",$data);
			$tile = $this->getServer()->getLevelByName($level)->getTile(new Vector3(intval($data[0]),intval($data[1]),intval($data[2])));
			$i = rand(3,5);
			while($i > 0){
				$item = $this->items[array_rand($this->items)];
				$item = explode(":",$item);
				$tile->getInventory()->addItem(Item::get($item[0],$item[1],$item[2]));
				$i--;
			}
		}
	}

	public function setEnvoy(Player $player){
		$cfg = new Config($this->getDataFolder() ."resources\Envoys.yml", Config::YAML);
		$cfg->set($player->x.":".$player->y.":".$player->z, $player->getLevel()->getName());
		$cfg->save();
		$player->getLevel()->setBlock($player->getPosition()->asVector3(), Block::get(54));
		$player->sendMessage(TF::GREEN."Envoy set!");
		return true;
	}

	
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		switch($cmd){
			case "setenvoy":
				if(!$player->hasPermission("envoy.set")) {$player->sendMessage(TF::RED."You do not have the required permission"); return false;}
				$this->setEnvoy($player);
				return true;
		}
	}
}
