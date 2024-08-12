<?php

declare(strict_types=1);

namespace bajan\envoys;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\utils\TextFormat;
use bajan\envoys\utils\EnvoyManager;
use bajan\envoys\utils\EnvoyFloatingText;
use bajan\envoys\utils\RewardManager;

class Envoys extends PluginBase implements Listener {

    private int $interval = 300;
    private int $despawnTimer = 120;
    private int $minEnvoy = 1;
    private int $maxEnvoy = 10;
    private EnvoyManager $envoyManager;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("rewards.yml");
        $this->interval = $this->getConfig()->get("envoy-spawn-interval");
        $this->despawnTimer = $this->getConfig()->get("despawn-timer");
        $this->minEnvoy = $this->getConfig()->get("min_envoy");
        $this->maxEnvoy = $this->getConfig()->get("max_envoy");
        $spawnLocations = $this->getConfig()->get("envoy-spawn-locations", []);
        $this->envoyManager = new EnvoyManager($this, $spawnLocations, $this->despawnTimer, $this->minEnvoy, $this->maxEnvoy);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->scheduleEnvoySpawnTask();
        RewardManager::initialize($this->getDataFolder());
    }

    private function scheduleEnvoySpawnTask(): void {
        $totalTime = $this->interval;
        $intervals = [
            3600,
            1800,
            900,
            600,
            300,
            60,
            30,
            15,
            10,
            5,
            4,
            3,
            2,
            1
        ];

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use (&$totalTime, $intervals): void {
            $totalTime--;
        
            if (in_array($totalTime, $intervals, true)) {
                $this->getServer()->broadcastMessage(TextFormat::GREEN . "An envoy will spawn in " . $this->formatTime($totalTime) . "!");
            }

            if ($totalTime <= 0) {
                $this->envoyManager->randomlySpawnEnvoys();
                $totalTime = $this->interval;
            }
        }), 20);
    }

    private function formatTime(int $seconds): string {
        if ($seconds >= 3600) {
            $hours = intdiv($seconds, 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "");
        } elseif ($seconds >= 60) {
            $minutes = intdiv($seconds, 60);
            return $minutes . " minute" . ($minutes > 1 ? "s" : "");
        } else {
            return $seconds . " second" . ($seconds > 1 ? "s" : "");
        }
    }


    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $position = $block->getPosition();
        $world = $player->getWorld();

        if ($this->envoyManager->isEnvoyPosition($world, $position)) {
            $this->envoyManager->claimEnvoy($player, $world, $position);
            $event->cancel();
        }
    }

    public function onChunkLoad(ChunkLoadEvent $event): void {
        $chunk = $event->getChunk();
        $world = $event->getWorld();
        $chunkX = $event->getChunkX();
        $chunkZ = $event->getChunkZ();

        foreach ($this->envoyManager->getActiveEnvoys() as $envoy) {
            $position = $envoy['position'];
            if ($world->getFolderName() === $envoy['world'] && $position->x >> 4 === $chunkX && $position->z >> 4 === $chunkZ) {
                EnvoyFloatingText::create($position, "§l§bEnvoy\nTap me!\n\nDespawning in §e" . $this->despawnTimer . "§f!", $envoy['tag']);
            }
        }
    }

    public function onChunkUnload(ChunkUnloadEvent $event): void {
        $this->envoyManager->saveEnvoyData();
    }

    public function onWorldUnload(WorldUnloadEvent $event): void {
        $this->envoyManager->saveEnvoyData();
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void {
        $entity = $event->getEntity();
        $fromWorld = $event->getFrom()->getWorld();
        $toWorld = $event->getTo()->getWorld();

        if ($fromWorld !== $toWorld) {
            foreach ($this->envoyManager->getActiveEnvoys() as $envoy) {
                $envoyPosition = $envoy['position'];
                $envoyTag = $envoy['tag'];
                EnvoyFloatingText::remove($envoyTag);
            }
        }
    }
}
