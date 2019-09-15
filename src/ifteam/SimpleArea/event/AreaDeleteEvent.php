<?php

namespace ifteam\SimpleArea\event;

use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\area\AreaSection;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class AreaDeleteEvent extends Event implements Cancellable {

    use CancellableTrait;

    public static $handlerList = null;
    public static $eventPool = [];
    public static $nextEvent = 0;
    protected $player, $world, $id, $resident;

    /**
     * __construct()
     *
     * @param string $player
     * @param string $world
     * @param string $id
     * @param array $resident
     */
    public function __construct($player, $world, $id, $resident) {
        $this->player = $player;
        $this->world = $world;
        $this->id = $id;
        $this->resident = $resident;
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
     * getWorld()
     *
     * @return string
     */
    public function getWorld() {
        return $this->world;
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
     * getResident()
     *
     * @return array $resident
     */
    public function getResident() {
        return $this->resident;
    }

    /**
     * getAreaData()
     *
     * @return AreaSection $area
     */
    public function getAreaData() {
        return AreaProvider::getInstance()->getAreaToId($this->world, $this->id);
    }

    public function getWhiteWorldData() {
        return WhiteWorldProvider::getInstance()->get($this->world);
    }
}

?>
