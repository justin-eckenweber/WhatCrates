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

use pocketmine\level\sound\Sound;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SchdowNVIDIA\WhatCrates\Commands\WhatCratesCommand;
use SchdowNVIDIA\WhatCrates\Libs\jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase {

    public $crates = array();
    public $worldsWithWhatCrates = array();
    public $existingKeys = array();

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveResource("whatcrates.yml");
        $this->saveResource("keys.yml");
        $this->saveDefaultConfig();
        $this->initWhatCrates();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("whatcrates", new WhatCratesCommand($this));
    }

    public function initWhatCrates()
    {
        $whatcrates = new Config($this->getDataFolder()."whatcrates.yml", Config::YAML);

        foreach ($whatcrates->getNested("whatcrates") as $whatcrate => $attribute) {

            $x = (string) $attribute["x"];
            $y = (string) $attribute["y"];
            $z = (string) $attribute["z"];
            $world = $attribute["world"];
            $name = (string) $whatcrate;
            $rewards = $attribute["rewards"];
            $key = $attribute["key"];

            array_push($this->crates, new WhatCrate($x, $y, $z, $world, $name, $rewards, $key));
            if(!in_array($world, $this->worldsWithWhatCrates)) {
                array_push($this->worldsWithWhatCrates, $world);
            }
            if(!in_array($key, $this->existingKeys)) {
                array_push($this->existingKeys, $key);
            }
        }
    }

    public function WhatCrateRaffle(WhatCrate $whatCrate, Player $player) {
        if($whatCrate->isOpen()) {
            return $player->sendMessage("§cSomeone is opening this WhatCrate already!");
        }

        $whatCrate->setOpen(true);
        $keys = $this->getKeysOfPlayer($player, $whatCrate->getKey());
        if($keys <= 0) {
            $whatCrate->setOpen(false);
            return $player->sendMessage("§cYou don't have a key for this WhatCrate.");
        }
        $this->removeKeysOfPlayer($player, $whatCrate->getKey(), 1);
        $rewards = $whatCrate->getRewards();
        $reward = array_rand($whatCrate->getRewards());
        $player->sendMessage("You've won: ".$rewards[$reward]);
        $whatCrate->setOpen(false);
    }

    public function getKeysOfPlayer(Player $player, string $key) {
        $keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        return $keyDB->getNested(strtolower($player->getName()).".".$key);
    }

    public function addKeysToPlayer(Player $player, string $key, int $amount) {
        $keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        $playername = strtolower($player->getName());
        if(empty($keyDB->getNested($playername.".".$key))) {
            $keyDB->setNested($playername . "." . $key, $amount);
        } else {
            $keyDB->setNested($playername . "." . $key, (int) ($keyDB->getNested($playername.".".$key) + $amount));
        }
    }

    public function removeKeysOfPlayer(Player $player, string $key, int $amount) {
        $keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        $playername = strtolower($player->getName());
        if(empty($keyDB->getNested($playername.".".$key))) {
            return;
        } else {
            $keyDB->setNested($playername . "." . $key, (int) ($keyDB->getNested($playername.".".$key) - $amount));
        }
    }

    public function reloadWhatCrates(Player $player)
    {
        $player->sendMessage("--- Reloading WhatCrates ---");
        $player->sendMessage("Clearing saved WhatCrates...");
        $this->crates = array();
        $this->worldsWithWhatCrates = array();
        $player->sendMessage("Loading all WhatCrates...");
        $this->initWhatCrates();
        $player->sendMessage("Done!");
    }

    public function openWhatCratesMenu(Player $player) {
        $form = new SimpleForm(function (Player $player, int $data = null) {
            if($data != null) {
                switch ($data) {
                    case 1:
                        $this->reloadWhatCrates($player);
                        break;
                }
            }
        });

        $form->setTitle("WhatCrates");
        $form->setContent("This UI is still WIP.");
        $form->addButton("§cClose");
        $form->addButton("Reload WhatCrates");
        $form->sendToPlayer($player);
        return $form;
    }

}