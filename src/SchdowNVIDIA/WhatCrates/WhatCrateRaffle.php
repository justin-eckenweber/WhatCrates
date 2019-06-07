<?php

/*
    WhatCrates:

    Copyright (C) 2019 SchdowNVIDIA
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace SchdowNVIDIA\WhatCrates;

use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\Sound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class WhatCrateRaffle extends Task {

    private $spinningTimes;
    private $whatCrate;
    private $plugin;
    private $player;
    private $rewards;
    private $currentReward;
    private $lastRaffleNumber;

    public function __construct(Main $plugin, Player $player, int $spinningTimes, WhatCrate $whatCrate)
    {
        $this->spinningTimes = $spinningTimes;
        $this->whatCrate = $whatCrate;
        $this->plugin = $plugin;
        $this->player = $player;
        $this->rewards = $whatCrate->getRewards();
        $this->currentReward = "Loading...";
        $this->lastRaffleNumber = -100;
    }

    public function onRun(int $currentTick)
    {
        $this->spinningTimes--;
        if($this->spinningTimes >= 0) {
            $raffleNumber = array_rand($this->rewards);
            while ($raffleNumber === $this->lastRaffleNumber) {
                $raffleNumber = array_rand($this->rewards);
            }
            $this->lastRaffleNumber = $raffleNumber;
            $this->currentReward = $raffleNumber;
            //$this->player->sendMessage("§7".$this->rewards[$this->currentReward]);
            $splittedReward = explode(":", $this->rewards[$this->currentReward]);
            $this->player->getLevel()->addSound(new ClickSound($this->whatCrate->getVector3()));
            $this->whatCrate->getFloatingText()->setText($splittedReward[1]);
            $this->plugin->sendFloatingText($this->player);
           // $this->player->level->addParticle(new RedstoneParticle(new Vector3($th), 1));
        } else {

            $this->whatCrate->getFloatingText()->setText("");
            $this->plugin->sendFloatingText($this->player);
            $this->player->getLevel()->addSound(new BlazeShootSound($this->whatCrate->getVector3()));
            $this->plugin->rewardPlayer($this->player, $this->rewards[$this->currentReward], $this->whatCrate);
            //$this->player->sendMessage("You've won: " . $this->rewards[$this->currentReward]);
            $this->whatCrate->setOpen(false);
            $this->getHandler()->cancel();
        }
    }

}