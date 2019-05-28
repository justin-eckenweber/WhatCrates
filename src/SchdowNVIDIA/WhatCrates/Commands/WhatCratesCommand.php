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

namespace SchdowNVIDIA\WhatCrates\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use SchdowNVIDIA\WhatCrates\Main;

class WhatCratesCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin)
    {
        parent::__construct("whatcrates", "WhatCrates Command", "/whatcrates", ["wcrates", "wcts"]);
        $this->setPermission("whatcrates");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if($sender instanceof Player) {
            if ($sender->hasPermission("whatcrates")) {
                $this->plugin->openWhatCratesMenu($sender);
        } else {
                $sender->sendMessage("§cYou're not allowed to use that!");
            }
        } else {
            $sender->sendMessage("§cRun this command in-game!");
        }
    }

}