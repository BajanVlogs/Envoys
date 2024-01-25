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
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\FireworksParticle;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;

class Main extends PluginBase implements Listener {

    //minutes
    public $spawntime = 5;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $this->spawntime * 60 * 20);
        @mkdir($this->getDataFolder());
        $this->saveResource("Config.yml");
        $this->saveResource("Envoys.yml");
        $this->saveResource("Items.yml");
        $this->cfg = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
        $this->envoys = new Config($this->getDataFolder() . "Envoys.yml", Config::YAML);
        $this->items = new Config($this->getDataFolder() . "Items.yml", Config::YAML);
    }

    public function runEnvoyEvent() {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->sendMessage(TF::AQUA . "WORLD EVENT");
            $player->sendMessage(TF::GREEN . "Envoys are being spawned in the warzone!");
        }

        foreach ($this->envoys as $data => $level) {
            $data = explode(":", $data);
            $tile = $this->getServer()->getLevelByName($level)->getTile(new Vector3(intval($data[0]), intval($data[1]), intval($data[2])));
            $i = rand(3, 5);

            while ($i > 0) {
                $item = $this->items[array_rand($this->items)];
                $item = explode(":", $item);
                $tile->getInventory()->addItem(Item::get($item[0], $item[1], $item[2]));
                $i--;
            }

            // Add particle effects to the envoy chest
            $this->addParticleEffects($tile->getPosition());
        }
    }

    private function addParticleEffects(Vector3 $position) {
        $level = $this->getServer()->getLevelByName("your_level_name_here");

        // Spawn portal particles
        $level->addParticle(new PortalParticle($position));

        // Spawn fireworks in the sky
        $pk = new SpawnParticleEffectPacket();
        $pk->effectId = 4; // Firework effect
        $pk->position = $position;
        $pk->data = 0; // Firework data
        $level->broadcastPacketToViewers($position, $pk);
        
        // Play sound and light flash
        $level->addLevelEvent($position, LevelEventPacket::EVENT_SOUND_ANVIL_USE);
        $level->addLevelEvent($position, LevelEventPacket::EVENT_SOUND_ORB);
    }

    public function setEnvoy(Player $sender) {
        $this->envoys->set($sender->x . ":" . $sender->y . ":" . $sender->z, $sender->getLevel()->getName());
        $this->envoys->save();
        $items = $this->items->get("Items");
        $item = $items[array_rand($items)];
        $values = explode(":", $item);
        $level = $sender->getLevel();
        $level->setBlock($sender->getPosition()->asVector3(), Block::get(Block::ENDER_CHEST));
        $nbt = new CompoundTag(" ", [
            new ListTag("Items", []),
            new StringTag("id", Tile::END_CHEST),
            new IntTag("x", $sender->x),
            new IntTag("y", $sender->y),
            new IntTag("z", $sender->z)
        ]);
        $enderChest = Tile::createTile("EnderChest", $sender->getLevel(), $nbt);
        $level->addTile($enderChest);
        $inv = $enderChest->getInventory();
        $inv->addItem(Item::get($values[0], $values[1]));
        $sender->sendMessage(TF::GREEN . "Envoy set!");
        return true;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch ($cmd->getName()) {
            case "setenvoy":
                if (!$sender->hasPermission("envoy.set")) {
                    $sender->sendMessage(TF::RED . "You do not have the required permission");
                    return false;
                }
                $this->setEnvoy($sender);
                return true;
        }
        return false;
    }
}
