<?php

namespace ifteam\SimpleArea\event;

use pocketmine\event\Event;
use pocketmine\event\Cancellable;

class AreaTaxChangeEvent extends Event implements Cancellable {
	public static $handlerList = null;
	public static $eventPool = [ ];
	public static $nextEvent = 0;
	private $levelName, $price;
	public function __construct($levelName, $price) {
		$this->levelName = $levelName;
		$this->price = $price;
	}
	public function getLevelName() {
		return $this->levelName;
	}
	public function getPrice() {
		return $this->price;
	}
	public function setPrice() {
		return $this->price;
	}
}

?>