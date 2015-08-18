<?php

namespace ifteam\SimpleArea\database\area;

use pocketmine\level\Level;
use pocketmine\Server;
use ifteam\SimpleArea\event\AreaAddEvent;
use ifteam\SimpleArea\event\AreaDeleteEvent;

class AreaProvider {
	private static $instance = null;
	/**
	 *
	 * @var AreaLoader
	 */
	private $areaLoader;
	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->areaLoader = new AreaLoader ();
	}
	/**
	 * Add area data
	 *
	 * @param string $level        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @param string $owner        	
	 * @param bool $isHome        	
	 * @param bool $fenceSet        	
	 * @return AreaSection|NULL
	 */
	public function addArea($level, $startX, $endX, $startZ, $endZ, $owner, $isHome, $fenceSet = true) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		
		if ($startX > $endX) {
			$backup = $endX;
			$endX = $startX;
			$startX = $backup;
		}
		if ($startZ > $endZ) {
			$backup = $endZ;
			$endZ = $startZ;
			$startZ = $backup;
		}
		
		$data = [ 
				"owner" => "",
				"resident" => [ ],
				"isHome" => $isHome,
				"startX" => $startX,
				"endX" => $endX,
				"startZ" => $startZ,
				"endZ" => $endZ 
		];
		
		$area = $this->areaLoader->addAreaSection ( $level, $data );
		if (! $area instanceof AreaSection)
			return null;
		
		if ($isHome and $fenceSet)
			$area->setFence ();
		
		if ($owner != "")
			$area->setOwner ( $owner );
		
		$event = new AreaAddEvent ( $area->getOwner (), $area->getLevel (), $area->getId () );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if ($event->isCancelled ()) {
			$this->deleteArea ( $area->getLevel (), $area->getId () );
			return null;
		}
		
		return $area;
	}
	/**
	 *
	 * @param string $level        	
	 * @param string $id        	
	 */
	public function deleteArea($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		$area = $this->getAreaToId ( $level, $id );
		
		if ($area->isHome ())
			$area->removeFence ();
		
		if ($area instanceof AreaSection) {
			$event = new AreaDeleteEvent ( $area->getOwner (), $area->getLevel (), $area->getId (), $area->getResident () );
			Server::getInstance ()->getPluginManager ()->callEvent ( $event );
			if ($event->isCancelled ())
				return;
		}
		$this->areaLoader->deleteAreaSection ( $level, $id );
	}
	/**
	 * Get all area data of the level
	 *
	 * @param string $level        	
	 * @return array $areas
	 */
	public function getAll($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->areaLoader->getAll ( $level );
	}
	/**
	 * Get All areas info of the level
	 *
	 * @param string $level        	
	 * @return NULL|Array
	 */
	public function getAreasInfo($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->areaLoader->getAreasInfo ( $level );
	}
	/**
	 * Get area data of the level
	 *
	 * @param string $level        	
	 * @param int $x        	
	 * @param int $z        	
	 * @return AreaSection|NULL
	 */
	public function getArea($level, $x, $z) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->areaLoader->getArea ( $level, $x, $z );
	}
	/**
	 * Get area data of the level (using Id)
	 *
	 * @param string $level        	
	 * @param int $id        	
	 * @return AreaSection $data | null
	 */
	public function getAreaToId($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->areaLoader->getAreaSection ( $level, $id );
	}
	/**
	 * Get checkOverlap of the level
	 *
	 * @param string $level        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @return AreaSection|NULL
	 */
	public function checkOverlap($level, $startX, $endX, $startZ, $endZ, $pass = null) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		return $this->areaLoader->checkOverlap ( $level, $startX, $endX, $startZ, $endZ, $pass );
	}
	/**
	 * Resize the area
	 *
	 * @param string $level        	
	 * @param int $id        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @param bool $resetFence        	
	 * @return boolean
	 */
	public function resizeArea($level, $id, $startX = 0, $endX = 0, $startZ = 0, $endZ = 0, $resetFence = true) {
		$area = $this->getAreaToId ( $level, $id );
		if (! $area instanceof AreaSection)
			return false;
		
		$rstartX = $area->get ( "startX" ) - $startX;
		$rendX = $area->get ( "endX" ) + $endX;
		$rstartZ = $area->get ( "startZ" ) - $startZ;
		$rendZ = $area->get ( "endZ" ) + $endZ;
		
		$getOverlap = $this->checkOverlap ( $level, $rstartX, $rendX, $rstartZ, $rendZ, $id );
		if ($getOverlap instanceof AreaSection)
			return false;
		
		if ($resetFence)
			$area->removeFence ();
		
		$area->set ( "startX", $rstartX );
		$area->set ( "startZ", $rstartZ );
		$area->set ( "endX", $rendX );
		$area->set ( "endZ", $rendZ );
		
		if ($resetFence)
			$area->setFence ();
		return true;
	}
	/**
	 * Save settings (bool is async)
	 *
	 * @param string $bool        	
	 */
	public function save($bool = false) {
		if ($this->areaLoader instanceof AreaLoader)
			$this->areaLoader->save ( $bool );
	}
	/**
	 *
	 * @return AreaProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>