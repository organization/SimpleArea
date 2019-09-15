<?php

namespace ifteam\SimpleArea\database\world;

use ifteam\SimpleArea\event\AreaTaxChangeEvent;

class WhiteWorldData {
	private $data = [];
	private $world;

	public function __construct(array &$data, $world) {
		if (!isset ($data ["protect"]))
			$data ["protect"] = true;
		if (!isset ($data ["defaultAreaPrice"]))
			$data ["defaultAreaPrice"] = 5000;
		if (!isset ($data ["areaTax"]))
			$data ["areaTax"] = 0;
		if (!isset ($data ["pricePerBlock"]))
			$data ["pricePerBlock"] = 10;
		if (!isset ($data ["welcome"]))
			$data ["welcome"] = "";
		if (!isset ($data ["pvpAllow"]))
			$data ["pvpAllow"] = true;
		if (!isset ($data ["invenSave"]))
			$data ["invenSave"] = true;
		if (!isset ($data ["autoCreateAllow"]))
			$data ["autoCreateAllow"] = true;
		if (!isset ($data ["manualCreateAllow"]))
			$data ["manualCreateAllow"] = true;
		if (!isset ($data ["areaHoldLimit"]))
			$data ["areaHoldLimit"] = true;
		if (!isset ($data ["defaultAreaSize"]))
			$data ["defaultAreaSize"] = [
					32,
					22
			];
		if (!isset ($data ["defaultFenceType"]))
			$data ["defaultFenceType"] = [
					139,
					1
			];
		if (!isset ($data ["isAllowAccessDeny"]))
			$data ["isAllowAccessDeny"] = true;
		if (!isset ($data ["isAllowAreaSizeUp"]))
			$data ["isAllowAreaSizeUp"] = false;
		if (!isset ($data ["isAllowAreaSizeDown"]))
			$data ["isAllowAreaSizeDown"] = false;
		if (!isset ($data ["isCountShareArea"]))
			$data ["isCountShareArea"] = false;
		if (!isset ($data ["manualCreateMaxSize"]))
			$data ["manualCreateMaxSize"] = 200;
		if (!isset ($data ["manualCreateMinSize"]))
			$data ["manualCreateMixSize"] = 20;

		$this->world = $world;
		$this->data = &$data;
	}

	public function getAll() {
		return $this->data;
	}

	public function getWelcome() {
		return $this->data ["welcome"];
	}

	public function getAreaHoldLimit() {
		return $this->data ["areaHoldLimit"];
	}

	public function getDefaultAreaPrice() {
		return $this->data ["defaultAreaPrice"];
	}

	public function getDefaultAreaSize() {
		return $this->data ["defaultAreaSize"];
	}

	public function getPricePerBlock() {
		return $this->data ["pricePerBlock"];
	}

	public function getDefaultFenceType() {
		return $this->data ["defaultFenceType"];
	}

	public function getAreaTax() {
		return $this->data ["areaTax"];
	}

	public function isProtected() {
		return $this->data ["protect"] == true ? true : false;
	}

	public function isAllowOption($blockId, $blockDamage = 0) {
		return isset ($this->data ["allowOption"] ["{$blockId}:{$blockDamage}"]) ? true : false;
	}

	public function isForbidOption($blockId, $blockDamage = 0) {
		return isset ($this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"]) ? true : false;
	}

	public function isPvpAllow() {
		return $this->data ["pvpAllow"] == true ? true : false;
	}

	public function isInvenSave() {
		return $this->data ["invenSave"] == true ? true : false;
	}

	public function isAutoCreateAllow() {
		return $this->data ["autoCreateAllow"] == true ? true : false;
	}

	public function isManualCreateAllow() {
		return $this->data ["manualCreateAllow"] == true ? true : false;
	}

	public function isAllowAccessDeny() {
		return $this->data ["isAllowAccessDeny"] == true ? true : false;
	}

	public function isAllowAreaSizeUp() {
		return $this->data ["isAllowAreaSizeUp"] == true ? true : false;
	}

	public function isAllowAreaSizeDown() {
		return $this->data ["isAllowAreaSizeDown"] == true ? true : false;
	}

	public function isCountShareArea() {
		return $this->data ["isCountShareArea"] == true ? true : false;
	}

	public function setProtect($bool = true) {
		$this->data ["protect"] = $bool;
	}

	public function setAllowOption($bool, $blockId, $blockDamage) {
		if ($bool) {
			if ($blockDamage === "*") {
				for ($dmg = 0; $dmg <= 15; $dmg++)
					$this->data ["allowOption"] ["{$blockId}:{$dmg}"] = true;
				return;
			}
			$this->data ["allowOption"] ["{$blockId}:{$blockDamage}"] = true;
		} else if ($blockDamage === "*") {
			for ($dmg = 0; $dmg <= 15; $dmg++)
				if (isset ($this->data ["allowOption"] ["{$blockId}:{$dmg}"]))
					unset ($this->data ["allowOption"] ["{$blockId}:{$dmg}"]);
			return;
		} else if (isset ($this->data ["allowOption"] ["{$blockId}:{$blockDamage}"])) {
			unset ($this->data ["allowOption"] ["{$blockId}:{$blockDamage}"]);
		}
	}

	public function setForbidOption($bool, $blockId, $blockDamage) {
		if ($bool) {
			if ($blockDamage === "*") {
				for ($dmg = 0; $dmg <= 15; $dmg++)
					$this->data ["forbidOption"] ["{$blockId}:{$dmg}"] = true;
				return;
			}
			$this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"] = true;
		} else if ($blockDamage === "*") {
			for ($dmg = 0; $dmg <= 15; $dmg++)
				if (isset ($this->data ["forbidOption"] ["{$blockId}:{$dmg}"]))
					unset ($this->data ["forbidOption"] ["{$blockId}:{$dmg}"]);
			return;
		} else if (isset ($this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"])) {
			unset ($this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"]);
		}
	}

	public function setPvpAllow($bool = true) {
		$this->data ["pvpAllow"] = $bool;
	}

	public function setInvenSave($bool = true) {
		$this->data ["invenSave"] = $bool;
	}

	public function setWelcome($string) {
		$this->data ["welcome"] = $string;
	}

	public function setAutoCreateAllow($bool = true) {
		$this->data ["autoCreateAllow"] = $bool;
	}

	public function setManualCreate($bool = true) {
		$this->data ["manualCreateAllow"] = $bool;
	}

	public function setAreaHoldLimit($limit) {
		$this->data ["areaHoldLimit"] = $limit;
	}

	public function setDefaultAreaPrice($price) {
		$this->data ["defaultAreaPrice"] = $price;
	}

	public function setDefaultAreaSize($x, $z) {
		$this->data ["defaultAreaSize"] = [
				$x,
				$z
		];
	}

	public function setPricePerBlock($price) {
		$this->data ["pricePerBlock"] = $price;
	}

	public function setDefaultFence($id, $damage) {
		$this->data ["defaultFenceType"] = [
				$id,
				$damage
		];
	}

	public function setAreaTax($price) {
		$event = new AreaTaxChangeEvent ($this->world, $price);
		$event->call();
		//Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if (!$event->isCancelled())
			$this->data ["areaTax"] = $event->getPrice();
	}

	public function setAllowAccessDeny($bool) {
		$this->data ["isAllowAccessDeny"] = $bool;
	}

	public function setAllowAreaSizeUp($bool) {
		return $this->data ["isAllowAreaSizeUp"] = $bool;
	}

	public function setAllowAreaSizeDown($bool) {
		return $this->data ["isAllowAreaSizeDown"] = $bool;
	}

	public function setCountShareArea($bool) {
		return $this->data ["isCountShareArea"] = $bool;
	}
}