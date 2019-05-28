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

class WhatCrate {

    private $posX;
    private $posY;
    private $posZ;
    private $world;
    private $name;
    private $rewards;
    private $key;
    private $open;

    public function __construct(string $posX, string $posY, string $posZ, string $world, string $name, array $rewards, string $key)
    {
        $this->posX = $posX;
        $this->posY = $posY;
        $this->posZ = $posZ;
        $this->world = $world;
        $this->name = $name;
        $this->rewards = $rewards;
        $this->key = $key;
        $this->open = false;
    }

    public function getX() {
        return (string) $this->posX;
    }

    public function getY() {
        return (string) $this->posY;
    }

    public function getZ() {
        return (string) $this->posZ;
    }

    public function getWorld() {
        return (string) $this->world;
    }

    public function getCompactPosition() {
        return (string) $this->world.":".$this->posX.":".$this->posY.":".$this->posZ;
    }

    public function getName() {
        return (string) $this->name;
    }

    public function getRewards() {
        return (array) $this->rewards;
    }

    public function getKey() {
        return (string) $this->key;
    }

    public function isOpen() {
        return (boolean) $this->open;
    }

    public function setOpen(bool $isOpen) {
        $this->open = $isOpen;
    }

}