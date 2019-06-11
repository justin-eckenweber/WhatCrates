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

use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\Sound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use SchdowNVIDIA\WhatCrates\Commands\WhatCratesCommand;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase {

    public $crates = array();
    public $worldsWithWhatCrates = array();
    public $existingKeys = array();

    // Current Config Versions
    public $cfgVersion = 0;
    public $messagesVersion = 1;
    public $whatCratesVersion = 0;

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveResource("whatcrates.yml");
        $this->saveResource("keys.yml");
        $this->saveResource("messages.yml");
        $this->saveDefaultConfig();
        $this->cfgChecker();
        $this->initWhatCrates();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("whatcrates", new WhatCratesCommand($this));
        $this->keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        $this->messages = new Config($this->getDataFolder()."messages.yml", Config::YAML);
    }

    public function cfgChecker () {
        // config.yml
        if(($this->getConfig()->get("version")) < $this->cfgVersion || !($this->getConfig()->exists("version"))) {
            $this->getLogger()->critical("Your config.yml is outdated.");
            $this->getLogger()->info("Loading new config...");
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "old_config.yml");
            $this->saveResource("config.yml");
            $this->getLogger()->notice("Done: The old config has been saved as \"old_config.yml\" and the new config has been successfully loaded.");
        };
        // messages.yml
        $messages = new Config($this->getDataFolder()."messages.yml", Config::YAML);
        if(($messages->get("version")) < $this->messagesVersion || !($messages->exists("version"))) {
            $this->getLogger()->critical("Your messages.yml is outdated.");
            $this->getLogger()->info("Loading new messages...");
            rename($this->getDataFolder() . "messages.yml", $this->getDataFolder() . "old_messages.yml");
            $this->saveResource("messages.yml");
            $this->getLogger()->notice("Done: The old messages has been saved as \"old_messages.yml\" and the new messages has been successfully loaded.");
        };
        // Currently don't need to check whatCratesVersion. there will be no update for a long time.
    }

    public function sendFloatingText(Player $player, bool $invisible) {
        foreach ($this->crates as $whatcrate) {
            if($whatcrate instanceof WhatCrate) {
                $text = $whatcrate->getFloatingText();
                $keys = $this->getKeysOfPlayer($player, $whatcrate->getKey());
                if($keys > 0) {
                    $text->setTitle($whatcrate->getName() . " (" . $keys . ")");
                } else {
                    $text->setTitle($whatcrate->getName());
                }
                $text->setInvisible($invisible);
                if($text instanceof FloatingTextParticle) {
                    foreach ($text->encode() as $pckg) {
                        $player->dataPacket($pckg);
                    }
                }
            }
        }
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
            $textPos = new Vector3(intval($x) + 0.5, intval($y) + 1, intval($z) + 0.5);
            $floatingText = new FloatingTextParticle($textPos, '', $name, TextFormat::RESET);

            array_push($this->crates, new WhatCrate($x, $y, $z, $world, $name, $rewards, $key, $floatingText));
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
            return $player->sendMessage($this->messages->get('already-open'));
        }

        $whatCrate->setOpen(true);
        $keys = $this->getKeysOfPlayer($player, $whatCrate->getKey());
        if($keys <= 0) {
            $whatCrate->setOpen(false);
            return $player->sendMessage($this->messages->get('dont-have-key'));
        }
        $this->removeKeysOfPlayer($player, $whatCrate->getKey(), 1);
        $this->getScheduler()->scheduleRepeatingTask(new WhatCrateRaffle($this, $player, intval($this->getConfig()->get("spinning-times")), $whatCrate), intval($this->getConfig()->get("raffle-speed")));
    }

    public function rewardPlayer(Player $player, string $reward, WhatCrate $whatCrate) {
        $rwd = explode(":", $reward);
        switch ($rwd[0]) {
            case "cmd":
                $player->sendMessage($this->replaceMessagePlaceholders($this->messages->get('you-won'), $rwd[1]));
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $this->replaceCommandPlaceholders($rwd[2], $player, $whatCrate));
                break;

            case "item":
                $player->sendMessage($this->replaceMessagePlaceholders($this->messages->get('you-won'), $rwd[1]));
                $player->getInventory()->addItem(Item::get(intval($rwd[2]), intval($rwd[3]), intval($rwd[4])));
                break;

            default:
                $player->sendMessage("Invalid reward type! ($rwd[0]) ($reward)");
                break;
        }
    }
    // All placeholder replacers
    public function replaceCommandPlaceholders(string $string, Player $player, WhatCrate $whatCrate) {
        $string = str_replace("{USERNAME}", $player->getName(), $string);
        $string = str_replace("{CRATE}", $whatCrate->getName(), $string);
        return $string;
    }

    public function replaceMessagePlaceholders(string $toReplace, string $reward) {
        $toReplace = str_replace("{REWARD}", $reward, $toReplace);
        return $toReplace;
    }

    public function getKeysOfPlayer(Player $player, string $key) {
        //$keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        return $this->keyDB->getNested(strtolower($player->getName()).".".$key);
    }

    public function addKeysToPlayer(Player $player, string $key, int $amount) {
       // $keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        $playername = strtolower($player->getName());
        if(empty($this->keyDB->getNested($playername.".".$key))) {
            $this->keyDB->setNested($playername . "." . $key, $amount);
        } else {
            $this->keyDB->setNested($playername . "." . $key, (int) ($this->keyDB->getNested($playername.".".$key) + $amount));
        }
        $this->keyDB->save();
    }

    public function removeKeysOfPlayer(Player $player, string $key, int $amount) {
        //$keyDB = new Config($this->getDataFolder()."keys.yml", Config::YAML);
        $playername = strtolower($player->getName());
        //if(empty($keyDB->getNested($playername.".".$key))) {
        //    return;
        //} else {
        $this->keyDB->setNested($playername . "." . $key, (int) ($this->keyDB->getNested($playername.".".$key) - $amount));
        $this->keyDB->save();
        //}
    }

    public function reloadWhatCrates(Player $player)
    {
        $player->sendMessage("--- Reloading WhatCrates ---");
        $player->sendMessage("Reloading Crates...");
        $this->crates = array();
        $this->worldsWithWhatCrates = array();
        $this->initWhatCrates();
        $player->sendMessage("Reloading key file...");
        $this->keyDB->reload();
        $player->sendMessage("Reloading config...");
        $this->getConfig()->reload();
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