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

  /* solo */
  private $playerlist = [];

	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->server = Server::getInstance ();
		
		$this->init ();

	}


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


	public function getArea($level, $x, $z, $player = null) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
     if($player !== null) {
       if(isset($this->playerlist[$player])){
         $id = $this->playerlist[$player];
         if(isset($this->areas[$level][$id])){
           $area = $this->areas[$level][$id];
           if ($area->get('startX') <= $x && $area ->get('endX') >= $x && $area->get('startZ') <= $z && $area->get('endZ') >= $z){
             return $area;
           }
         }
       }
     }
		if (isset($this->areas[$level])) {
		  foreach($this->areas[$level] as $id => $area)
				if ($area->get('startX') <= $x && $area ->get('endX') >= $x && $area->get('startZ') <= $z && $area->get('endZ') >= $z){
            if($player !== null){
              $this->playerlist[$player] = $id;
            }
					return $area;
          }
     }
		if (! isset ( $this->jsons [$level] ))
			return null;
		foreach ( $this->jsons[$level] as $id => $area){
			if (isset ( $area ["startX"] ))
				if ($area ["startX"] <= $x && $area ["endX"] >= $x && $area ["startZ"] <= $z && $area ["endZ"] >= $z)
					return $this->getAreaSection ( $level, $area ["id"] );
     }
		return null;
	}


	public function checkOverlap($level, $startX, $endX, $startZ, $endZ, $pass = null) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (! isset ( $this->jsons [$level] ))
			return null;
		foreach ( $this->jsons [$level] as $id => $area ) {
			if (isset ( $area ["startX"] )) {
				if ($pass !== null && $pass == $area ["id"])
					continue;
				if ((($area ["startX"] <= $startX && $area ["endX"] >= $startX) || ($area ["startX"] <= $endX and $area ["endX"] >= $endX)) && (($area ["startZ"] <= $startZ && $area ["endZ"] >= $startZ) || ($area ["endZ"] <= $endZ && $area ["endZ"] >= $endZ)))
					return $this->getAreaSection ( $level, $area ["id"] );
			}
		}
		return null;
	}


	public function getAll($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->jsons [$level] )) {
			return $this->jsons [$level];
		} else {
			return null;
		}
	}


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


	public function get($level, $key) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->jsons [$level] [$key] )) {
			return $this->jsons [$level] [$key];
		} else {
			return null;
		}
	}


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


	public function deleteAreaSection($level, $id) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->areas [$level] [$id] ))
			unset ( $this->areas [$level] [$id] );
		if (isset ( $this->jsons [$level] [$id] ))
			unset ( $this->jsons [$level] [$id] );
	}


	public function getLevelData($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->jsons [$level] ))
			return $this->jsons [$level];
		return null;
	}


	public function save($bool = false) {
		foreach ( $this->jsons as $levelName => $json ) {
			$filePath = $this->server->getDataPath () . "worlds/" . $levelName . "/protects.json";
			$config = new Config ( $filePath, Config::JSON );
			$config->setAll ( $json );
			$config->save ( $bool );
    }
    $this->playerlist = [];
	}


	public static function getInstance() {
		return static::$instance;
	}
}

?>