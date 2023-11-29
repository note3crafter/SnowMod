<?php

//   ╔═════╗╔═╗ ╔═╗╔═════╗╔═╗    ╔═╗╔═════╗╔═════╗╔═════╗
//   ╚═╗ ╔═╝║ ║ ║ ║║ ╔═══╝║ ╚═╗  ║ ║║ ╔═╗ ║╚═╗ ╔═╝║ ╔═══╝
//     ║ ║  ║ ╚═╝ ║║ ╚══╗ ║   ╚══╣ ║║ ║ ║ ║  ║ ║  ║ ╚══╗
//     ║ ║  ║ ╔═╗ ║║ ╔══╝ ║ ╠══╗   ║║ ║ ║ ║  ║ ║  ║ ╔══╝
//     ║ ║  ║ ║ ║ ║║ ╚═══╗║ ║  ╚═╗ ║║ ╚═╝ ║  ║ ║  ║ ╚═══╗
//     ╚═╝  ╚═╝ ╚═╝╚═════╝╚═╝    ╚═╝╚═════╝  ╚═╝  ╚═════╝
//   Plugin by TheNote! Not for Sale! Now for Pocketmine
//                        2017-2022

namespace TheNote\SnowMod;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\utils\SlabType;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wall;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

class SnowMod extends PluginBase implements Listener
{
    public int $cooltime = 0;
    public mixed $m_version = 2, $pk;
    public array $denied = [];

    public function onEnable(): void
    {
        $this->getScheduler()->scheduleRepeatingTask(new SnowModTask ($this), 1);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("settings.yml", false);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        if ($command->getName() === "snowmod") {
            if (isset($args[0])) {
                if (strtolower($args[0]) === "enable") {
                    if ($sender->hasPermission("snowmod.use")) {
                        $settings->set("snowallowed", true);
                        $settings->save();
                        $sender->sendMessage(TextFormat::GREEN . "The SnowMod are Enabled.");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You have no Permission to use this Command!");
                    }
                } else if (strtolower($args[0]) === "disable") {
                    if ($sender->hasPermission("snowmod.use")) {
                        $settings->set("snowallowed", false);
                        $settings->save();
                        $sender->sendMessage(TextFormat::GREEN . "The SnowMod are Disabled.");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You have no Permission to use this Command!");
                    }
                } else {
                    $sender->sendMessage("Usage: /snowmod <disable/enable>");
                    return false;
                }
            } else {
                $sender->sendMessage("Usage: /snowmod <disable/enable>");
                return false;
            }
        }
        return true;
    }

    public function onChunkLoadEvent(ChunkLoadEvent $event): bool
    {
        $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $check = $settings->get("snowallowed");
        if ($check === true) {
            for ($x = 0; $x < 16; ++$x)
                for ($z = 0; $z < 16; ++$z)
                    for($y = World::Y_MIN; $y < World::Y_MAX; $y++)
                    $event->getChunk()->setBiomeId($x, $y, $z, BiomeIds::ICE_PLAINS);
        } elseif ($check === false) {
            for ($x = 0; $x < 16; ++$x)
                for ($z = 0; $z < 16; ++$z)
                    for($y = World::Y_MIN; $y < World::Y_MAX; $y++)
                    $event->getChunk()->setBiomeId($x, $y, $z, BiomeIds::PLAINS);
        }
        return true;
    }

    public function onPlayerJoinEvent(PlayerJoinEvent $event): bool
    {
        $player = $event->getPlayer();
        $pk = new LevelEventPacket ();
        $pk->eventId = 3001;
        $pk->eventData = 10000;
        $player->getNetworkSession()->sendDataPacket($pk);
        return true;
    }

    public function SnowMod()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->SnowModCreate($player);
        }
    }

    public function SnowModCreate(Player $player)
    {

        $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $check = $settings->get("snowallowed");
        $x = mt_rand(round($player->getPosition()->getX() - 15), round($player->getPosition()->getX() + 15));
        $z = mt_rand(round($player->getPosition()->getZ() - 15), round($player->getPosition()->getZ() + 15));
        $y = ($hb = $player->getPosition()->getWorld()->getHighestBlockAt($x, $z)) === null ? ($player->getPosition()->getWorld()->getHighestBlockAt((int)$player->getPosition()->getX(), (int)$player->getPosition()->getZ()) ?? (int)$player->getPosition()->getY()) : $hb;
        if ($check === false) {
            if ($this->cooltime < 11) {
                $this->cooltime++;
                $this->getScheduler()->scheduleDelayedTask(new SnowDestructLayer($this, new Position ($x, $y, $z, $player->getWorld())), 20);
            }
        } elseif ($check === true) {
            if ($this->cooltime < 11) {
                $this->cooltime++;
                $this->getScheduler()->scheduleDelayedTask(new SnowCreateLayer($this, new Position ($x, $y, $z, $player->getWorld())), 20);
            }
        }
    }

    public function SnowCreate(Position $pos)
    {
        $this->cooltime--;
        if ($pos == null)
            return;
        $down = $pos->getWorld()->getBlock($pos);
        if (!$down->isSolid())
            return;
        if ($down->getTypeId() == BlockTypeIds::MYCELIUM
            or $down->getTypeId() == BlockTypeIds::PODZOL
            or $down->getTypeId() == BlockTypeIds::COBBLESTONE
            or $down->getTypeId() == BlockTypeIds::DEAD_BUSH
            or $down->getTypeId() == BlockTypeIds::WATER
            or $down->getTypeId() == BlockTypeIds::LAVA
            or $down->getTypeId() == BlockTypeIds::WOOL
            or $down->getTypeId() == BlockTypeIds::ACACIA_FENCE_GATE
            or $down->getTypeId() == BlockTypeIds::BIRCH_FENCE_GATE
            or $down->getTypeId() == BlockTypeIds::DARK_OAK_FENCE_GATE
            or $down->getTypeId() == BlockTypeIds::JUNGLE_FENCE_GATE
            or $down->getTypeId() == BlockTypeIds::OAK_FENCE_GATE
            or $down->getTypeId() == BlockTypeIds::SPRUCE_FENCE_GATE
            or $down->getTypeId() == BlockTypeIds::SMOOTH_STONE
            or $down->getTypeId() == BlockTypeIds::FARMLAND)
            return;

        if ($down instanceof Stair || ($down instanceof Slab && $down->getSlabType() === SlabType::BOTTOM)
        || $down instanceof Fence || $down instanceof FenceGate || $down instanceof Wall) return;

        $up = $pos->getWorld()->getBlock($pos->add(0, 1, 0));
        if ($up->getTypeId() != BlockTypeIds::AIR)
            return;
        $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $level = $settings->get("worlds", []);
        $wname = $pos->getWorld()->getFolderName();
        if (in_array($wname, $level)) {
            $pos->getWorld()->setBlock($pos->add(0, 1, 0), VanillaBlocks::SNOW_LAYER(), true);
        }
    }

    public function SnowDestruct(Position $pos)
    {
        $this->cooltime--;
        if ($pos == null) return;
        $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $level = $settings->get("worlds", []);
        $wname = $pos->getWorld()->getFolderName();
        if (in_array($wname, $level)) {
            if ($pos->getWorld()->getBlockAt($pos->x, $pos->y, $pos->z)->getTypeId() == BlockTypeIds::SNOW_LAYER) {
                $pos->getWorld()->setBlock($pos, VanillaBlocks::AIR(), false);
            }
        }

    }
}
