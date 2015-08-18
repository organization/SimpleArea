<?php

namespace ifteam\SimpleArea\database\world;

use ifteam\SimpleArea\event\AreaTaxChangeEvent;
use pocketmine\Server;

class WhiteWorldData {
	private $data = [ ];
	private $level;
	public function __construct(array &$data, $level) {
		if (! isset ( $data ["protect"] ))
			$data ["protect"] = true;
		if (! isset ( $data ["allowOption"] ))
			$data ["allowOption"] = [ ];
		if (! isset ( $data ["forbidOption"] ))
			$data ["forbidOption"] = [ ];
		if (! isset ( $data ["defaultAreaPrice"] ))
			$data ["defaultAreaPrice"] = 5000;
		if (! isset ( $data ["areaTax"] ))
			$data ["areaTax"] = 0;
		if (! isset ( $data ["pricePerBlock"] ))
			$data ["pricePerBlock"] = 10;
		if (! isset ( $data ["welcome"] ))
			$data ["welcome"] = "";
		if (! isset ( $data ["pvpAllow"] ))
			$data ["pvpAllow"] = true;
		if (! isset ( $data ["invenSave"] ))
			$data ["invenSave"] = true;
		if (! isset ( $data ["autoCreateAllow"] ))
			$data ["autoCreateAllow"] = true;
		if (! isset ( $data ["manualCreateAllow"] ))
			$data ["manualCreateAllow"] = true;
		if (! isset ( $data ["areaHoldLimit"] ))
			$data ["areaHoldLimit"] = true;
		if (! isset ( $data ["defaultAreaSize"] ))
			$data ["defaultAreaSize"] = [ 
					32,
					22 
			];
		if (! isset ( $data ["defaultFenceType"] ))
			$data ["defaultFenceType"] = [ 
					139,
					1 
			];
		if (! isset ( $data ["isAllowAccessDeny"] ))
			$data ["isAllowAccessDeny"] = true;
		if (! isset ( $data ["isAllowAreaSizeUp"] ))
			$data ["isAllowAreaSizeUp"] = false;
		if (! isset ( $data ["isAllowAreaSizeDown"] ))
			$data ["isAllowAreaSizeDown"] = false;
		
		$this->level = $level;
		$this->data = &$data;
	}
	/**
	 * Get white world data
	 *
	 * @param string $level        	
	 * @return NULL|AreaSection
	 */
	public function getAll() {
		return $this->data;
	}
	/**
	 * Get welcome message
	 *
	 * @return string
	 */
	public function getWelcome() {
		return $this->data ["welcome"];
	}
	/**
	 * Get default area hold limit
	 *
	 * @return int
	 */
	public function getAreaHoldLimit() {
		return $this->data ["areaHoldLimit"];
	}
	/**
	 * Get default area price
	 *
	 * @return int
	 */
	public function getDefaultAreaPrice() {
		return $this->data ["defaultAreaPrice"];
	}
	/**
	 * Get default area size
	 *
	 * @return array
	 */
	public function getDefaultAreaSize() {
		return $this->data ["defaultAreaSize"];
	}
	/**
	 * Get price per block
	 *
	 * @return int
	 */
	public function getPricePerBlock() {
		return $this->data ["pricePerBlock"];
	}
	/**
	 * Get default fence type
	 *
	 * @return int
	 */
	public function getDefaultFenceType() {
		return $this->data ["defaultFenceType"];
	}
	/**
	 * Get allow option
	 *
	 * @return array
	 */
	public function getAllowOption() {
		return $this->data ["allowOption"];
	}
	/**
	 * Get forbid option
	 *
	 * @return array
	 */
	public function getForbidOption() {
		return $this->data ["forbidOption"];
	}
	/**
	 * Get area tax
	 *
	 * @return int
	 */
	public function getAreaTax() {
		return $this->data ["areaTax"];
	}
	/**
	 * Check world is protected
	 *
	 * @return boolean
	 */
	public function isProtected() {
		return $this->data ["protect"] == true ? true : false;
	}
	/**
	 * Check area is Allow that block
	 *
	 * @return boolean
	 */
	public function isAllowOption($blockId, $blockDamage = 0) {
		return isset ( $this->data ["allowOption"] ["{$blockId}:{$blockDamage}"] ) ? true : false;
	}
	/**
	 * Check world is Forbid that block
	 *
	 * @return boolean
	 */
	public function isForbidOption($blockId, $blockDamage = 0) {
		return isset ( $this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"] ) ? true : false;
	}
	/**
	 * Check world is Pvp allowed
	 *
	 * @return boolean
	 */
	public function isPvpAllow() {
		return $this->data ["pvpAllow"] == true ? true : false;
	}
	/**
	 * Check world is enabled inven save
	 *
	 * @return boolean
	 */
	public function isInvenSave() {
		return $this->data ["invenSave"] == true ? true : false;
	}
	/**
	 * Check world is auto area create allowed
	 *
	 * @return boolean
	 *
	 */
	public function isAutoCreateAllow() {
		return $this->data ["autoCreateAllow"] == true ? true : false;
	}
	/**
	 * Check world is manual area create allowed
	 *
	 * @return boolean
	 *
	 */
	public function isManualCreateAllow() {
		return $this->data ["manualCreateAllow"] == true ? true : false;
	}
	/**
	 * Check world is area accessdeny option allowed
	 *
	 * @return boolean
	 */
	public function isAllowAccessDeny() {
		return $this->data ["isAllowAccessDeny"] == true ? true : false;
	}
	/**
	 * Check world is area size up allowed
	 *
	 * @return boolean
	 */
	public function isAllowAreaSizeUp() {
		return $this->data ["isAllowAreaSizeUp"] == true ? true : false;
	}
	/**
	 * Check world is area size down allowed
	 *
	 * @return boolean
	 */
	public function isAllowAreaSizeDown() {
		return $this->data ["isAllowAreaSizeDown"] == true ? true : false;
	}
	/**
	 * Set world protect status
	 *
	 * @param bool $bool        	
	 */
	public function setProtect($bool = true) {
		$this->data ["protect"] = $bool;
	}
	/**
	 * Set world block allow status
	 *
	 * @param bool $bool        	
	 * @param int $blockId        	
	 * @param int $blockDamage        	
	 */
	public function setAllowOption($bool, $blockId, $blockDamage) {
		if ($bool) {
			if ($blockDamage === "*") {
				for($dmg = 0; $dmg <= 15; $dmg ++)
					$this->data ["allowOption"] ["{$blockId}:{$dmg}"] = true;
				return;
			}
			$this->data ["allowOption"] ["{$blockId}:{$blockDamage}"] = true;
		} else if ($blockDamage === "*") {
			for($dmg = 0; $dmg <= 15; $dmg ++)
				if (isset ( $this->data ["allowOption"] ["{$blockId}:{$dmg}"] ))
					unset ( $this->data ["allowOption"] ["{$blockId}:{$dmg}"] );
			return;
		} else if (isset ( $this->data ["allowOption"] ["{$blockId}:{$blockDamage}"] )) {
			unset ( $this->data ["allowOption"] ["{$blockId}:{$blockDamage}"] );
		}
	}
	/**
	 * Set world block forbid status
	 *
	 * @param bool $bool        	
	 * @param int $blockId        	
	 * @param int $blockDamage        	
	 */
	public function setForbidOption($bool, $blockId, $blockDamage) {
		if ($bool) {
			if ($blockDamage === "*") {
				for($dmg = 0; $dmg <= 15; $dmg ++)
					$this->data ["forbidOption"] ["{$blockId}:{$dmg}"] = true;
				return;
			}
			$this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"] = true;
		} else if ($blockDamage === "*") {
			for($dmg = 0; $dmg <= 15; $dmg ++)
				if (isset ( $this->data ["forbidOption"] ["{$blockId}:{$dmg}"] ))
					unset ( $this->data ["forbidOption"] ["{$blockId}:{$dmg}"] );
			return;
		} else if (isset ( $this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"] )) {
			unset ( $this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"] );
		}
	}
	/**
	 * Set world pvp allow status
	 *
	 * @param bool $bool        	
	 */
	public function setPvpAllow($bool = true) {
		$this->data ["pvpAllow"] = $bool;
	}
	/**
	 * Set world inven save status
	 *
	 * @param bool $bool        	
	 */
	public function setInvenSave($bool = true) {
		$this->data ["invenSave"] = $bool;
	}
	/**
	 * Set world welcome message
	 *
	 * @param unknown $string        	
	 */
	public function setWelcome($string) {
		$this->data ["welcome"] = $string;
	}
	/**
	 * Set world auto area create allow status
	 *
	 * @param string $bool        	
	 *
	 */
	public function setAutoCreateAllow($bool = true) {
		$this->data ["autoCreateAllow"] = $bool;
	}
	/**
	 * Set world manual area create allow status
	 *
	 * @param string $bool        	
	 *
	 */
	public function setManualCreate($bool = true) {
		$this->data ["manualCreateAllow"] = $bool;
	}
	/**
	 * Set default area Hold Limit
	 *
	 * @param int $limit        	
	 */
	public function setAreaHoldLimit($limit) {
		$this->data ["areaHoldLimit"] = $limit;
	}
	/**
	 * Set default area Price
	 *
	 * @param int $price        	
	 */
	public function setDefaultAreaPrice($price) {
		$this->data ["defaultAreaPrice"] = $price;
	}
	/**
	 * Set default area size
	 *
	 * @param int $x        	
	 * @param int $z        	
	 */
	public function setDefaultAreaSize($x, $z) {
		$this->data ["defaultAreaSize"] = [ 
				$x,
				$z 
		];
	}
	/**
	 * Set price per block
	 *
	 * @param int $price        	
	 */
	public function setPricePerBlock($price) {
		$this->data ["pricePerBlock"] = $price;
	}
	/**
	 * Set default area fence
	 *
	 * @param int $id        	
	 * @param int $damage        	
	 */
	public function setDefaultFence($id, $damage) {
		$this->data ["defaultFenceType"] = [ 
				$id,
				$damage 
		];
	}
	/**
	 * Set area tax
	 *
	 * @param int $price        	
	 */
	public function setAreaTax($price) {
		$event = new AreaTaxChangeEvent ( $this->level, $price );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if (! $event->isCancelled ())
			$this->data ["areaTax"] = $event->getPrice ();
	}
	/**
	 * Set world is area accessdeny option allow status
	 *
	 * @return boolean
	 */
	public function setAllowAccessDeny($bool) {
		$this->data ["isAllowAccessDeny"] = $bool;
	}
	/**
	 * Set world is area size up allow status
	 *
	 * @return boolean
	 */
	public function setAllowAreaSizeUp($bool) {
		return $this->data ["isAllowAreaSizeUp"] = $bool;
	}
	/**
	 * Set world is area size down allow status
	 *
	 * @return boolean
	 */
	public function setAllowAreaSizeDown($bool) {
		return $this->data ["isAllowAreaSizeDown"] = $bool;
	}
}

?>