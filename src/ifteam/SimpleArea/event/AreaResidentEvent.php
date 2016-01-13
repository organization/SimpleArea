<?php

namespace ifteam\SimpleArea\event;

use ifteam\SimpleArea\database\area\AreaProvider;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use ifteam\SimpleArea\database\area\AreaSection;

class AreaResidentEvent extends Event implements Cancellable {
	public static $handlerList = null;
	public static $eventPool = [ ];
	public static $nextEvent = 0;
	protected $player, $level, $id;
	private $added = [ ];
	private $deleted = [ ];
	/**
	 *
	 * @param Player $player        	
	 * @param Level $level        	
	 * @param string $id        	
	 */
	public function __construct($player, $level, $id, $added = [], $deleted = []) {
		$this->player = $player;
		$this->level = $level;
		$this->id = $id;
		$this->added = $added;
		$this->deleted = $deleted;
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
		return AreaProvider::getInstance ()->getAreaToId ( $this->level, $this->id );
	}
	/**
	 *
	 * @return array
	 */
	public function getAdded() {
		return $this->added;
	}
	/**
	 *
	 * @return array
	 */
	public function getDeleted() {
		return $this->deleted;
	}
}

?>