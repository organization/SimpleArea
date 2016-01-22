<?php

namespace ifteam\SimpleArea\database\area;

use ifteam\SimpleArea\event\AreaSellEvent;
use pocketmine\Server;
use ifteam\SimpleArea\event\AreaBuyEvent;
use ifteam\SimpleArea\event\AreaResidentEvent;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;

class AreaSection {
	private $data = [ ];
	private $level;
	public function __construct(array &$data, $level) {
		$basicElements = [ 
				"id",
				"owner",
				"resident",
				"isHome",
				"startX",
				"endX",
				"startZ",
				"endZ" 
		];
		foreach ( $basicElements as $element )
			if (! isset ( $data [$element] ))
				return null;
		
		$whiteWorld = WhiteWorldProvider::getInstance ()->get ( $level );
		
		if (! isset ( $data ["protect"] ))
			$data ["protect"] = true;
		if (! isset ( $data ["allowOption"] ))
			$data ["allowOption"] = [ ];
		if (! isset ( $data ["forbidOption"] ))
			$data ["forbidOption"] = [ ];
		if (! isset ( $data ["areaPrice"] ))
			$data ["areaPrice"] = $whiteWorld->getDefaultAreaPrice ();
		if (! isset ( $data ["welcome"] ))
			$data ["welcome"] = "";
		if (! isset ( $data ["pvpAllow"] ))
			$data ["pvpAllow"] = $whiteWorld->isPvpAllow ();
		if (! isset ( $data ["invenSave"] ))
			$data ["invenSave"] = $whiteWorld->isInvenSave ();
		if (! isset ( $data ["accessDeny"] ))
			$data ["accessDeny"] = false;
		
		if ($data ["owner"] !== "")
			$data ["owner"] = strtolower ( $data ["owner"] );
		
		$lowerResident = [ ];
		foreach ( $data ["resident"] as $resident => $bool )
			$lowerResident [strtolower ( $resident )] = $bool;
		$data ["resident"] = $lowerResident;
		
		$this->level = $level;
		$this->data = &$data;
	}
	/**
	 * Sell area
	 *
	 * @param string $player        	
	 */
	public function sell($player = null) {
		if ($player instanceof Player)
			$player = $player->getName ();
		$player = strtolower ( $player );
		$event = new AreaSellEvent ( $this->getOwner (), $this->getLevel (), $this->getId (), $player );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if (! $event->isCancelled ())
			$this->setOwner ( $player );
	}
	/**
	 * Buy area
	 *
	 * @param string $player        	
	 */
	public function buy($player) {
		if ($player instanceof Player)
			$player = $player->getName ();
		$player = strtolower ( $player );
		$event = new AreaBuyEvent ( $this->getOwner (), $this->getLevel (), $this->getId (), $player );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		if (! $event->isCancelled ())
			$this->setOwner ( $player );
	}
	/**
	 * changeResident
	 *
	 * @param array $add        	
	 * @param array $delete        	
	 */
	public function changeResident($add = [], $delete = []) {
		$ReaffirmedAdd = [ ];
		foreach ( $add as $player ) {
			if ($player instanceof Player)
				$player = $player->getName ();
			$player = strtolower ( $player );
			$ReaffirmedAdd [] = $player;
		}
		
		$ReaffirmedDelete = [ ];
		foreach ( $delete as $player ) {
			if ($player instanceof Player)
				$player = $player->getName ();
			$player = strtolower ( $player );
			if (isset ( $this->data ["resident"] [$player] ))
				$ReaffirmedDelete [] = $player;
		}
		
		$event = new AreaResidentEvent ( $this->getOwner (), $this->getLevel (), $this->getId (), $ReaffirmedAdd, $ReaffirmedDelete );
		Server::getInstance ()->getPluginManager ()->callEvent ( $event );
		
		if (! $event->isCancelled ()) {
			foreach ( $ReaffirmedAdd as $player ) {
				if ($player instanceof Player)
					$player = $player->getName ();
				$player = strtolower ( $player );
				$this->data ["resident"] [$player] = true;
			}
			foreach ( $ReaffirmedDelete as $player ) {
				if ($player instanceof Player)
					$player = $player->getName ();
				$player = strtolower ( $player );
				if (isset ( $this->data ["resident"] [$player] ))
					unset ( $this->data ["resident"] [$player] );
			}
		}
	}
	/**
	 * Get area data
	 *
	 * @param string $key        	
	 * @return array
	 */
	public function get($key) {
		if (! isset ( $this->data [$key] ))
			return null;
		return $this->data [$key];
	}
	/**
	 * Get area data
	 *
	 * @return array
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
	 * Get area resident list
	 *
	 * @return array
	 */
	public function getResident() {
		return $this->data ["resident"];
	}
	/**
	 * Get area id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->data ["id"];
	}
	/**
	 * Get area owner
	 *
	 * @return string
	 */
	public function getOwner() {
		return $this->data ["owner"];
	}
	/**
	 * Get area price
	 *
	 * @return int
	 */
	public function getPrice() {
		return $this->data ["areaPrice"];
	}
	/**
	 * Get level name
	 *
	 * @return string $level
	 */
	public function getLevel() {
		return $this->level;
	}
	/**
	 * Get area center pos
	 *
	 * @return Vector3
	 */
	public function getCenter() {
		$xSize = $this->data ["endX"] - $this->data ["startX"];
		$zSize = $this->data ["endZ"] - $this->data ["startZ"];
		$x = $this->data ["startX"] + ($xSize / 2);
		$z = $this->data ["startZ"] + ($zSize / 2);
		$y = Server::getInstance ()->getLevelByName ( $this->level )->getHighestBlockAt ( $x, $z );
		return new Vector3 ( $x, $y, $z );
	}
	/**
	 * Get all allow block list
	 *
	 * @return array
	 */
	public function getAllowOption() {
		return $this->data ["allowOption"];
	}
	/**
	 * Get all forbid block list
	 *
	 * @return array
	 */
	public function getForbidOption() {
		return $this->data ["forbidOption"];
	}
	/**
	 * Check area type is home
	 *
	 * @return boolean
	 */
	public function isHome() {
		return $this->data ["isHome"] == true ? true : false;
	}
	/**
	 * Check area is protected
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
	 * Check area is Forbid that block
	 *
	 * @return boolean
	 */
	public function isForbidOption($blockId, $blockDamage = 0) {
		return isset ( $this->data ["forbidOption"] ["{$blockId}:{$blockDamage}"] ) ? true : false;
	}
	/**
	 * Check area is Pvp Allowed
	 *
	 * @return boolean
	 */
	public function isPvpAllow() {
		return $this->data ["pvpAllow"] == true ? true : false;
	}
	/**
	 * Check area is enabled inven save
	 *
	 * @return boolean
	 */
	public function isInvenSave() {
		return $this->data ["invenSave"] == true ? true : false;
	}
	/**
	 * Residents check
	 *
	 * @return boolean
	 */
	public function isResident($name) {
		if ($name instanceof Player)
			$name = $name->getName ();
		$name = strtolower ( $name );
		return isset ( $this->data ["resident"] [strtolower ( $name )] ) ? true : false;
	}
	/**
	 * Owner check
	 *
	 * @return boolean
	 */
	public function isOwner($name) {
		if ($name instanceof Player)
			$name = $name->getName ();
		$name = strtolower ( $name );
		return $this->data ["owner"] == strtolower ( $name ) ? true : false;
	}
	public function isCanBuy() {
		if (! $this->isHome ())
			return false;
		return $this->data ["owner"] == "" ? true : false;
	}
	public function isAccessDeny() {
		if ($this->isCanBuy ())
			return false;
		return $this->data ["accessDeny"];
	}
	/**
	 * Set area type
	 *
	 * @param bool $bool        	
	 */
	public function setHome($bool = true) {
		$this->data ["isHome"] = $bool;
	}
	/**
	 * Set area protect status
	 *
	 * @param bool $bool        	
	 */
	public function setProtect($bool = true) {
		$this->data ["protect"] = $bool;
	}
	/**
	 * Set area block allow status
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
	 * Set area block forbid status
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
	 * Set area pvp allow status
	 *
	 * @param bool $bool        	
	 */
	public function setPvpAllow($bool = true) {
		$this->data ["pvpAllow"] = $bool;
	}
	/**
	 * Set area inven save status
	 *
	 * @param bool $bool        	
	 */
	public function setInvenSave($bool = true) {
		$this->data ["invenSave"] = $bool;
	}
	/**
	 * Set area resident
	 *
	 * @param bool $bool        	
	 */
	public function setResident($bool, $name) {
		if ($name instanceof Player)
			$name = $name->getName ();
		$name = strtolower ( $name );
		if ($bool) {
			$this->changeResident ( [ 
					$name 
			] );
			$this->data ["resident"] [$name] = true;
		} else {
			if (isset ( $this->data ["resident"] [$name] )) {
				$this->changeResident ( [ ], [ 
						$name 
				] );
				unset ( $this->data ["resident"] [$name] );
			}
		}
	}
	/**
	 * Set area owner
	 *
	 * @param string $name        	
	 */
	public function setOwner($name) {
		if ($name instanceof Player)
			$name = $name->getName ();
		$name = strtolower ( $name );
		if ($this->data ["owner"] != "")
			$this->setResident ( false, $this->data ["owner"] );
		$this->data ["owner"] = strtolower ( $name );
		if ($name != "")
			$this->setResident ( true, $name );
	}
	/**
	 * Set area welcome message
	 *
	 * @param string $string        	
	 */
	public function setWelcome($string) {
		$this->data ["welcome"] = mb_convert_encoding ( $string, "UTF-8" );
	}
	/**
	 * Set area price
	 *
	 * @param int $price        	
	 */
	public function setPrice($price) {
		$this->data ["areaPrice"] = $price;
	}
	/**
	 * Set area is access deny
	 *
	 * @param int $bool        	
	 */
	public function setAccessDeny($bool) {
		$this->data ["accessDeny"] = $bool;
	}
	/**
	 * Set area data
	 *
	 * @param string $key        	
	 * @param array $data        	
	 */
	public function set($key, $data) {
		$this->data [$key] = $data;
	}
	/**
	 * Set area data
	 *
	 * @param array $data        	
	 */
	public function setAll($data) {
		$this->data = $data;
	}
	/**
	 * Self area delete
	 */
	public function deleteArea() {
		AreaProvider::getInstance ()->deleteArea ( $this->level, $this->data ["id"] );
	}
	/**
	 *
	 * @param int $length        	
	 * @param int $fenceType        	
	 */
	public function setFence($length = 2, $fenceId = null, $fenceDamange = null) {
		if (isset ( $this->data ["fencePos"] )) {
			$this->removePastFence ();
		}
		
		$startX = $this->data ["startX"] - 1;
		$startZ = $this->data ["startZ"] - 1;
		$endX = $this->data ["endX"] + 1;
		$endZ = $this->data ["endZ"] + 1;
		
		if ($fenceId === null and $fenceDamange === null) {
			$defaultFenceData = WhiteWorldProvider::getInstance ()->get ( $this->level )->getDefaultFenceType ();
			$fenceId = $defaultFenceData [0];
			$fenceDamange = $defaultFenceData [1];
		}
		
		$this->setHighestBlockAt ( $startX, $startZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->setHighestBlockAt ( $startX + $i, $startZ, $fenceId, $fenceDamange );
			$this->setHighestBlockAt ( $startX, $startZ + $i, $fenceId, $fenceDamange );
		}
		
		$this->setHighestBlockAt ( $startX, $endZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->setHighestBlockAt ( $startX + $i, $endZ, $fenceId, $fenceDamange );
			$this->setHighestBlockAt ( $startX, $endZ - $i, $fenceId, $fenceDamange );
		}
		
		$this->setHighestBlockAt ( $endX, $startZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->setHighestBlockAt ( $endX - $i, $startZ, $fenceId, $fenceDamange );
			$this->setHighestBlockAt ( $endX, $startZ + $i, $fenceId, $fenceDamange );
		}
		
		$this->setHighestBlockAt ( $endX, $endZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->setHighestBlockAt ( $endX - $i, $endZ, $fenceId, $fenceDamange );
			$this->setHighestBlockAt ( $endX, $endZ - $i, $fenceId, $fenceDamange );
		}
		$this->setSideFence ( $startX, $startX, $startZ, $endZ, $length, $fenceId, $fenceDamange ); // UP
		$this->setSideFence ( $endX, $endX, $startZ, $endZ, $length, $fenceId, $fenceDamange ); // DOWN
		$this->setSideFence ( $startX, $endX, $startZ, $startZ, $length, $fenceId, $fenceDamange ); // WEST
		$this->setSideFence ( $startX, $endX, $endZ, $endZ, $length, $fenceId, $fenceDamange ); // EAST
	}
	private function setSideFence($startX, $endX, $startZ, $endZ, $length, $fenceId, $fenceDamange) {
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
		
		$fenceQueue = 0;
		$emptyQueue = 0;
		for($x = $startX; $x <= $endX; $x ++)
			for($z = $startZ; $z <= $endZ; $z ++)
				if ($fenceQueue <= $length) {
					$fenceQueue ++;
					$this->setHighestBlockAt ( $x, $z, $fenceId, $fenceDamange );
				} else {
					if ($emptyQueue < 2) {
						$emptyQueue ++;
						if ($emptyQueue >= 2) {
							$fenceQueue = 0;
							$emptyQueue = 0;
						}
						continue;
					}
				}
	}
	public function removePastFence() {
		$level = Server::getInstance ()->getLevelByName ( $this->level );
		foreach ( $this->data ["fencePos"] as $pos => $fence ) {
			$pos = explode ( ":", $pos );
			$level->setBlock ( new Vector3 ( $pos [0], $pos [1], $pos [2] ), Block::get ( Block::AIR ) );
		}
		unset ( $this->data ["fencePos"] );
	}
	/**
	 *
	 * @param int $length        	
	 * @param int $fenceId        	
	 * @param int $fenceDamange        	
	 */
	public function removeFence($length = 2, $fenceId = null, $fenceDamange = null) {
		if (isset ( $this->data ["fencePos"] )) {
			$this->removePastFence ();
			return;
		}
		
		$startX = $this->data ["startX"] - 1;
		$startZ = $this->data ["startZ"] - 1;
		$endX = $this->data ["endX"] + 1;
		$endZ = $this->data ["endZ"] + 1;
		
		if ($fenceId === null and $fenceDamange === null) {
			$defaultFenceData = WhiteWorldProvider::getInstance ()->get ( $this->level )->getDefaultFenceType ();
			$fenceId = $defaultFenceData [0];
			$fenceDamange = $defaultFenceData [1];
		}
		
		$this->removeHighestWall ( $startX, $startZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->removeHighestWall ( $startX + $i, $startZ, $fenceId, $fenceDamange );
			$this->removeHighestWall ( $startX, $startZ + $i, $fenceId, $fenceDamange );
		}
		
		$this->removeHighestWall ( $startX, $endZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->removeHighestWall ( $startX + $i, $endZ, $fenceId, $fenceDamange );
			$this->removeHighestWall ( $startX, $endZ - $i, $fenceId, $fenceDamange );
		}
		
		$this->removeHighestWall ( $endX, $startZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->removeHighestWall ( $endX - $i, $startZ, $fenceId, $fenceDamange );
			$this->removeHighestWall ( $endX, $startZ + $i, $fenceId, $fenceDamange );
		}
		
		$this->removeHighestWall ( $endX, $endZ, $fenceId, $fenceDamange );
		for($i = 1; $i <= $length; $i ++) {
			$this->removeHighestWall ( $endX - $i, $endZ, $fenceId, $fenceDamange );
			$this->removeHighestWall ( $endX, $endZ - $i, $fenceId, $fenceDamange );
		}
		
		$this->removeSideFence ( $startX, $startX, $startZ, $endZ, $fenceId, $fenceDamange ); // UP
		$this->removeSideFence ( $endX, $endX, $startZ, $endZ, $fenceId, $fenceDamange ); // DOWN
		$this->removeSideFence ( $startX, $endX, $startZ, $startZ, $fenceId, $fenceDamange ); // WEST
		$this->removeSideFence ( $startX, $endX, $endZ, $endZ, $fenceId, $fenceDamange ); // EAST
	}
	private function removeSideFence($startX, $endX, $startZ, $endZ, $fenceId, $fenceDamange) {
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
		
		for($x = $startX; $x <= $endX; $x ++)
			for($z = $startZ; $z <= $endZ; $z ++)
				$this->removeHighestWall ( $x, $z, $fenceId, $fenceDamange );
	}
	/**
	 *
	 * @param int $x        	
	 * @param int $z        	
	 * @param int $fenceId        	
	 * @param int $fenceDamange        	
	 */
	private function setHighestBlockAt($x, $z, $fenceId, $fenceDamange = 0) {
		$level = Server::getInstance ()->getLevelByName ( $this->level );
		
		$y = $level->getHighestBlockAt ( $x, $z );
		$blockId = $level->getBlockIdAt ( $x, $y, $z );
		$blockDmg = $level->getBlockDataAt ( $x, $y, $z );
		
		if ($blockId == $fenceId and $blockDmg == $fenceDamange)
			return;
		
		if ($blockId != 0) {
			$y ++;
			if ($blockId == Block::SIGN_POST)
				return;
			$block = Block::get ( $blockId, $blockDmg );
			if ($block->canBeReplaced ()) {
				$y --;
			} else if (! $block->isSolid ()) {
				$y --;
			}
		}
		
		if (! isset ( $this->data ["fencePos"] ["{$x}:{$y}:{$z}"] ))
			$this->data ["fencePos"] ["{$x}:{$y}:{$z}"] = "{$fenceId}:{$fenceDamange}";
		
		$level->setBlock ( new Vector3 ( $x, $y, $z ), Block::get ( $fenceId, $fenceDamange ) );
	}
	/**
	 *
	 * @param int $x        	
	 * @param int $z        	
	 * @param int $fenceId        	
	 * @param int $fenceDamange        	
	 */
	private function removeHighestWall($x, $z, $fenceId, $fenceDamange = 0) {
		$level = Server::getInstance ()->getLevelByName ( $this->level );
		$y = $level->getHighestBlockAt ( $x, $z );
		
		if ($level->getBlockIdAt ( $x, $y, $z ) == $fenceId and $level->getBlockDataAt ( $x, $y, $z ) == $fenceDamange)
			$level->setBlock ( new Vector3 ( $x, $y, $z ), Block::get ( Block::AIR ) );
	}
}

?>