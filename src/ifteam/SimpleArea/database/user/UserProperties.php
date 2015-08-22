<?php

namespace ifteam\SimpleArea\database\user;

use pocketmine\event\Listener;
use ifteam\SimpleArea\event\AreaAddEvent;
use ifteam\SimpleArea\event\AreaDeleteEvent;
use ifteam\SimpleArea\event\AreaResidentEvent;
use ifteam\SimpleArea\event\RentAddEvent;
use ifteam\SimpleArea\event\RentDeleteEvent;
use ifteam\SimpleArea\event\RentBuyEvent;
use ifteam\SimpleArea\event\RentOutEvent;
use ifteam\SimpleArea\task\CheckAreaEventTask;
use ifteam\SimpleArea\task\CheckRentEventTask;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\area\AreaSection;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\database\rent\RentSection;
use pocketmine\event\Event;
use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\Player;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\database\world\WhiteWorldData;

class UserProperties implements Listener {
	private static $instance = null;
	/**
	 *
	 * @var Server
	 */
	private $server;
	/**
	 *
	 * @var AreaProvider
	 */
	private $areaProvider;
	/**
	 *
	 * @var RentProvider
	 */
	private $rentProvider;
	/**
	 *
	 * @var WhiteWorldProvider
	 */
	private $whiteWorldProvider;
	private $properties = [ ];
	private $rentProperties = [ ];
	private $saleList = [ ];
	private $rentSaleList = [ ];
	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->server = Server::getInstance ();
		$this->areaProvider = AreaProvider::getInstance ();
		$this->rentProvider = RentProvider::getInstance ();
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance ();
		$this->init ();
	}
	/**
	 * Load list the user area holdings
	 */
	public function init($levelName = null) {
		if ($levelName !== null) {
			$level = $this->server->getLevelByName ( $levelName );
			if (! $level instanceof Level)
				return;
			$areas = $this->areaProvider->getAll ( $level->getFolderName () );
			foreach ( $areas as $area ) {
				if (isset ( $area ["resident"] ) and count ( $area ["resident"] ) == 0) {
					$this->addSaleList ( $level->getFolderName (), $area ["id"] );
					continue;
				}
				if (! isset ( $area ["resident"] ) or ! isset ( $area ["id"] ) or ! is_array ( $area ["resident"] ))
					continue;
				foreach ( $area ["resident"] as $resident => $bool )
					$this->addUserProperties ( $resident, $level->getFolderName (), $area ["id"] );
			}
			$rents = $this->rentProvider->getAll ( $level->getFolderName () );
			foreach ( $rents as $rent ) {
				if (! isset ( $rent ["owner"] ))
					continue;
				if ($rent ["owner"] == "") {
					$this->addRentSaleList ( $level->getFolderName (), $rent ["rentId"] );
					continue;
				}
				$this->addUserRentProperties ( $rent ["owner"], $level->getFolderName (), $rent ["rentId"] );
			}
		}
		foreach ( $this->server->getLevels () as $level )
			if ($level instanceof Level) {
				$areas = $this->areaProvider->getAll ( $level->getFolderName () );
				$whiteWorld = $this->whiteWorldProvider->get ( $level );
				foreach ( $areas as $area ) {
					if (isset ( $area ["resident"] ) and count ( $area ["resident"] ) == 0) {
						$this->addSaleList ( $level->getFolderName (), $area ["id"] );
						continue;
					}
					if (! isset ( $area ["resident"] ) or ! isset ( $area ["id"] ) or ! is_array ( $area ["resident"] ))
						continue;
					if ($whiteWorld->isCountShareArea ()) {
						foreach ( $area ["resident"] as $resident => $bool )
							$this->addUserProperties ( $resident, $level->getFolderName (), $area ["id"] );
					} else {
						if ($area ["owner"] != "")
							$this->addUserProperties ( $area ["owner"], $level->getFolderName (), $area ["id"] );
					}
				}
				$rents = $this->rentProvider->getAll ( $level->getFolderName () );
				foreach ( $rents as $rent ) {
					if (! isset ( $rent ["owner"] ))
						continue;
					if ($rent ["owner"] == "") {
						$this->addRentSaleList ( $level->getFolderName (), $rent ["rentId"] );
						continue;
					}
					$this->addUserRentProperties ( $rent ["owner"], $level->getFolderName (), $rent ["rentId"] );
				}
			}
	}
	/**
	 * Add user area holdings
	 *
	 * @param string $username        	
	 * @param string $level        	
	 * @param int $areaId        	
	 */
	public function addUserProperties($username, $level, $areaId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if ($username instanceof Player)
			$username = $username->getName ();
		$username = strtolower ( $username );
		$this->properties [$username] [$level] [$areaId] = true;
	}
	/**
	 * Add user rent holdings
	 *
	 * @param string $username        	
	 * @param string $level        	
	 * @param int $rentId        	
	 */
	public function addUserRentProperties($username, $level, $rentId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if ($username instanceof Player)
			$username = $username->getName ();
		$username = strtolower ( $username );
		$this->rentProperties [$username] [$level] [$rentId] = true;
	}
	/**
	 * Add can buy area list
	 *
	 * @param string $level        	
	 * @param int $areaId        	
	 */
	public function addSaleList($level, $areaId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		$this->saleList [$level] [$areaId] = true;
	}
	/**
	 * Add can buy rent list
	 *
	 * @param string $level        	
	 * @param int $areaId        	
	 */
	public function addRentSaleList($level, $areaId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		$this->rentSaleList [$level] [$areaId] = true;
	}
	/**
	 * Delete user area holdings
	 *
	 * @param string $username        	
	 * @param string $level        	
	 * @param int $areaId        	
	 */
	public function deleteUserProperties($username, $level, $areaId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if ($username instanceof Player)
			$username = $username->getName ();
		$username = strtolower ( $username );
		if (isset ( $this->properties [$username] [$level] [$areaId] ))
			unset ( $this->properties [$username] [$level] [$areaId] );
	}
	/**
	 * Delete user rent holdings
	 *
	 * @param string $username        	
	 * @param string $level        	
	 * @param int $rentId        	
	 */
	public function deleteUserRentProperties($username, $level, $rentId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if ($username instanceof Player)
			$username = $username->getName ();
		$username = strtolower ( $username );
		if (isset ( $this->rentProperties [$username] [$level] [$rentId] ))
			unset ( $this->rentProperties [$username] [$level] [$rentId] );
	}
	/**
	 * Delete can buy area list
	 *
	 * @param string $level        	
	 * @param int $areaId        	
	 */
	public function deleteSaleList($level, $areaId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->saleList [$level] [$areaId] ))
			unset ( $this->saleList [$level] [$areaId] );
	}
	/**
	 * Delete can buy rent list
	 *
	 * @param string $level        	
	 * @param int $rentId        	
	 */
	public function deleteRentSaleList($level, $rentId) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->rentSaleList [$level] [$rentId] ))
			unset ( $this->rentSaleList [$level] [$rentId] );
	}
	/**
	 * Get user area holdings
	 *
	 * @param string $username        	
	 * @param string $level        	
	 * @return array
	 */
	public function getUserProperties($username, $level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if ($username instanceof Player)
			$username = $username->getName ();
		$username = strtolower ( $username );
		if (isset ( $this->properties [$username] [$level] ))
			return $this->properties [$username] [$level];
		return [ ];
	}
	/**
	 * Get user rent holdings
	 *
	 * @param string $username        	
	 * @param string $level        	
	 * @return array
	 */
	public function getUserRentProperties($username, $level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if ($username instanceof Player)
			$username = $username->getName ();
		$username = strtolower ( $username );
		if (isset ( $this->rentProperties [$username] [$level] ))
			return $this->rentProperties [$username] [$level];
		return [ ];
	}
	/**
	 * Get can buy area list
	 *
	 * @param string $level        	
	 * @return array
	 */
	public function getSaleList($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->saleList [$level] ))
			return $this->saleList [$level];
		return [ ];
	}
	/**
	 * Get can buy rent list
	 *
	 * @param string $level        	
	 * @return array
	 */
	public function getRentSaleList($level) {
		if ($level instanceof Level)
			$level = $level->getFolderName ();
		if (isset ( $this->rentSaleList [$level] ))
			return $this->rentSaleList [$level];
		return [ ];
	}
	/**
	 * Apply area event
	 *
	 * @param Event $event        	
	 */
	public function applyAreaEvent(Event $event) {
		switch (true) {
			case $event instanceof AreaAddEvent :
				$area = $event->getAreaData ();
				$whiteWorld = $event->getWhtieWorldData ();
				
				if (! $area instanceof AreaSection)
					return;
				if (! $whiteWorld instanceof WhiteWorldData)
					return;
				
				$residents = $area->getResident ();
				if (count ( $residents ) == 0) {
					$this->addSaleList ( $area->getLevel (), $area->getId () );
					return;
				}
				if ($whiteWorld->isCountShareArea ()) {
					foreach ( $residents as $resident => $bool )
						$this->addUserProperties ( $resident, $area->getLevel (), $area->getId () );
				} else {
					if ($area->getOwner () != "")
						$this->addUserProperties ( $area->getOwner (), $area->getLevel (), $area->getId () );
				}
				break;
			case $event instanceof AreaDeleteEvent :
				$whiteWorld = $event->getWhtieWorldData ();
				$area = $event->getAreaData ();
				
				$residents = $event->getResident ();
				$this->deleteSaleList ( $event->getLevel (), $event->getAreaId () );
				
				if ($whiteWorld->isCountShareArea ()) {
					foreach ( $residents as $resident => $bool )
						$this->addUserProperties ( $resident, $event->getLevel (), $event->getAreaId () );
				} else {
					if ($area->getOwner () != "")
						$this->addUserProperties ( $area->getOwner (), $event->getLevel (), $event->getAreaId () );
				}
				break;
			case $event instanceof AreaResidentEvent :
				$area = $event->getAreaData ();
				if (! $area instanceof AreaSection)
					return;
				if (count ( $event->getAdded () ) == 0) {
					$residents = $area->getResident ();
					foreach ( $event->getDeleted () as $player )
						if (isset ( $residents [$player] ))
							unset ( $residents [$player] );
					if (count ( $residents ) == 0) {
						$this->addSaleList ( $area->getLevel (), $area->getId () );
					} else {
						$this->deleteSaleList ( $area->getLevel (), $area->getId () );
					}
				} else {
					$this->deleteSaleList ( $area->getLevel (), $area->getId () );
				}
				foreach ( $event->getAdded () as $player )
					$this->addUserProperties ( $player, $area->getLevel (), $area->getId () );
				foreach ( $event->getDeleted () as $player )
					$this->deleteUserProperties ( $player, $area->getLevel (), $area->getId () );
				break;
		}
	}
	public function onAreaAddEvent(AreaAddEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckAreaEventTask ( $this, $event ), 1 );
	}
	public function onAreaDeleteEvent(AreaDeleteEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckAreaEventTask ( $this, $event ), 1 );
	}
	public function onAreaResidentEvent(AreaResidentEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckAreaEventTask ( $this, $event ), 1 );
	}
	/**
	 * Apply rent event
	 *
	 * @param Event $event        	
	 */
	public function applyRentEvent(Event $event) {
		switch (true) {
			case $event instanceof RentAddEvent :
				$rent = $event->getRentData ();
				if (! $rent instanceof RentSection)
					return;
				
				$owner = $rent->getOwner ();
				if ($owner == "") {
					$this->addRentSaleList ( $rent->getLevel (), $rent->getRentId () );
				} else {
					$this->addUserRentProperties ( $owner, $rent->getLevel (), $rent->getRentId () );
				}
				break;
			case $event instanceof RentDeleteEvent :
				$rent = $event->getRentData ();
				if (! $rent instanceof RentSection)
					return;
				
				$this->deleteRentSaleList ( $rent->getLevel (), $rent->getRentId () );
				$this->deleteUserRentProperties ( $rent->getOwner (), $rent->getLevel (), $rent->getRentId () );
				break;
			case $event instanceof RentBuyEvent :
				$rent = $event->getRentData ();
				if (! $rent instanceof RentSection)
					return;
				
				$this->deleteRentSaleList ( $rent->getLevel (), $rent->getRentId () );
				$this->addUserRentProperties ( $rent->getOwner (), $rent->getLevel (), $rent->getRentId () );
				break;
			case $event instanceof RentOutEvent :
				$rent = $event->getRentData ();
				if (! $rent instanceof RentSection)
					return;
				
				$this->addRentSaleList ( $rent->getLevel (), $rent->getRentId () );
				$this->deleteUserRentProperties ( $rent->getOwner (), $rent->getLevel (), $rent->getRentId () );
				break;
		}
	}
	public function onRentAddEvent(RentAddEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckRentEventTask ( $this, $event ), 1 );
	}
	public function onRentDeleteEvent(RentDeleteEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckRentEventTask ( $this, $event ), 1 );
	}
	public function onRentBuyEvent(RentBuyEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckRentEventTask ( $this, $event ), 1 );
	}
	public function onRentOutEvent(RentOutEvent $event) {
		$this->server->getScheduler ()->scheduleDelayedTask ( new CheckRentEventTask ( $this, $event ), 1 );
	}
	/**
	 *
	 * @return UserProperties
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>