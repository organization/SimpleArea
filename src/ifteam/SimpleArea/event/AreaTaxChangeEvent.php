<?php

namespace ifteam\SimpleArea\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class AreaTaxChangeEvent extends Event implements Cancellable {

	use CancellableTrait;

	public static $handlerList = null;
	public static $eventPool = [];
	public static $nextEvent = 0;
	private $worldName, $price;

	public function __construct($worldName, $price) {
		$this->worldName = $worldName;
		$this->price = $price;
	}

	public function getWorldName() {
		return $this->worldName;
	}

	public function getPrice() {
		return $this->price;
	}

	public function setPrice() {
		return $this->price;
	}
}

?>