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

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class WhatCrateRaffle extends Task {

    private $spinningTimes;
    private $whatCrate;
    private $plugin;
    private $player;
    private $rewards;
    private $currentReward;

    public function __construct(Main $plugin, Player $player, int $spinningTimes, WhatCrate $whatCrate)
    {
        $this->spinningTimes = $spinningTimes;
        $this->whatCrate = $whatCrate;
        $this->plugin = $plugin;
        $this->player = $player;
        $this->rewards = $whatCrate->getRewards();
        $this->currentReward = "Loading...";
    }

    public function onRun(int $currentTick)
    {
        $this->spinningTimes--;
        if($this->spinningTimes >= 0) {
            $this->currentReward = array_rand($this->rewards);
            $this->player->sendMessage("§7".$this->rewards[$this->currentReward]);
        } else {
            $this->player->getLevel()->addSound(new BlazeShootSound($this->player->asVector3()));
            $this->plugin->rewardPlayer($this->player, $this->rewards[$this->currentReward], $this->whatCrate);
            //$this->player->sendMessage("You've won: " . $this->rewards[$this->currentReward]);
            $this->whatCrate->setOpen(false);
            $this->getHandler()->cancel();
        }
    }

}