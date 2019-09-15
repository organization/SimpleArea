<?php

namespace ifteam\SimpleArea\event;

use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\database\rent\RentSection;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class RentOutEvent extends Event implements Cancellable {

    use CancellableTrait;

    public static $handlerList = null;
    public static $eventPool = [];
    public static $nextEvent = 0;
    protected $player, $world, $id;

    /**
     * __construct()
     *
     * @param string $player
     * @param string $world
     * @param string $id
     */
    public function __construct($player, $world, $id) {
        $this->player = $player;
        $this->world = $world;
        $this->id = $id;
    }

    /**
     * getPlayer()
     *
     * @return string
     */
    public function getPlayer() {
        return $this->player;
    }

    /**
     * getRentId()
     *
     * @return string $id
     */
    public function getRentId() {
        return $this->id;
    }

    /**
     * getAreaData()
     *
     * @return RentSection $area
     */
    public function getRentData() {
        return RentProvider::getInstance()->getRentToId($this->world, $this->id);
    }
}

?>