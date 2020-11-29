<?php

//   ╔═════╗╔═╗ ╔═╗╔═════╗╔═╗    ╔═╗╔═════╗╔═════╗╔═════╗
//   ╚═╗ ╔═╝║ ║ ║ ║║ ╔═══╝║ ╚═╗  ║ ║║ ╔═╗ ║╚═╗ ╔═╝║ ╔═══╝
//     ║ ║  ║ ╚═╝ ║║ ╚══╗ ║   ╚══╣ ║║ ║ ║ ║  ║ ║  ║ ╚══╗
//     ║ ║  ║ ╔═╗ ║║ ╔══╝ ║ ╠══╗   ║║ ║ ║ ║  ║ ║  ║ ╔══╝
//     ║ ║  ║ ║ ║ ║║ ╚═══╗║ ║  ╚═╗ ║║ ╚═╝ ║  ║ ║  ║ ╚═══╗
//     ╚═╝  ╚═╝ ╚═╝╚═════╝╚═╝    ╚═╝╚═════╝  ╚═╝  ╚═════╝
//   Copyright by TheNote! Not for Resale! Not for others
//                        2017-2020

namespace TheNote\SnowMod;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\utils\TextFormat;

class SnowMod extends PluginBase implements Listener
{
    public $cooltime = 0;
    public $m_version = 2, $pk;
    public $denied = [];

    public function onEnable()
    {
        $this->getScheduler()->scheduleRepeatingTask(new SnowModTask ($this), 1);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("settings.json", false);
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        $settings = new Config($this->getDataFolder() . "settings.json" , Config::JSON);
        if ($command->getName() === "snowmod") {
            if (isset($args[0])) {
                if (strtolower($args[0]) === "enable") {
                    if ($sender->isOp()) {
                        $settings->set("snowallowed", true);
                        $settings->save();
                        $sender->sendMessage(TextFormat::GREEN . "The SnowMod are Enabled.");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You have no Permission to use this Command!");
                    }
                } else if (strtolower($args[0]) === "disable") {
                    if ($sender->isOp()) {
                        $settings->set("snowallowed", false);
                        $settings->save();
                        $sender->sendMessage(TextFormat::GREEN . "The SnowMod are Disabled.");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You have no Permission to use this Command!");
                    }
                }
            } else {
                $sender->sendMessage("Usage: /snowmod <disable/enable>");
            }
        }
        return true;
    }
    public function onChunkLoadEvent(ChunkLoadEvent $event)
    {
        $settings = new Config($this->getDataFolder() . "settings.json");
        $check = $settings->get("snowallowed");
        if ($check === true) {
            for ($x = 0; $x < 16; ++$x)
                for ($z = 0; $z < 16; ++$z)
                    $event->getChunk()->setBiomeId($x, $z, 12);
        } elseif ($check === false){
            for ($x = 0; $x < 16; ++$x)
                for ($z = 0; $z < 16; ++$z)
                    $event->getChunk()->setBiomeId($x, $z, 1);
        }
    }
    public function onPlayerJoinEvent(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $pk = new LevelEventPacket ();
        $pk->evid = 3001;
        $pk->data = 10000;
        $player->dataPacket($pk);
    }
    public function SnowMod()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->SnowModCreate($player);
        }
    }
    public function SnowModCreate(Player $player)
    {

        $settings = new Config($this->getDataFolder() . "settings.json");
        $check = $settings->get("snowallowed");
        $x = mt_rand($player->x - 15, $player->x + 15);
        $z = mt_rand($player->z - 15, $player->z + 15);
        $y = $player->getLevel()->getHighestBlockAt($x, $z);
        if ($check === false) {
            if ($this->cooltime < 11) {
                $this->cooltime++;
                $this->getScheduler()->scheduleDelayedTask(new SnowDestructLayer ($this, new Position ($x, $y, $z, $player->getLevel())), 20);
            }
        } elseif ($check === true) {
            if ($this->cooltime < 11) {
                $this->cooltime++;
                $this->getScheduler()->scheduleDelayedTask(new SnowCreateLayer ($this, new Position ($x, $y, $z, $player->getLevel())), 20);
            }
        }
    }
    public function SnowCreate(Position $pos)
    {
        $this->cooltime--;
        if ($pos == null)
            return;

        $down = $pos->getLevel()->getBlock($pos);
        if (!$down->isSolid())
            return;
        if ($down->getId() == Block::GRAVEL or $down->getId() == Block::COBBLESTONE or $down->getId() == 32 or $down->getId() == Block::DIAMOND_BLOCK or $down->getId() == Block::WATER or $down->getId() == Block::WOOL or $down->getId() == 44 or $down->getId() == Block::FENCE or $down->getId() == Block::STONE_BRICK_STAIRS or $down->getId() == 43 or $down->getId() == Block::FARMLAND)
            return;

        $up = $pos->getLevel()->getBlock($pos->add(0, 1, 0));
        if ($up->getId() != Block::AIR)
            return;
        $pos->getLevel()->setBlock($up, Block::get(Item::SNOW_LAYER), 0, true);
    }
    public function SnowDestruct(Position $pos)
    {
        $this->cooltime--;
        if ($pos == null)
            return;
        if ($pos->getLevel()->getBlockIdAt($pos->x, $pos->y, $pos->z) == Block::SNOW_LAYER) {
            $pos->getLevel()->setBlock($pos, Block::get(Block::AIR), 0, false);
        }
    }
}