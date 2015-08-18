<?php

namespace ifteam\SimpleArea\database\rent;

use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\Server;

class RentLoader {
	private $rents;
	private $jsons;
	private static $instance = null;
	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->init ();
	}
	/**
	 * Create a default setting
	 */
	public function init($levelName = null) {
		if ($levelName !== null) {
			$level = Server::getInstance ()->getLevelByName ( $levelName );
			if (! $level instanceof Level)
				return;
			$filePath = Server::getInstance ()->getDataPath () . "worlds/" . $level->getFolderName () . "/rents.json";
			if (isset ( $this->jsons [$level->getFolderName ()] ))
				return;
			$this->jsons [$level->getFolderName ()] = (new Config ( $filePath, Config::JSON, [ 
					"rentIndex" => 0 
			] ))->getAll ();
			return;
		}
		foreach ( Server::getInstance ()->getLevels () as $level ) {
			if (! $level instanceof Level)
				continue;
			$filePath = Server::getInstance ()->getDataPath () . "worlds/" . $level->getFolderName () . "/rents.json";
			if (isset ( $this->jsons [$level->getFolderName ()] ))
				continue;
			$this->jsons [$level->getFolderName ()] = (new Config ( $filePath, Config::JSON, [ 
					"rentIndex" => 0 
			] ))->getAll ();
		}
	}
	/**
	 * Get rent data of the level (using x, y, z)
	 *
	 * @param string $level        	
	 * @param int $x        	
	 * @param int $y        	
	 * @param int $z        	
	 * @return NULL|RentSection
	 */
	public function getRent($level, $x, $y, $z) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (! isset ( $this->jsons [$level] ))
			return null;
		foreach ( $this->jsons [$level] as $id => $rent )
			if (isset ( $rent ["startX"] ))
				if ($rent ["startX"] <= $x and $rent ["endX"] >= $x and $rent ["startY"] <= $y and $rent ["endY"] >= $y and $rent ["startZ"] <= $z and $rent ["endZ"] >= $z)
					return $this->getRentSection ( $level, $rent ["rentId"] );
		return null;
	}
	/**
	 *
	 * @param string $level        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startY        	
	 * @param int $endY        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @return NULL|RentSection
	 */
	public function checkOverlap($level, $startX, $endX, $startY, $endY, $startZ, $endZ) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		foreach ( $this->jsons [$level] as $id => $rent )
			if (isset ( $rent ["startX"] ))
				if ((($rent ["startX"] <= $startX and $rent ["endX"] >= $startX) or ($rent ["startX"] <= $endX and $rent ["endX"] >= $endX)) and (($rent ["startY"] <= $startY and $rent ["endY"] >= $startY) or ($rent ["startY"] <= $endY and $rent ["endY"] >= $endY)) and (($rent ["startZ"] <= $startZ and $rent ["endZ"] >= $startZ) or ($rent ["endZ"] <= $endZ and $rent ["endZ"] >= $endZ)))
					return $this->getRentSection ( $level, $rent ["rentId"] );
		return null;
	}
	/**
	 * Get All rent data of the level
	 *
	 * @param string $level        	
	 * @return NULL|Array
	 */
	public function getAll($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->jsons [$level] )) {
			return $this->jsons [$level];
		} else {
			return null;
		}
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
		if (! isset ( $this->jsons [$level] ))
			return null;
		$info = [ 
				"userRent" => 0,
				"buyableRent" => 0 
		];
		foreach ( $this->jsons [$level] as $id => $rent ) {
			if (! isset ( $rent ["owner"] ))
				continue;
			if ($rent ["owner"] == "") {
				++ $info ["buyableRent"];
			} else {
				++ $info ["userRent"];
			}
		}
		return $info;
	}
	/**
	 * Get rent data of the level using key
	 *
	 * @param string $level        	
	 * @return NULL|Array
	 */
	public function get($level, $key) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->jsons [$level] [$key] )) {
			return $this->jsons [$level] [$key];
		} else {
			return null;
		}
	}
	/**
	 *
	 * @param int $level        	
	 * @param int $id        	
	 * @return RentSection|NULL
	 */
	public function getRentSection($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->rents [$level] [$id] )) {
			return $this->rents [$level] [$id];
		} else {
			
			if (! isset ( $this->jsons [$level] [$id] ))
				return null;
			
			$rentSection = new RentSection ( $this->jsons [$level] [$id], $level );
			
			if ($rentSection == null) {
				unset ( $this->jsons [$level] [$id] );
				return null;
			} else {
				$this->rents [$level] [$id] = $rentSection;
				return $rentSection;
			}
		}
	}
	/**
	 * addRentSection (addRent)
	 *
	 * @param string $level        	
	 * @param array $data        	
	 * @return NULL|RentSection
	 */
	public function addRentSection($level, array $data) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		
		$isOverlap = $this->checkOverlap ( $level, $data ["startX"], $data ["endX"], $data ["startY"], $data ["endY"], $data ["startZ"], $data ["endZ"] );
		if ($isOverlap !== null)
			return null;
		
		$data ["rentId"] = $this->jsons [$level] ["rentIndex"] ++;
		$this->jsons [$level] [$data ["rentId"]] = $data;
		
		$rentSection = new RentSection ( $this->jsons [$level] [$data ["rentId"]], $level );
		if ($rentSection == null) {
			unset ( $this->jsons [$level] [$data ["rentId"]] );
			return null;
		} else {
			$this->rents [$level] [$data ["rentId"]] = $rentSection;
			return $rentSection;
		}
	}
	public function deleteRentSection($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->rents [$level] [$id] ))
			unset ( $this->rents [$level] [$id] );
		if (isset ( $this->jsons [$level] [$id] ))
			unset ( $this->jsons [$level] [$id] );
	}
	/**
	 * Get level data
	 *
	 * @param string $level        	
	 */
	public function getLevelData($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->jsons [$level] ))
			return $this->jsons [$level];
		return null;
	}
	/**
	 * Save settings (bool is async)
	 *
	 * @param string $bool        	
	 */
	public function save($bool = false) {
		foreach ( $this->jsons as $levelName => $json ) {
			$filePath = Server::getInstance ()->getDataPath () . "worlds/" . $levelName . "/rents.json";
			$config = new Config ( $filePath, Config::JSON );
			$config->setAll ( $json );
			$config->save ( $bool );
		}
	}
	/**
	 *
	 * @return RentLoader
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>