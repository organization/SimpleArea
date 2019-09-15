<?php

namespace ifteam\SimpleArea\database\rent;

use ifteam\SimpleArea\event\RentBuyEvent;
use ifteam\SimpleArea\event\RentOutEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class RentSection {
    private $data = [];
    private $world;

    public function __construct(array &$data, $world) {
        $basicElements = [
                "areaId",
                "rentId",
                "owner",
                "rentPrice",
                "startX",
                "startY",
                "startZ",
                "endX",
                "endY",
                "endZ"
        ];
        foreach ($basicElements as $element)
            if (!isset ($data [$element]))
                return null;

        if (!isset ($data ["welcome"]))
            $data ["welcome"] = "";
        if (!isset ($data ["buySignX"]))
            $data ["buySignX"] = null;
        if (!isset ($data ["buySignY"]))
            $data ["buySignY"] = null;
        if (!isset ($data ["buySignZ"]))
            $data ["buySignZ"] = null;

        if ($data ["owner"] !== "")
            $data ["owner"] = strtolower($data ["owner"]);

        $this->world = $world;
        $this->data = &$data;
    }

    public function out() {
        $event = new RentOutEvent ($this->getOwner(), $this->getWorld(), $this->getRentId());
        $event->call();
        //Server::getInstance ()->getPluginManager ()->callEvent ( $event );
        if (!$event->isCancelled())
            $this->data ["owner"] = "";
    }

    /**
     * Get rent owner
     *
     * @return string
     */
    public function getOwner() {
        return $this->data ["owner"];
    }

    /**
     * Get world name
     *
     * @return string $world
     */
    public function getWorld() {
        return $this->world;
    }

    /**
     * Get rent id
     *
     * @return int
     */
    public function getRentId() {
        return $this->data ["rentId"];
    }

    public function buy($player) {
        if ($player instanceof Player)
            $player = $player->getName();
        $player = strtolower($player);

        $event = new RentBuyEvent ($this->getOwner(), $this->getWorld(), $this->getRentId(), $player);
        $event->call();
        //Server::getInstance ()->getPluginManager ()->callEvent ( $event );
        if (!$event->isCancelled())
            $this->setOwner($player);
    }

    /**
     * Set rent owner
     *
     * @param string $name
     */
    public function setOwner($name) {
        if ($name instanceof Player)
            $name = $name->getName();
        $name = strtolower($name);
        $this->data ["owner"] = strtolower($name);
    }

    /**
     * Get rent data
     *
     * @param string $key
     * @return array
     */
    public function get($key) {
        if (!isset ($this->data [$key]))
            return null;
        return $this->data [$key];
    }

    /**
     * Get rent data
     *
     * @return array
     */
    public function getAll() {
        return $this->data;
    }

    /**
     * Get welcome message
     *
     * @return string
     */
    public function getWelcome() {
        return $this->data ["welcome"];
    }

    /**
     * Get area id
     *
     * @return int
     */
    public function getAreaId() {
        return $this->data ["areaId"];
    }

    /**
     * Get rent price
     *
     * @return int
     */
    public function getPrice() {
        return $this->data ["rentPrice"];
    }

    /**
     * Get rent center pos
     *
     * @return Vector3
     */
    public function getCenter() {
        $xSize = $this->data ["endX"] - $this->data ["startX"];
        $zSize = $this->data ["endZ"] - $this->data ["startZ"];
        $x = $this->data ["endX"] + ($xSize / 2);
        $z = $this->data ["endZ"] + ($zSize / 2);
        $y = $this->data ["startY"];
        return new Vector3 ($x, $y, $z);
    }

    /**
     * Get buy sign pos
     *
     * @return Vector3
     */
    public function getBuySignPos() {
        return new Vector3 ($this->data ["buySignX"], $this->data ["buySignY"], $this->data ["buySignZ"]);
    }

    /**
     * Owner check
     *
     * @return boolean
     */
    public function isOwner($name) {
        if ($name instanceof Player)
            $name = $name->getName();
        $name = strtolower($name);
        return $this->data ["owner"] == strtolower($name) ? true : false;
    }

    /**
     *
     * @return boolean
     */
    public function isCanBuy() {
        return ($this->data ["owner"] == "") ? true : false;
    }

    /**
     *
     * @return boolean
     */
    public function isBuySingNull() {
        if ($this->data ["buySignX"] == null and $this->data ["buySignY"] == null and $this->data ["buySignZ"] == null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set rent welcome message
     *
     * @param string $string
     */
    public function setWelcome($string) {
        $this->data ["welcome"] = mb_convert_encoding($string, "UTF-8");
    }

    /**
     * Set rent price
     *
     * @param int $price
     */
    public function setPrice($price) {
        $this->data ["rentPrice"] = $price;
    }

    /**
     * Set buy sign pos
     *
     * @param int $x
     * @param int $y
     * @param int $z
     */
    public function setBuySignPos($x, $y, $z) {
        $this->data ["buySignX"] = $x;
        $this->data ["buySignY"] = $y;
        $this->data ["buySignZ"] = $z;
    }

    /**
     * Self rent delete
     */
    public function deleteRent() {
        RentProvider::getInstance()->deleteRent($this->world, $this->getRentId());
    }
}