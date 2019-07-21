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

    private function replaceCommandPlaceholders(string $toReplace, Player $player, $amount, $type) {
        $toReplace = str_replace("{USERNAME}", $player->getName(), $toReplace);
        $toReplace = str_replace("{AMOUNT}", $amount, $toReplace);
        $toReplace = str_replace("{TYPE}", $type, $toReplace);
        return $toReplace;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!isset($args[0])) {
            return true;
        }
        if (!$sender->hasPermission("whatcrates")) {
            return $sender->sendMessage("§cYou're not allowed to use that!");
        }
        switch ($args[0]) {
            case "ui":
              if ($sender instanceof Player) {
              if ($sender->hasPermission("whatcrates")) {
                  $this->plugin->openWhatCratesMenu($sender);
              } else {
                  $sender->sendMessage($this->plugin->messages->get('no-permission'));
              }
              } else {
                  $sender->sendMessage("§cYou can use the UI only in-game!");
              }
        break;
            case "key":
                if(!isset($args[1])) return $sender->sendMessage($this->plugin->messages->get('key-wrong-syntax'));
                if($args[1] === "give") {
                    if(!isset($args[2])) return $sender->sendMessage($this->plugin->messages->get('key-wrong-syntax'));
                    if(!isset($args[3])) return $sender->sendMessage($this->plugin->messages->get('key-wrong-syntax'));
                    if(!isset($args[4])) return $sender->sendMessage($this->plugin->messages->get('key-wrong-syntax'));
                    $player = $args[2];
                    $type = $args[3];
                    $amount = $args[4];
                    if(!in_array($type, $this->plugin->existingKeys)) return $sender->sendMessage($this->plugin->messages->get("invalid-key-type"));

                    if($this->plugin->getServer()->getPlayer($player)) {
                        $pplayer = $this->plugin->getServer()->getPlayer($args[2]);
                        $this->plugin->addKeysToPlayer($pplayer, $type, intval($amount));
                        if(in_array($pplayer->getLevel()->getName(), $this->plugin->worldsWithWhatCrates)) {
                            $this->plugin->sendFloatingText($pplayer, false);
                        }
                        $pplayer->sendMessage($this->replaceCommandPlaceholders($this->plugin->messages->get("you-received-keys"), $pplayer, $amount, $type));
                        $sender->sendMessage($this->replaceCommandPlaceholders($this->plugin->messages->get('you-gave-keys'), $pplayer, $amount, $type));
                    }
                } else if ($args[1] === "remove") {
                    return true;
                } else if ($args[1] === "list"){
                    $sender->sendMessage($this->plugin->messages->get('key-list') . implode(", ", $this->plugin->existingKeys));
                } else {
                    $sender->sendMessage($this->plugin->messages->get('key-wrong-syntax'));
                }
                break;
            case "keyall":
                if(!isset($args[1])) return $sender->sendMessage("§cWrong Syntax! Use: §f/whatcrates keyall [type] [amount]");
                if(!isset($args[2])) return $sender->sendMessage("§cWrong Syntax! Use: §f/whatcrates keyall [type] [amount]");

                if(!$sender instanceof Player) return $sender->sendMessage("Please use that in-game");

                if(!in_array($args[1], $this->plugin->existingKeys)) return $sender->sendMessage($this->plugin->messages->get("invalid-key-type"));

                foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    $this->plugin->addKeysToPlayer($onlinePlayer, $args[1], intval($args[2]));
                    $onlinePlayer->sendMessage($this->replaceCommandPlaceholders($this->plugin->messages->get("you-received-keys"), $onlinePlayer, $args[2], $args[1]));
                    if(in_array($onlinePlayer->getLevel()->getName(), $this->plugin->worldsWithWhatCrates)) {
                        $this->plugin->sendFloatingText($onlinePlayer, false);
                    }
                }
                    $sender->sendMessage($this->replaceCommandPlaceholders($this->plugin->messages->get("you-keyall"), $sender, $args[2], $args[1]));

                break;
            default:

                break;
    }
    }


}