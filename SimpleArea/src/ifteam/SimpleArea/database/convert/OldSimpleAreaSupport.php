<?php

namespace ifteam\SimpleArea\database\convert;

use ifteam\SimpleArea\SimpleArea;
use pocketmine\utils\Config;
use pocketmine\Server;

class OldSimpleAreaSupport {
	private $plugin;
	private $server;
	public function __construct(SimpleArea $plugin) {
		$this->plugin = $plugin;
		$this->server = Server::getInstance ();
		$this->convertYMLtoJSON ();
	}
	public function convertYMLtoJSON() {
		$data = [ ];
		
		if (file_exists ( $this->plugin->getDataFolder () . "settings.yml" )) {
			$settings = (new Config ( $this->plugin->getDataFolder () . "settings.yml", Config::YAML ))->getAll ();
			if (isset ( $settings ["economy-home-price"] ))
				$data ["economy-home-price"] = $settings ["economy-home-price"];
			if (isset ( $settings ["maximum-home-limit"] ))
				$data ["maximum-home-limit"] = $settings ["maximum-home-limit"];
			if (isset ( $settings ["default-home-size"] ))
				$data ["default-home-size"] = $settings ["default-home-size"];
			if (isset ( $settings ["enable-setarea"] ))
				$data ["enable-setarea"] = $settings ["enable-setarea"];
		}
		
		$levels = [ ];
		foreach ( $this->server->getLevels () as $level )
			$levels [] = $level->getFolderName ();
		
		foreach ( $levels as $levelName ) {
			$link = $this->server->getDataPath () . "worlds/{$levelName}/options.yml";
			$jsonLink = $this->server->getDataPath () . "worlds/{$levelName}/options.json";
			if (file_exists ( $link ) and ! file_exists ( $jsonLink )) {
				$options = (new Config ( $link, Config::YAML ))->getAll ();
				if (! isset ( $options ["white-protect"] ))
					$options ["white-protect"] = true;
				if (! isset ( $options ["white-allow-option"] ))
					$options ["white-allow-option"] = [ ];
				if (! isset ( $options ["white-forbid-option"] ))
					$options ["white-forbid-option"] = [ ];
				if (! isset ( $data ["economy-home-price"] ))
					$data ["economy-home-price"] = 5000;
				if (! isset ( $options ["white-welcome"] ))
					$options ["white-welcome"] = "";
				if (! isset ( $options ["white-invensave"] ))
					$options ["white-invensave"] = true;
				if (! isset ( $options ["enable-setarea"] ))
					$options ["enable-setarea"] = true;
				if (! isset ( $data ["maximum-home-limit"] ))
					$data ["maximum-home-limit"] = 5;
				(new Config ( $jsonLink, Config::JSON, [ 
						"protect" => $options ["white-protect"],
						"allowOption" => $options ["white-allow-option"],
						"forbidOption" => $options ["white-forbid-option"],
						"defaultAreaPrice" => $data ["economy-home-price"],
						"welcome" => $options ["white-welcome"],
						"pvpAllow" => $options ["white-pvp-allow"],
						"invenSave" => $options ["white-invensave"],
						"autoCreateAllow" => $options ["enable-setarea"],
						"manualCreate" => $options ["enable-setarea"],
						"areaHoldLimit" => $data ["maximum-home-limit"],
						"defaultAreaSize" => [ 
								32,
								22 
						],
						"defaultFenceType" => [ 
								139,
								1 
						] 
				] ))->save ();
				@unlink ( $link );
			}
			
			$link = $this->server->getDataPath () . "worlds/{$levelName}/protects.yml";
			$jsonLink = $this->server->getDataPath () . "worlds/{$levelName}/protects.json";
			if (file_exists ( $link ) and ! file_exists ( $jsonLink )) {
				$protects = (new Config ( $link, Config::YAML ))->getAll ();
				$convertedData = [ 
						"areaIndex" => 0 
				];
				
				foreach ( $protects as $index => $protect ) {
					if (! isset ( $protect ["ID"] ))
						continue;
					
					if ($convertedData ["areaIndex"] < $protect ["ID"])
						$convertedData ["areaIndex"] = $protect ["ID"];
					
					$residents = $protect ["resident"];
					
					if (count ( $protect ["resident"] ) > 0 and $protect ["resident"] [0] === null) {
						$owner = "";
						$residents = [ ];
					} else {
						$owner = strtolower ( $protect ["resident"] [0] );
						$t_residents = [ ];
						foreach ( $protect ["resident"] as $t_resident )
							$t_residents [strtolower ( $t_resident )] = true;
						$residents = $t_residents;
					}
					
					if (! isset ( $protect ["startX"] ))
						continue;
					if (! isset ( $protect ["endX"] ))
						continue;
					if (! isset ( $protect ["startZ"] ))
						continue;
					if (! isset ( $protect ["endZ"] ))
						continue;
					if (! isset ( $protect ["protect"] ))
						$protect ["protect"] = true;
					if (! isset ( $protect ["allow-option"] ))
						$protect ["allow-option"] = [ ];
					if (! isset ( $protect ["forbid-option"] ))
						$protect ["forbid-option"] = [ ];
					if (! isset ( $data ["economy-home-price"] ))
						$data ["economy-home-price"] = 5000;
					if (! isset ( $protect ["welcome"] ))
						$protect ["welcome"] = "";
					if (! isset ( $protect ["pvp-allow"] ))
						$protect ["pvp-allow"] = true;
					if (! isset ( $protect ["invensave"] ))
						$protect ["invensave"] = true;
					
					$convertedData [$protect ["ID"]] = [ 
							"id" => $protect ["ID"],
							"owner" => $owner,
							"resident" => $residents,
							"isHome" => $protect ["is-home"],
							"startX" => $protect ["startX"],
							"endX" => $protect ["endX"],
							"startZ" => $protect ["startZ"],
							"endZ" => $protect ["endZ"],
							"protect" => $protect ["protect"],
							"allowOption" => $protect ["allow-option"],
							"forbidOption" => $protect ["forbid-option"],
							"areaPrice" => $data ["economy-home-price"],
							"welcome" => $protect ["welcome"],
							"pvpAllow" => $protect ["pvp-allow"],
							"invenSave" => $protect ["invensave"] 
					];
				}
				$convertedData ["areaIndex"] ++;
				
				(new Config ( $jsonLink, Config::JSON, $convertedData ))->save ();
				@unlink ( $link );
			}
		}
	}
}
?>