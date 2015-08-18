<?php

namespace ifteam\SimpleArea\database\area;

use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\level\Level;

class AreaLoader {
	private static $instance = null;
	private $areas;
	private $jsons;
	private $server;
	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->server = Server::getInstance ();
		
		$this->init ();
	}
	/**
	 * Create a default setting
	 */
	public function init($levelName = null) {
		if ($levelName !== null) {
			$level = $this->server->getLevelByName ( $levelName );
			if (! $level instanceof Level)
				return;
			$filePath = $this->server->getDataPath () . "worlds/" . $level->getFolderName () . "/protects.json";
			if (isset ( $this->jsons [$level->getFolderName ()] ))
				return;
			$this->jsons [$level->getFolderName ()] = (new Config ( $filePath, Config::JSON, [ 
					"areaIndex" => 0 
			] ))->getAll ();
			return;
		}
		foreach ( $this->server->getLevels () as $level ) {
			if (! $level instanceof Level)
				continue;
			$filePath = $this->server->getDataPath () . "worlds/" . $level->getFolderName () . "/protects.json";
			if (isset ( $this->jsons [$level->getFolderName ()] ))
				continue;
			$this->jsons [$level->getFolderName ()] = (new Config ( $filePath, Config::JSON, [ 
					"areaIndex" => 0 
			] ))->getAll ();
		}
	}
	/**
	 * Get area data of the level (using x, z)
	 *
	 * @param string $level        	
	 * @param int $x        	
	 * @param int $z        	
	 * @return NULL|AreaSection
	 */
	public function getArea($level, $x, $z) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (! isset ( $this->jsons [$level] ))
			return null;
		foreach ( $this->jsons [$level] as $id => $area )
			if (isset ( $area ["startX"] ))
				if ($area ["startX"] <= $x and $area ["endX"] >= $x and $area ["startZ"] <= $z and $area ["endZ"] >= $z)
					return $this->getAreaSection ( $level, $area ["id"] );
		return null;
	}
	/**
	 *
	 * @param string $level        	
	 * @param int $startX        	
	 * @param int $endX        	
	 * @param int $startZ        	
	 * @param int $endZ        	
	 * @param int $pass        	
	 * @return NULL|AreaSection
	 */
	public function checkOverlap($level, $startX, $endX, $startZ, $endZ, $pass = null) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (! isset ( $this->jsons [$level] ))
			return null;
		foreach ( $this->jsons [$level] as $id => $area ) {
			if (isset ( $area ["startX"] )) {
				if ($pass !== null and $pass == $area ["id"])
					continue;
				if ((($area ["startX"] <= $startX and $area ["endX"] >= $startX) or ($area ["startX"] <= $endX and $area ["endX"] >= $endX)) and (($area ["startZ"] <= $startZ and $area ["endZ"] >= $startZ) or ($area ["endZ"] <= $endZ and $area ["endZ"] >= $endZ)))
					return $this->getAreaSection ( $level, $area ["id"] );
			}
		}
		return null;
	}
	/**
	 * Get All area data of the level
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
	 * Get All areas info of the level
	 *
	 * @param string $level        	
	 * @return NULL|Array
	 */
	public function getAreasInfo($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (! isset ( $this->jsons [$level] ))
			return null;
		$info = [ 
				"userArea" => 0,
				"buyableArea" => 0,
				"adminArea" => 0 
		];
		foreach ( $this->jsons [$level] as $id => $area ) {
			if (! isset ( $area ["isHome"] ))
				continue;
			if ($area ["isHome"]) {
				if ($area ["owner"] == "") {
					++ $info ["buyableArea"];
				} else {
					++ $info ["userArea"];
				}
			} else {
				++ $info ["adminArea"];
			}
		}
		return $info;
	}
	/**
	 * Get area data of the level using key
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
	 * @return NULL|AreaSection
	 */
	public function getAreaSection($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->areas [$level] [$id] )) {
			return $this->areas [$level] [$id];
		} else {
			
			if (! isset ( $this->jsons [$level] [$id] ))
				return null;
			
			$areaSection = new AreaSection ( $this->jsons [$level] [$id], $level );
			
			if ($areaSection == null) {
				unset ( $this->jsons [$level] [$id] );
				return null;
			} else {
				$this->areas [$level] [$id] = $areaSection;
				return $areaSection;
			}
		}
	}
	/**
	 * addAreaSection (addArea)
	 *
	 * @param string $level        	
	 * @param array $data        	
	 * @param bool $fenceSet        	
	 * @return NULL|AreaSection
	 */
	public function addAreaSection($level, array $data) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		
		$isOverlap = $this->checkOverlap ( $level, $data ["startX"], $data ["endX"], $data ["startZ"], $data ["endZ"] );
		if ($isOverlap !== null)
			return null;
		
		$data ["id"] = $this->jsons [$level] ["areaIndex"] ++;
		$this->jsons [$level] [$data ["id"]] = $data;
		
		$areaSection = new AreaSection ( $this->jsons [$level] [$data ["id"]], $level );
		if ($areaSection == null) {
			unset ( $this->jsons [$level] [$data ["id"]] );
			return null;
		} else {
			$this->areas [$level] [$data ["id"]] = $areaSection;
			return $areaSection;
		}
	}
	/**
	 * deleteAreaSection (delArea)
	 *
	 * @param string $level        	
	 * @param int $id        	
	 */
	public function deleteAreaSection($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->areas [$level] [$id] ))
			unset ( $this->areas [$level] [$id] );
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
			$filePath = $this->server->getDataPath () . "worlds/" . $levelName . "/protects.json";
			$config = new Config ( $filePath, Config::JSON );
			$config->setAll ( $json );
			$config->save ( $bool );
		}
	}
	/**
	 *
	 * @return AreaLoader
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>