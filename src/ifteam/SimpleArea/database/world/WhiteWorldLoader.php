<?php

namespace ifteam\SimpleArea\database\world;

use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\utils\Config;

class WhiteWorldLoader {
	private $jsons;
	private $whiteWorlds;
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
			$filePath = Server::getInstance ()->getDataPath () . "worlds/" . $level->getFolderName () . "/options.json";
			if (isset ( $this->jsons [$level->getFolderName ()] ))
				return;
			$this->jsons [$level->getFolderName ()] = (new Config ( $filePath, Config::JSON, [ 
					"protect" => false,
					"allowOption" => [ ],
					"forbidOption" => [ ],
					"defaultAreaPrice" => 5000,
					"welcome" => "",
					"pvpAllow" => true,
					"invenSave" => true,
					"autoCreateAllow" => true,
					"manualCreate" => true,
					"areaHoldLimit" => 4,
					"defaultAreaSize" => [ 
							32,
							22 
					],
					"defaultFenceType" => [ 
							139,
							1 
					] 
			] ))->getAll ();
			$this->whiteWorlds [$level->getFolderName ()] = new WhiteWorldData ( $this->jsons [$level->getFolderName ()], $level->getFolderName () );
			return;
		}
		foreach ( Server::getInstance ()->getLevels () as $level ) {
			if (! $level instanceof Level)
				continue;
			$filePath = Server::getInstance ()->getDataPath () . "worlds/" . $level->getFolderName () . "/options.json";
			if (isset ( $this->jsons [$level->getFolderName ()] ))
				continue;
			$this->jsons [$level->getFolderName ()] = (new Config ( $filePath, Config::JSON, [ 
					"protect" => false,
					"allowOption" => [ ],
					"forbidOption" => [ ],
					"defaultAreaPrice" => 5000,
					"welcome" => "",
					"pvpAllow" => true,
					"invenSave" => true,
					"autoCreateAllow" => true,
					"manualCreate" => true,
					"areaHoldLimit" => 4,
					"defaultAreaSize" => [ 
							32,
							22 
					],
					"defaultFenceType" => [ 
							139,
							0 
					] 
			] ))->getAll ();
			$this->whiteWorlds [$level->getFolderName ()] = new WhiteWorldData ( $this->jsons [$level->getFolderName ()], $level->getFolderName () );
		}
	}
	/**
	 * Get All area data of the level
	 *
	 * @param string $level        	
	 * @return NULL|AreaSection
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
	 * getWhiteWorldData
	 *
	 * @param string $level        	
	 * @return WhiteWorldData $data | null
	 */
	public function getWhiteWorldData($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->whiteWorlds [$level] )) {
			return $this->whiteWorlds [$level];
		} else {
			return null;
		}
	}
	/**
	 * Save settings (bool is async)
	 *
	 * @param string $bool        	
	 */
	public function save($bool = false) {
		foreach ( $this->jsons as $levelName => $json ) {
			$filePath = Server::getInstance ()->getDataPath () . "worlds/" . $levelName . "/options.json";
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