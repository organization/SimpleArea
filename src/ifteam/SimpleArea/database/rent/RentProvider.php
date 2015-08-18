<?php

namespace ifteam\SimpleArea\database\rent;

use pocketmine\level\Level;
use ifteam\SimpleArea\event\RentAddEvent;
use ifteam\SimpleArea\event\RentDeleteEvent;
use pocketmine\Server;

class RentProvider {
	private static $instance = null;
	/**
	 *
	 * @var RentLoader
	 */
	private $rentLoader;
	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->rentLoader = new RentLoader ();
	}
	/**
	 * Add rent data
	 *
	 * @param string $level        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startY        	
	 * @param int $endY        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @param string $owner        	
	 * @param bool $isHome        	
	 * @return RentSection|NULL
	 */
	public function addRent($level, $startX, $endX, $startY, $endY, $startZ, $endZ, $areaId, $price) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		
		if ($startX > $endX) {
			$backup = $startX;
			$startX = $endX;
			$endX = $backup;
		}
		if ($startY > $endY) {
			$backup = $startY;
			$startY = $endY;
			$endY = $backup;
		}
		if ($startZ > $endZ) {
			$backup = $startZ;
			$startZ = $endZ;
			$endZ = $backup;
		}
		
		$data = [ 
				"areaId" => $areaId,
				"owner" => "",
				"rentPrice" => $price,
				"startX" => $startX,
				"endX" => $endX,
				"startY" => $startY,
				"endY" => $endY,
				"startZ" => $startZ,
				"endZ" => $endZ 
		];
		
		$rent = $this->rentLoader->addRentSection ( $level, $data );
		if (! $rent instanceof RentSection)
			return null;
		
		$event = new RentAddEvent ( $rent->getOwner (), $rent->getLevel (), $rent->getRentId () );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if ($event->isCancelled ()) {
			$this->deleteRent ( $rent->getLevel (), $rent->getId () );
			return null;
		}
		
		return $rent;
	}
	/**
	 *
	 * @param string $level        	
	 * @param string $id        	
	 */
	public function deleteRent($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		$rent = $this->getRentToId ( $level, $id );
		$event = new RentDeleteEvent ( $rent->getOwner (), $rent->getLevel (), $rent->getRentId () );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if ($event->isCancelled ())
			return;
		$this->rentLoader->deleteRentSection ( $level, $id );
	}
	/**
	 * Get all rent data of the level
	 *
	 * @param string $level        	
	 * @return array $rents
	 */
	public function getAll($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->rentLoader->getAll ( $level );
	}
	/**
	 * Get All rents info of the level
	 *
	 * @param string $level        	
	 * @return NULL|Array
	 */
	public function getRentsInfo($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->rentLoader->getRentsInfo ( $level );
	}
	/**
	 * Get rent data of the level
	 *
	 * @param string $level        	
	 * @param int $x        	
	 * @param int $y        	
	 * @param int $z        	
	 * @return RentSection|NULL
	 */
	public function getRent($level, $x, $y, $z) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->rentLoader->getRent ( $level, $x, $y, $z );
	}
	/**
	 * Get rent data of the level (using Id)
	 *
	 * @param string $level        	
	 * @param int $id        	
	 * @return RentSection|NULL
	 */
	public function getRentToId($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->rentLoader->getRentSection ( $level, $id );
	}
	/**
	 * Get checkOverlap of the level
	 *
	 * @param string $level        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startY        	
	 * @param int $endY        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @return RentSection|NULL
	 */
	public function checkOverlap($level, $startX, $endX, $startY, $endY, $startZ, $endZ) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->rentLoader->checkOverlap ( $level, $startX, $endX, $startY, $endY, $startZ, $endZ );
	}
	/**
	 * Save settings (bool is async)
	 *
	 * @param string $bool        	
	 */
	public function save($bool = false) {
		if ($this->rentLoader instanceof RentLoader)
			$this->rentLoader->save ( $bool );
	}
	/**
	 *
	 * @return RentProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}
}
?>