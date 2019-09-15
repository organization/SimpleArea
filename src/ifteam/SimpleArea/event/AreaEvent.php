<?php

namespace ifteam\SimpleArea\event;

use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\area\AreaSection;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class AreaEvent extends Event implements Cancellable {

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
     * getAreaId()
     *
     * @return string $id
     */
    public function getAreaId() {
        return $this->id;
    }

    /**
     * getAreaData()
     *
     * @return AreaSection $area
     */
    public function getAreaData() {
        return AreaProvider::getInstance()->getAreaToId($this->world, $this->id);
    }
}

?>