<?php

namespace ifteam\SimpleArea\event;

use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\area\AreaSection;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class AreaModifyEvent extends Event implements Cancellable {

    use CancellableTrait;

    /**
     * Actual Denied Event Type
     */
    const PLACE_PROTECT_AREA = 1;
    const PLACE_FORBID = 1;
    const PLACE_WHITE = 2;
    const PLACE_WHITE_FORBID = 3;
    const BREAK_PROTECT_AREA = 4;
    const BREAK_FORBID = 5;
    const BREAK_WHITE = 6;
    const BREAK_WHITE_FORBID = 7;
    const SIGN_CHANGE_PROTECT_AREA = 8;
    // -------------------------------------
    const SIGN_CHANGE_FORBID = 9;
    const SIGN_CHANGE_WHITE = 10;
    const SIGN_CHANGE_WHITE_FORBID = 11;
    public static $handlerList = null;
    // -------------------------------------
    public static $eventPool = [];
    public static $nextEvent = 0;
    protected $player, $world, $id;
    private $block, $type;

    /**
     *
     * @param string $player
     * @param string $world
     * @param int $id
     * @param Block $block
     * @param int $type
     */
    public function __construct($player, $world, $id, Block $block, $type) {
        $this->player = $player;
        $this->world = $world;
        $this->id = $id;
        $this->block = $block;
        $this->type = $type;
    }

    public function getBlock() {
        return $this->block;
    }

    public function getType() {
        return $this->type;
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