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

class SnowMod extends PluginBase implements Listener
{
    public $cooltime = 0;
    public $m_version = 2, $pk;
    public $denied = [];

    public function onEnable()
    {
        $this->getScheduler()->scheduleRepeatingTask(new SnowModTask ($this), 1);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onChunkLoadEvent(ChunkLoadEvent $event)
    {
        for ($x = 0; $x < 16; ++$x)
            for ($z = 0; $z < 16; ++$z)
                $event->getChunk()->setBiomeId($x, $z, 12);
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
        $x = mt_rand($player->x - 15, $player->x + 15);
        $z = mt_rand($player->z - 15, $player->z + 15);
        $y = $player->getLevel()->getHighestBlockAt($x, $z);

        if ($this->cooltime < 11) {
            $this->cooltime++;
            $this->getScheduler()->scheduleDelayedTask(new SnowCreateLayer ($this, new Position ($x, $y, $z, $player->getLevel())), 20);
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
}