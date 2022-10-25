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

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use TheNote\core\player\Player;

class SnowMod extends PluginBase implements Listener
{
    public $cooltime = 0;
    public $m_version = 2, $pk;
    public $denied = [];

    public function onEnable(): void
    {
        $this->getScheduler()->scheduleRepeatingTask(new SnowModTask ($this), 1);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("settings.json", false);
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
                    $event->getChunk()->setBiomeId($x, $z, BiomeIds::ICE_PLAINS);
        } elseif ($check === false) {
            for ($x = 0; $x < 16; ++$x)
                for ($z = 0; $z < 16; ++$z)
                    $event->getChunk()->setBiomeId($x, $z, BiomeIds::PLAINS);
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
        $x = mt_rand($player->getPosition()->getX() - 15, $player->getPosition()->getX() + 15);
        $z = mt_rand($player->getPosition()->getZ() - 15, $player->getPosition()->getZ() + 15);
        $y = $player->getWorld()->getHighestBlockAt($x, $z);
        if ($check === false) {
            if ($this->cooltime < 11) {
                $this->cooltime++;
                $this->getScheduler()->scheduleDelayedTask(new SnowDestructLayer ($this, new Position ($x, $y, $z, $player->getWorld())), 20);
            }
        } elseif ($check === true) {
            if ($this->cooltime < 11) {
                $this->cooltime++;
                $this->getScheduler()->scheduleDelayedTask(new SnowCreateLayer ($this, new Position ($x, $y, $z, $player->getWorld())), 20);
            }
        }
    }

    public function SnowCreate(Vector3 $pos)
    {
        $this->cooltime--;
        if ($pos == null)
            return;
        $down = $pos->getWorld()->getBlock($pos);
        if (!$down->isSolid())
            return;
        if ($down->getId() == BlockLegacyIds::MYCELIUM
            or $down->getId() == BlockLegacyIds::PODZOL
            or $down->getId() == BlockLegacyIds::COBBLESTONE
            or $down->getId() == BlockLegacyIds::DEAD_BUSH
            or $down->getId() == BlockLegacyIds::WATER
            or $down->getId() == BlockLegacyIds::LAVA
            or $down->getId() == BlockLegacyIds::WOOL
            or $down->getId() == BlockLegacyIds::ACACIA_FENCE_GATE
            or $down->getId() == BlockLegacyIds::BIRCH_FENCE_GATE
            or $down->getId() == BlockLegacyIds::DARK_OAK_FENCE_GATE
            or $down->getId() == BlockLegacyIds::JUNGLE_FENCE_GATE
            or $down->getId() == BlockLegacyIds::OAK_FENCE_GATE
            or $down->getId() == BlockLegacyIds::SPRUCE_FENCE_GATE
            or $down->getID() == BlockLegacyIds::SMOOTH_STONE
            or $down->getId() == BlockLegacyIds::FARMLAND)
            return;

        $up = $pos->getWorld()->getBlock($pos->add(0, 1, 0));
        if ($up->getId() != BlockLegacyIds::AIR)
            return;
        $settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $level = $settings->get("worlds", []);
        $wname = $pos->getWorld()->getFolderName();
        if (in_array($wname, $level)) {
            $pos->getWorld()->setBlock($pos->add(0, 1, 0), BlockFactory::getInstance()->get(BlockLegacyIds::SNOW_LAYER, 0), true);
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
            if ($pos->getWorld()->getBlockAt($pos->x, $pos->y, $pos->z)->getId() == BlockLegacyIds::SNOW_LAYER) {
                $pos->getWorld()->setBlock($pos, BlockFactory::getInstance()->get(BlockLegacyIds::AIR, 0), false);
            }
        }

    }
}