<?php

namespace ifteam\SimpleArea\event;

use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\database\rent\RentSection;

class RentBuyEvent extends Event implements Cancellable {
	public static $handlerList = null;
	public static $eventPool = [ ];
	public static $nextEvent = 0;
	protected $player, $level, $id;
	private $buyer;
	/**
	 * __construct()
	 *
	 * @param string $player        	
	 * @param string $level        	
	 * @param string $id        	
	 * @param string $buyer        	
	 */
	public function __construct($player, $level, $id, $buyer) {
		$this->player = $player;
		$this->level = $level;
		$this->id = $id;
		$this->buyer = $buyer;
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
		return RentProvider::getInstance ()->getRentToId ( $this->level, $this->id );
	}
	public function getBuyer() {
		return $this->buyer;
	}
}

?>