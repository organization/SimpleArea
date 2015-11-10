<?php

namespace ifteam\SimpleArea;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\player\PlayerDeathEvent;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\database\user\UserProperties;
use pocketmine\Player;
use ifteam\SimpleArea\database\area\AreaManager;
use ifteam\SimpleArea\database\world\WhiteWorldManager;
use ifteam\SimpleArea\database\area\AreaSection;
use ifteam\SimpleArea\database\world\WhiteWorldData;
use pocketmine\event\Event;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityCombustByBlockEvent;
use pocketmine\block\Fire;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use ifteam\SimpleArea\database\minefarm\MineFarmManager;
use ifteam\SimpleArea\event\AreaModifyEvent;
use ifteam\SimpleArea\database\rent\RentManager;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\database\rent\RentSection;
use pocketmine\utils\TextFormat;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {
	private $plugin;
	/**
	 *
	 * @var AreaProvider
	 */
	private $areaProvider;
	/**
	 *
	 * @var WhiteWorldProvider
	 */
	private $whiteWorldProvider;
	/**
	 *
	 * @var UserProperties
	 */
	private $userProperties;
	/**
	 *
	 * @var AreaManager
	 */
	private $areaManager;
	/**
	 *
	 * @var WhiteWorldManager
	 */
	private $whiteworldManager;
	/**
	 *
	 * @var MineFarmManager
	 */
	private $mineFarmManager;
	/**
	 *
	 * @var RentProvider
	 */
	private $rentProvider;
	/**
	 *
	 * @var RentManager
	 */
	private $rentManager;
	private $queue;
	private $waterLists = [ ];
	public function __construct(SimpleArea $plugin) {
		$this->plugin = $plugin;
		$this->areaProvider = AreaProvider::getInstance ();
		$this->rentProvider = RentProvider::getInstance ();
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance ();
		$this->userProperties = UserProperties::getInstance ();
		$this->areaManager = AreaManager::getInstance ();
		$this->whiteworldManager = WhiteWorldManager::getInstance ();
		$this->mineFarmManager = MineFarmManager::getInstance ();
		$this->rentManager = RentManager::getInstance ();
	}
	public function onCommand(CommandSender $player, Command $command, $label, Array $args) {
		if (! $player instanceof Player) {
			switch (strtolower ( $command )) {
				case $this->get ( "commands-area" ) :
					if (! isset ( $args [0] ))
						break;
					if (isset ( $args [0] ) and $args [0] == "?")
						break;
				case $this->get ( "commands-rent" ) :
					if (! isset ( $args [0] ))
						break;
					if (isset ( $args [0] ) and $args [0] == "?")
						break;
				case $this->get ( "commands-whiteworld" ) :
					if (! isset ( $args [0] ))
						break;
					if (isset ( $args [0] ) and $args [0] == "?")
						break;
				case $this->get ( "commands-minefarm" ) :
					if (! isset ( $args [0] ))
						break;
					if (isset ( $args [0] ) and $args [0] == $this->get ( "commands-minefarm-start" ))
						break;
					if (isset ( $args [0] ) and $args [0] == "?")
						break;
					if (isset ( $args [0] ) and $args [0] == "구매")
						break;
				default :
					$this->alert ( $player, $this->get ( "only-in-game" ) );
					return true;
			}
		}
		switch (strtolower ( $command->getName () )) {
			case $this->get ( "commands-area" ) :
				if (! isset ( $args [0] )) {
					$this->message ( $player, $this->get ( "commands-area-help-1" ) );
					$this->message ( $player, $this->get ( "commands-area-help-2" ) );
					$this->message ( $player, $this->get ( "commands-area-help-3" ) );
					$this->message ( $player, $this->get ( "commands-area-help-4" ) );
					$this->message ( $player, $this->get ( "commands-area-help-5" ) );
					$this->message ( $player, $this->get ( "commands-area-help-6" ) );
					return true;
				}
				switch (strtolower ( $args [0] )) {
					case $this->get ( "commands-area-move" ) :
						if (! $player->hasPermission ( "simplearea.area.move" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-move-help" ) );
							return true;
						}
						$this->areaManager->move ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-autocreate" ) :
						if (! $player->hasPermission ( "simplearea.area.autocreate" ))
							return false;
						$whiteWorld = $this->whiteWorldProvider->get ( $player->getLevel () );
						if (! $whiteWorld->isAutoCreateAllow ()) {
							$this->alert ( $player, $this->get ( "whiteworld-autocreate-not-allowed" ) );
							return true;
						}
						$this->areaManager->autoCreate ( $player );
						break;
					case $this->get ( "commands-area-manualcreate" ) :
						if (! $player->hasPermission ( "simplearea.area.manualcreate" ))
							return false;
						$whiteWorld = $this->whiteWorldProvider->get ( $player->getLevel () );
						if (! $whiteWorld->isManualCreateAllow ()) {
							$this->alert ( $player, $this->get ( "whiteworld-manualcreate-not-allowed" ) );
							return true;
						}
						if (isset ( $this->queue ["manual"] [strtolower ( $player->getName () )] )) {
							$this->message ( $player, $this->get ( "please-choose-two-pos" ) );
							return true;
						}
						if (! isset ( $this->queue ["manual"] [strtolower ( $player->getName () )] )) {
							$this->queue ["manual"] [strtolower ( $player->getName () )] = [ 
									"startX" => null,
									"endX" => null,
									"startZ" => null,
									"endZ" => null,
									"startLevel" => $player->getLevel ()->getFolderName () 
							];
							$this->message ( $player, $this->get ( "start-manual-create-area" ) );
							$this->message ( $player, $this->get ( "please-choose-two-pos" ) );
							$this->message ( $player, $this->get ( "you-can-stop-create-manual-area" ) );
						}
						break;
					case $this->get ( "commands-area-buy" ) :
						if (! $player->hasPermission ( "simplearea.area.buy" ))
							return false;
						$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
						if (! $area instanceof AreaSection) {
							$this->alert ( $player, $this->get ( "area-doesent-exist" ) );
							$this->alert ( $player, $this->get ( "commands-area-info-help" ) );
							return false;
						}
						if (! isset ( $this->queue ["areaBuy"] [strtolower ( $player->getName () )] )) {
							$this->message ( $player, $this->get ( "do-you-want-buy-this-area" ) );
							$this->message ( $player, $this->get ( "if-you-want-to-buy-please-command" ) );
							$this->queue ["areaBuy"] [strtolower ( $player->getName () )] = [ 
									"time" => $this->makeTimestamp () 
							];
							return true;
						}
						$before = $this->queue ["areaBuy"] [strtolower ( $player->getName () )] ["time"];
						$after = $this->makeTimestamp ();
						$timeout = intval ( $after - $before );
						
						if ($timeout <= 10) {
							$this->areaManager->buy ( $player );
						} else {
							$this->alert ( $player, $this->get ( "area-buy-time-over" ) );
						}
						unset ( $this->queue ["areaBuy"] [strtolower ( $player->getName () )] );
						break;
					case $this->get ( "commands-area-sell" ) :
						if (! $player->hasPermission ( "simplearea.area.sell" ))
							return false;
						$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
						if (! $area instanceof AreaSection) {
							$this->alert ( $player, $this->get ( "area-doesent-exist" ) );
							$this->alert ( $player, $this->get ( "commands-area-info-help" ) );
							return false;
						}
						if (! isset ( $this->queue ["areaSell"] [strtolower ( $player->getName () )] )) {
							$this->message ( $player, $this->get ( "do-you-want-sell-this-area" ) );
							$this->message ( $player, $this->get ( "if-you-want-to-sell-please-command" ) );
							$this->queue ["areaSell"] [strtolower ( $player->getName () )] = [ 
									"time" => $this->makeTimestamp () 
							];
							return true;
						}
						$before = $this->queue ["areaSell"] [strtolower ( $player->getName () )] ["time"];
						$after = $this->makeTimestamp ();
						$timeout = intval ( $after - $before );
						
						if ($timeout <= 10) {
							$this->areaManager->sell ( $player );
						} else {
							$this->alert ( $player, $this->get ( "area-sell-time-over" ) );
						}
						unset ( $this->queue ["areaSell"] [strtolower ( $player->getName () )] );
						break;
					case $this->get ( "commands-area-give" ) :
						if (! $player->hasPermission ( "simplearea.area.give" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-give-help" ) );
							return true;
						}
						$this->areaManager->give ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-info" ) :
						if (! $player->hasPermission ( "simplearea.area.info" ))
							return false;
						$this->areaManager->info ( $player );
						break;
					case $this->get ( "commands-area-share" ) :
						if (! $player->hasPermission ( "simplearea.area.share" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-share-help" ) );
							return true;
						}
						$this->areaManager->share ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-deport" ) :
						if (! $player->hasPermission ( "simplearea.area.deport" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-deport-help" ) );
							return true;
						}
						$this->areaManager->deport ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-sharelist" ) :
						if (! $player->hasPermission ( "simplearea.area.sharelist" ))
							return false;
						$this->areaManager->shareList ( $player );
						break;
					case $this->get ( "commands-area-welcome" ) :
						if (! $player->hasPermission ( "simplearea.area.welcome" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-welcome-help" ) );
							return true;
						}
						$this->areaManager->welcome ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-protect" ) :
						if (! $player->hasPermission ( "simplearea.area.protect" ))
							return false;
						$this->areaManager->protect ( $player );
						break;
					case $this->get ( "commands-area-allowblock" ) :
						if (! $player->hasPermission ( "simplearea.area.allowblock" ))
							return false;
						if (! isset ( $args [1] ) or ! isset ( $args [2] )) {
							if (isset ( $args [1] )) {
								if ($args [1] == $this->get ( "subcommands-list" )) {
									$this->areaManager->allowBlockList ( $player );
									return true;
								}
								if ($args [1] == $this->get ( "subcommands-clear" )) {
									$this->areaManager->allowBlockClear ( $player );
									return true;
								}
							}
							$this->message ( $player, $this->get ( "commands-area-allowblock-help" ) );
							$this->message ( $player, $this->get ( "commands-area-allowblock-help-1" ) );
							return true;
						}
						$args [1] == $this->get ( "subcommands-add" ) ? $bool = true : $bool = false;
						$this->areaManager->allowBlock ( $player, $args [2], $bool );
						break;
					case $this->get ( "commands-area-forbidblock" ) :
						if (! $player->hasPermission ( "simplearea.area.forbidblock" ))
							return false;
						if (! isset ( $args [1] ) or ! isset ( $args [2] )) {
							if (isset ( $args [1] )) {
								if ($args [1] == $this->get ( "subcommands-list" )) {
									$this->areaManager->forbidBlockList ( $player );
									return true;
								}
								if ($args [1] == $this->get ( "subcommands-clear" )) {
									$this->areaManager->forbidBlockClear ( $player );
									return true;
								}
							}
							$this->message ( $player, $this->get ( "commands-area-forbidblock-help" ) );
							$this->message ( $player, $this->get ( "commands-area-forbidblock-help-1" ) );
							return true;
						}
						$args [1] == $this->get ( "subcommands-add" ) ? $bool = true : $bool = false;
						$this->areaManager->forbidBlock ( $player, $args [2], $bool );
						break;
					case $this->get ( "commands-area-areaprice" ) :
						if (! $player->hasPermission ( "simplearea.area.areaprice" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-areaprice-help" ) );
							return true;
						}
						$this->areaManager->areaPrice ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-setfence" ) :
						if (! $player->hasPermission ( "simplearea.area.setfence" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-setfence-help" ) );
							return true;
						}
						$this->areaManager->setFence ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-setinvensave" ) :
						if (! $player->hasPermission ( "simplearea.area.setinvensave" ))
							return false;
						$this->areaManager->setInvenSave ( $player );
						break;
					case $this->get ( "commands-area-changemode" ) :
						if (! $player->hasPermission ( "simplearea.area.changemode" ))
							return false;
						$this->areaManager->changeMode ( $player );
						break;
					case $this->get ( "commands-area-abandon" ) :
						if (! $player->hasPermission ( "simplearea.area.abandon" ))
							return false;
						if (! isset ( $this->queue ["areaAbandon"] [strtolower ( $player->getName () )] )) {
							$this->message ( $player, $this->get ( "do-you-want-area-abandon" ) );
							$this->message ( $player, $this->get ( "if-you-want-to-abandon-do-again" ) );
							$this->queue ["areaAbandon"] [strtolower ( $player->getName () )] ["time"] = $this->makeTimestamp ();
						} else {
							$before = $this->queue ["areaAbandon"] [strtolower ( $player->getName () )] ["time"];
							$after = $this->makeTimestamp ();
							$timeout = intval ( $after - $before );
							
							if ($timeout <= 10) {
								$this->areaManager->abandon ( $player );
							} else {
								$this->message ( $player, $this->get ( "area-abandon-time-over" ) );
							}
							if (isset ( $this->queue ["areaAbandon"] [strtolower ( $player->getName () )] ))
								unset ( $this->queue ["areaAbandon"] [strtolower ( $player->getName () )] );
						}
						break;
					case $this->get ( "commands-area-cancel" ) :
						if (isset ( $this->queue ["manual"] [strtolower ( $player->getName () )] )) {
							unset ( $this->queue ["manual"] [strtolower ( $player->getName () )] );
							$this->message ( $player, $this->get ( "area-cancel-stopped" ) );
						} else if (isset ( $this->queue ["rentCreate"] [strtolower ( $player->getName () )] )) {
							unset ( $this->queue ["rentCreate"] [strtolower ( $player->getName () )] );
							$this->message ( $player, $this->get ( "area-cancel-stopped" ) );
						} else if (isset ( $this->queue ["areaSizeUp"] [strtolower ( $player->getName () )] )) {
							unset ( $this->queue ["areaSizeUp"] [strtolower ( $player->getName () )] );
							$this->message ( $player, $this->get ( "area-cancel-stopped" ) );
						} else if (isset ( $this->queue ["areaSizeDown"] [strtolower ( $player->getName () )] )) {
							unset ( $this->queue ["areaSizeDown"] [strtolower ( $player->getName () )] );
							$this->message ( $player, $this->get ( "area-cancel-stopped" ) );
						} else {
							$this->alert ( $player, $this->get ( "area-cancel-failed" ) );
						}
						break;
					case $this->get ( "commands-area-canbuylist" ) :
						if (! $player->hasPermission ( "simplearea.area.canbuylist" ))
							return false;
						if (! isset ( $args [1] ) or ! is_numeric ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-move-help" ) );
							$this->message ( $player, $this->get ( "commands-area-canbuylist-help" ) );
							$this->areaManager->saleList ( $player );
							return true;
						}
						$this->areaManager->saleList ( $player, $args [1] );
						break;
					case $this->get ( "commands-area-accessdeny" ) :
						if (! $player->hasPermission ( "simplearea.area.accessdeny" ))
							return false;
						$this->areaManager->accessDeny ( $player );
						break;
					case $this->get ( "commands-area-sizeup" ) :
						if (! $player->hasPermission ( "simplearea.area.sizeup" ))
							return false;
						if (! isset ( $this->queue ["areaSizeUp"] [strtolower ( $player->getName () )] )) {
							$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
							if (! $area instanceof AreaSection) {
								$this->alert ( $player, $this->get ( "area-doesent-exist" ) );
								$this->alert ( $player, $this->get ( "commands-area-info-help" ) );
								return true;
							}
							if (! $area->isOwner ( $player->getName () )) {
								$this->alert ( $player, $this->get ( "youre-not-owner" ) );
								return true;
							}
							$this->queue ["areaSizeUp"] [strtolower ( $player->getName () )] = [ 
									"startX" => 0,
									"endX" => 0,
									"startZ" => 0,
									"endZ" => 0,
									"id" => $area->getId (),
									"isTouched" => false,
									"resizePrice" => 0,
									"startLevel" => $player->getLevel ()->getFolderName () 
							];
							$this->message ( $player, $this->get ( "area-size-up-start" ) );
							$this->message ( $player, $this->get ( "please-choose-one-point" ) );
							$this->message ( $player, $this->get ( "you-can-stop-create-manual-area" ) );
							return true;
						}
						$sizeUpData = $this->queue ["areaSizeUp"] [strtolower ( $player->getName () )];
						if (! $sizeUpData ["isTouched"]) {
							$this->message ( $player, $this->get ( "please-choose-one-point" ) );
							return true;
						}
						$level = $sizeUpData ["startLevel"];
						$id = $sizeUpData ["id"];
						$startX = $sizeUpData ["startX"];
						$endX = $sizeUpData ["endX"];
						$startZ = $sizeUpData ["startZ"];
						$endZ = $sizeUpData ["endZ"];
						$price = $sizeUpData ["resizePrice"];
						$this->areaManager->areaSizeUp ( $player, $level, $id, $startX, $endX, $startZ, $endZ, $price );
						if (isset ( $this->queue ["areaSizeUp"] [strtolower ( $player->getName () )] ))
							unset ( $this->queue ["areaSizeUp"] [strtolower ( $player->getName () )] );
						break;
					case $this->get ( "commands-area-sizedown" ) :
						if (! $player->hasPermission ( "simplearea.area.sizedown" ))
							return false;
						if (! isset ( $this->queue ["areaSizeDown"] [strtolower ( $player->getName () )] )) {
							$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
							if (! $area instanceof AreaSection) {
								$this->alert ( $player, $this->get ( "area-doesent-exist" ) );
								$this->alert ( $player, $this->get ( "commands-area-info-help" ) );
								return true;
							}
							if (! $area->isOwner ( $player->getName () )) {
								$this->alert ( $player, $this->get ( "youre-not-owner" ) );
								return true;
							}
							$this->queue ["areaSizeDown"] [strtolower ( $player->getName () )] = [ 
									"startX" => 0,
									"endX" => 0,
									"startZ" => 0,
									"endZ" => 0,
									"id" => $area->getId (),
									"isTouched" => false,
									"startLevel" => $player->getLevel ()->getFolderName () 
							];
							$this->message ( $player, $this->get ( "area-size-down-start" ) );
							$this->message ( $player, $this->get ( "please-choose-inside-point" ) );
							$this->message ( $player, $this->get ( "you-can-stop-create-manual-area" ) );
							return true;
						}
						$sizeUpData = $this->queue ["areaSizeDown"] [strtolower ( $player->getName () )];
						if (! $sizeUpData ["isTouched"]) {
							$this->message ( $player, $this->get ( "please-choose-inside-point" ) );
							return true;
						}
						$level = $sizeUpData ["startLevel"];
						$id = $sizeUpData ["id"];
						$startX = $sizeUpData ["startX"];
						$endX = $sizeUpData ["endX"];
						$startZ = $sizeUpData ["startZ"];
						$endZ = $sizeUpData ["endZ"];
						$this->areaManager->areaSizeDown ( $player, $level, $id, $startX, $endX, $startZ, $endZ );
						unset ( $this->queue ["areaSizeDown"] [strtolower ( $player->getName () )] );
						break;
					case $this->get ( "commands-area-delete" ) :
						if (! $player->hasPermission ( "simplearea.area.delete" ))
							return false;
						if (! isset ( $this->queue ["areaDelete"] [strtolower ( $player->getName () )] )) {
							$this->message ( $player, $this->get ( "do-you-want-area-delete" ) );
							$this->message ( $player, $this->get ( "if-you-want-to-delete-do-again" ) );
							$this->queue ["areaDelete"] [strtolower ( $player->getName () )] ["time"] = $this->makeTimestamp ();
						} else {
							$before = $this->queue ["areaDelete"] [strtolower ( $player->getName () )] ["time"];
							$after = $this->makeTimestamp ();
							$timeout = intval ( $after - $before );
							
							if ($timeout <= 10) {
								$this->areaManager->delete ( $player );
							} else {
								$this->message ( $player, $this->get ( "area-delete-time-over" ) );
							}
							if (isset ( $this->queue ["areaDelete"] [strtolower ( $player->getName () )] ))
								unset ( $this->queue ["areaDelete"] [strtolower ( $player->getName () )] );
						}
						break;
					case $this->get ( "commands-area-getlist" ) :
						if (! $player->hasPermission ( "simplearea.area.getlist" ))
							return false;
						$this->areaManager->getList ( $player );
						break;
					case $this->get ( "commands-area-pvpallow" ) :
						if (! $player->hasPermission ( "simplearea.area.pvpallow" ))
							return false;
						$this->areaManager->pvpallow ( $player );
						break;
					case "?" :
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-area-help-1" ) );
							$this->message ( $player, $this->get ( "commands-area-help-2" ) );
							$this->message ( $player, $this->get ( "commands-area-help-3" ) );
							$this->message ( $player, $this->get ( "commands-area-help-4" ) );
							$this->message ( $player, $this->get ( "commands-area-help-5" ) );
							$this->message ( $player, $this->get ( "commands-area-help-6" ) );
							return true;
						}
						switch (strtolower ( $args [1] )) {
							case $this->get ( "commands-area-move" ) :
								$this->message ( $player, $this->get ( "commands-area-move-help" ) );
								break;
							case $this->get ( "commands-area-autocreate" ) :
								$this->message ( $player, $this->get ( "commands-area-autocreate-help" ) );
								break;
							case $this->get ( "commands-area-manualcreate" ) :
								$this->message ( $player, $this->get ( "commands-area-manualcreate-help" ) );
								break;
							case $this->get ( "commands-area-buy" ) :
								$this->message ( $player, $this->get ( "commands-area-buy-help" ) );
								break;
							case $this->get ( "commands-area-sell" ) :
								$this->message ( $player, $this->get ( "commands-area-sell-help" ) );
								break;
							case $this->get ( "commands-area-give" ) :
								$this->message ( $player, $this->get ( "commands-area-give-help" ) );
								break;
							case $this->get ( "commands-area-canbuylist" ) :
								$this->message ( $player, $this->get ( "commands-area-canbuylist-help" ) );
								break;
							case $this->get ( "commands-area-info" ) :
								$this->message ( $player, $this->get ( "commands-area-info-help" ) );
								break;
							case $this->get ( "commands-area-share" ) :
								$this->message ( $player, $this->get ( "commands-area-share-help" ) );
								break;
							case $this->get ( "commands-area-deport" ) :
								$this->message ( $player, $this->get ( "commands-area-deport-help" ) );
								break;
							case $this->get ( "commands-area-getlist" ) :
								$this->message ( $player, $this->get ( "commands-area-getlist-help" ) );
								break;
							case $this->get ( "commands-area-sharelist" ) :
								$this->message ( $player, $this->get ( "commands-area-sharelist-help" ) );
								break;
							case $this->get ( "commands-area-welcome" ) :
								$this->message ( $player, $this->get ( "commands-area-welcome-help" ) );
								break;
							case $this->get ( "commands-area-protect" ) :
								$this->message ( $player, $this->get ( "commands-area-protect-help" ) );
								break;
							case $this->get ( "commands-area-allowblock" ) :
								$this->message ( $player, $this->get ( "commands-area-allowblock-help" ) );
								$this->message ( $player, $this->get ( "commands-area-allowblock-help-1" ) );
								break;
							case $this->get ( "commands-area-forbidblock" ) :
								$this->message ( $player, $this->get ( "commands-area-forbidblock-help" ) );
								$this->message ( $player, $this->get ( "commands-area-forbidblock-help-1" ) );
								break;
							case $this->get ( "commands-area-areaprice" ) :
								$this->message ( $player, $this->get ( "commands-area-areaprice-help" ) );
								break;
							case $this->get ( "commands-area-setfence" ) :
								$this->message ( $player, $this->get ( "commands-area-setfence-help" ) );
								break;
							case $this->get ( "commands-area-setinvensave" ) :
								$this->message ( $player, $this->get ( "commands-area-setinvensave-help" ) );
								break;
							case $this->get ( "commands-area-changemode" ) :
								$this->message ( $player, $this->get ( "commands-area-changemode-help" ) );
								break;
							case $this->get ( "commands-area-abandon" ) :
								$this->message ( $player, $this->get ( "commands-area-abandon-help" ) );
								break;
							case $this->get ( "commands-area-accessdeny" ) :
								$this->message ( $player, $this->get ( "commands-area-accessdeny-help" ) );
								break;
							case $this->get ( "commands-area-sizeup" ) :
								$this->message ( $player, $this->get ( "commands-area-sizeup-help" ) );
								break;
							case $this->get ( "commands-area-sizedown" ) :
								$this->message ( $player, $this->get ( "commands-area-sizedown-help" ) );
								break;
							case $this->get ( "commands-area-pvpallow" ) :
								$this->message ( $player, $this->get ( "commands-area-pvpallow-help" ) );
								break;
							default :
								$this->message ( $player, $this->get ( "commands-area-help-1" ) );
								$this->message ( $player, $this->get ( "commands-area-help-2" ) );
								$this->message ( $player, $this->get ( "commands-area-help-3" ) );
								$this->message ( $player, $this->get ( "commands-area-help-4" ) );
								$this->message ( $player, $this->get ( "commands-area-help-5" ) );
								$this->message ( $player, $this->get ( "commands-area-help-6" ) );
								break;
						}
						break;
					default :
						$this->message ( $player, $this->get ( "commands-area-help-1" ) );
						$this->message ( $player, $this->get ( "commands-area-help-2" ) );
						$this->message ( $player, $this->get ( "commands-area-help-3" ) );
						$this->message ( $player, $this->get ( "commands-area-help-4" ) );
						$this->message ( $player, $this->get ( "commands-area-help-5" ) );
						$this->message ( $player, $this->get ( "commands-area-help-6" ) );
						break;
				}
				break;
			case $this->get ( "commands-whiteworld" ) :
				if (! isset ( $args [0] )) {
					$this->message ( $player, $this->get ( "commands-whiteworld-help-1" ) );
					$this->message ( $player, $this->get ( "commands-whiteworld-help-2" ) );
					$this->message ( $player, $this->get ( "commands-whiteworld-help-3" ) );
					$this->message ( $player, $this->get ( "commands-whiteworld-help-4" ) );
					$this->message ( $player, $this->get ( "commands-whiteworld-help-5" ) );
					$this->message ( $player, $this->get ( "commands-whiteworld-help-6" ) );
					return true;
				}
				switch (strtolower ( $args [0] )) {
					case $this->get ( "commands-whiteworld-info" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.info" ))
							return false;
						$this->whiteworldManager->info ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-protect" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.protect" ))
							return false;
						$this->whiteworldManager->protect ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-allowblock" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.allowblock" ))
							return false;
						if (! isset ( $args [1] ) or ! isset ( $args [2] )) {
							if (isset ( $args [1] )) {
								if ($args [1] == $this->get ( "subcommands-list" )) {
									$this->whiteworldManager->allowBlockList ( $player->getLevel (), $player );
									return true;
								}
								if ($args [1] == $this->get ( "subcommands-clear" )) {
									$this->whiteworldManager->allowBlockClear ( $player->getLevel (), $player );
									return true;
								}
							}
							$this->message ( $player, $this->get ( "commands-whiteworld-allowblock-help" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-allowblock-help-1" ) );
							return true;
						}
						$args [1] == $this->get ( "subcommands-add" ) ? $bool = true : $bool = false;
						$this->whiteworldManager->allowBlock ( $player->getLevel (), $args [2], $bool, $player );
						break;
					case $this->get ( "commands-whiteworld-forbidblock" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.forbidblock" ))
							return false;
						if (! isset ( $args [1] ) or ! isset ( $args [2] )) {
							if (isset ( $args [1] )) {
								if ($args [1] == $this->get ( "subcommands-list" )) {
									$this->whiteworldManager->forbidBlockList ( $player->getLevel (), $player );
									return true;
								}
								if ($args [1] == $this->get ( "subcommands-clear" )) {
									$this->whiteworldManager->forbidBlockClear ( $player->getLevel (), $player );
									return true;
								}
							}
							$this->message ( $player, $this->get ( "commands-whiteworld-forbidblock-help" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-forbidblock-help-1" ) );
							return true;
						}
						$args [1] == $this->get ( "subcommands-add" ) ? $bool = true : $bool = false;
						$this->whiteworldManager->forbidBlock ( $player->getLevel (), $args [2], $bool, $player );
						break;
					case $this->get ( "commands-whiteworld-areaprice" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.areaprice" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-whiteworld-areaprice-help" ) );
							return true;
						}
						$this->whiteworldManager->areaPrice ( $player->getLevel (), $args [1], $player );
						break;
					case $this->get ( "commands-whiteworld-setfence" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.setfence" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-whiteworld-setfence-help" ) );
							return true;
						}
						$this->whiteworldManager->setFence ( $player->getLevel (), $args [1], $player );
						break;
					case $this->get ( "commands-whiteworld-setinvensave" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.setinvensave" ))
							return false;
						$this->whiteworldManager->setInvenSave ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-setautocreateallow" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.setautocreateallow" ))
							return false;
						$this->whiteworldManager->setAutoCreateAllow ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-setmanualcreate" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.setmanualcreate" ))
							return false;
						$this->whiteworldManager->setManualCreate ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-areaholdlimit" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.areaholdlimit" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-whiteworld-areaholdlimit-help" ) );
							return true;
						}
						$this->whiteworldManager->areaHoldLimit ( $player->getLevel (), $args [1], $player );
						break;
					case $this->get ( "commands-whiteworld-defaultareasize" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.defaultareasize" ))
							return false;
						if (! isset ( $args [1] ) or ! isset ( $args [2] )) {
							$this->message ( $player, $this->get ( "commands-whiteworld-defaultareasize-help" ) );
							return true;
						}
						$this->whiteworldManager->defaultAreaSize ( $player->getLevel (), $args [1], $args [2], $player );
						break;
					case $this->get ( "commands-whiteworld-accessdeny" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.defaultareasize" ))
							return false;
						$this->whiteworldManager->setAccessDeny ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-sizeup" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.defaultareasize" ))
							return false;
						$this->whiteworldManager->setAreaSizeUp ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-sizedown" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.defaultareasize" ))
							return false;
						$this->whiteworldManager->setAreaSizeDown ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-pvpallow" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.pvpallow" ))
							return false;
						$this->whiteworldManager->pvpAllow ( $player->getLevel (), $player );
						break;
					case $this->get ( "commands-whiteworld-priceperblock" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.priceperblock" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-whiteworld-priceperblock-help" ) );
							return true;
						}
						$this->whiteworldManager->setPricePerBlock ( $player->getLevel (), $player, $args [1] );
						break;
					case $this->get ( "commands-whiteworld-checkshare" ) :
						if (! $player->hasPermission ( "simplearea.whiteworld.checkshare" ))
							return false;
						$this->whiteworldManager->setCountShareArea ( $player->getLevel (), $player );
						break;
					case "?" :
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-whiteworld-help-1" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-help-2" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-help-3" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-help-4" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-help-5" ) );
							$this->message ( $player, $this->get ( "commands-whiteworld-help-6" ) );
							return true;
						}
						switch (strtolower ( $args [1] )) {
							case $this->get ( "commands-whiteworld-info" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-info-help" ) );
								break;
							case $this->get ( "commands-whiteworld-protect" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-protect-help" ) );
								break;
							case $this->get ( "commands-whiteworld-allowblock" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-allowblock-help" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-allowblock-help-1" ) );
								break;
							case $this->get ( "commands-whiteworld-forbidblock" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-forbidblock-help" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-forbidblock-help-1" ) );
								break;
							case $this->get ( "commands-whiteworld-areaprice" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-areaprice-help" ) );
								break;
							case $this->get ( "commands-whiteworld-setfence" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-setfence-help" ) );
								break;
							case $this->get ( "commands-whiteworld-areaholdlimit" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-areaholdlimit-help" ) );
								break;
							case $this->get ( "commands-whiteworld-defaultareasize" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-defaultareasize-help" ) );
								break;
							case $this->get ( "commands-whiteworld-accessdeny" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-accessdeny-help" ) );
								break;
							case $this->get ( "commands-whiteworld-sizeup" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-sizeup-help" ) );
								break;
							case $this->get ( "commands-whiteworld-sizedown" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-sizedown-help" ) );
								break;
							case $this->get ( "commands-whiteworld-pvpallow" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-pvpallow-help" ) );
								break;
							case $this->get ( "commands-whiteworld-priceperblock" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-priceperblock-help" ) );
								break;
							case $this->get ( "commands-whiteworld-checkshare" ) :
								$this->message ( $player, $this->get ( "commands-whiteworld-checkshare-help" ) );
								break;
							default :
								$this->message ( $player, $this->get ( "commands-whiteworld-help-1" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-help-2" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-help-3" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-help-4" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-help-5" ) );
								$this->message ( $player, $this->get ( "commands-whiteworld-help-6" ) );
								break;
						}
						break;
					default :
						$this->message ( $player, $this->get ( "commands-whiteworld-help-1" ) );
						$this->message ( $player, $this->get ( "commands-whiteworld-help-2" ) );
						$this->message ( $player, $this->get ( "commands-whiteworld-help-3" ) );
						$this->message ( $player, $this->get ( "commands-whiteworld-help-4" ) );
						$this->message ( $player, $this->get ( "commands-whiteworld-help-5" ) );
						$this->message ( $player, $this->get ( "commands-whiteworld-help-6" ) );
						break;
				}
				break;
			case $this->get ( "commands-areatax" ) :
				if (! isset ( $args [0] ) or ! is_numeric ( $args [0] )) {
					$this->message ( $player, $this->get ( "commands-areatax-help-1" ) );
					$this->message ( $player, $this->get ( "commands-areatax-help-2" ) );
					return true;
				}
				$this->whiteWorldProvider->get ( $player->getLevel () )->setAreaTax ( $args [0] );
				$this->message ( $player, $this->get ( "areatax-changed" ) . $args [0] );
				break;
			case $this->get ( "commands-minefarm" ) :
				if (! isset ( $args [0] )) {
					$this->message ( $player, $this->get ( "commands-minefarm-help-1" ) );
					if ($player->isOp ())
						$this->message ( $player, $this->get ( "commands-minefarm-help-2" ) );
					$this->message ( $player, $this->get ( "commands-minefarm-help-3" ) );
					$this->message ( $player, $this->get ( "commands-minefarm-help-4" ) );
					$this->message ( $player, $this->get ( "commands-minefarm-help-5" ) );
					return true;
				}
				switch (strtolower ( $args [0] )) {
					case $this->get ( "commands-minefarm-buy" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.buy" ))
							return false;
						$this->mineFarmManager->buy ( $player );
						break;
					case $this->get ( "commands-minefarm-delete" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.delete" ))
							return false;
						$this->mineFarmManager->delete ( $player );
						break;
					case $this->get ( "commands-minefarm-move" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.move" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-minefarm-move-help" ) );
							$this->mineFarmManager->move ( $player, 0 );
							return true;
						}
						$this->mineFarmManager->move ( $player, $args [1] );
						break;
					case $this->get ( "commands-minefarm-list" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.list" ))
							return false;
						$this->mineFarmManager->getList ( $player );
						break;
					case $this->get ( "commands-minefarm-start" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.start" ))
							return false;
						$this->mineFarmManager->start ( $player );
						break;
					case $this->get ( "commands-minefarm-setprice" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.setprice" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-minefarm-setprice-help" ) );
							return true;
						}
						$this->mineFarmManager->setPrice ( $player, $args [1] );
						break;
					case $this->get ( "commands-minefarm-farmholdlimit" ) :
						if (! $player->hasPermission ( "simplearea.minefarm.farmholdlimit" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-minefarm-farmholdlimit-help" ) );
							return true;
						}
						$this->mineFarmManager->farmHoldLimit ( $player, $args [1] );
						break;
					case "?" :
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-minefarm-help-3" ) );
							return true;
						}
						switch (strtolower ( $args [1] )) {
							case $this->get ( "commands-minefarm-buy" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-buy-help" ) );
								break;
							case $this->get ( "commands-minefarm-delete" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-delete-help" ) );
								break;
							case $this->get ( "commands-minefarm-move" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-move-help" ) );
								break;
							case $this->get ( "commands-minefarm-list" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-list-help" ) );
								break;
							case $this->get ( "commands-minefarm-start" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-start-help" ) );
								break;
							case $this->get ( "commands-minefarm-setprice" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-setprice-help" ) );
								break;
							case $this->get ( "commands-minefarm-farmholdlimit" ) :
								$this->message ( $player, $this->get ( "commands-minefarm-farmholdlimit-help" ) );
								break;
							default :
								$this->message ( $player, $this->get ( "commands-minefarm-help-3" ) );
								break;
						}
						break;
					default :
						$this->message ( $player, $this->get ( "commands-minefarm-help-1" ) );
						if ($player->isOp ())
							$this->message ( $player, $this->get ( "commands-minefarm-help-2" ) );
						$this->message ( $player, $this->get ( "commands-minefarm-help-3" ) );
						$this->message ( $player, $this->get ( "commands-minefarm-help-4" ) );
						$this->message ( $player, $this->get ( "commands-minefarm-help-5" ) );
						break;
				}
				break;
			case $this->get ( "commands-rent" ) :
				if (! isset ( $args [0] )) {
					$this->message ( $player, $this->get ( "commands-rent-help-1" ) );
					$this->message ( $player, $this->get ( "commands-rent-help-2" ) );
					$this->message ( $player, $this->get ( "commands-rent-help-3" ) );
					$this->message ( $player, $this->get ( "commands-rent-help-4" ) );
					$this->message ( $player, $this->get ( "commands-rent-help-5" ) );
					return true;
				}
				switch ($args [0]) {
					case $this->get ( "commands-rent-move" ) :
						if (! $player->hasPermission ( "simplearea.rent.move" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-rent-move-help" ) );
							return true;
						}
						$this->rentManager->move ( $player, $args [1] );
						break;
					case $this->get ( "commands-rent-list" ) :
						if (! $player->hasPermission ( "simplearea.rent.list" ))
							return false;
						$this->rentManager->getList ( $player );
						break;
					case $this->get ( "commands-rent-create" ) :
						if (! $player->hasPermission ( "simplearea.rent.create" ))
							return false;
						if (isset ( $this->queue ["rentCreate"] [strtolower ( $player->getName () )] )) {
							if ($this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["startX"] === null) {
								$this->message ( $player, $this->get ( "please-choose-pos1" ) );
								return true;
							}
							if ($this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["endX"] === null) {
								$this->message ( $player, $this->get ( "please-choose-pos2" ) );
								return true;
							}
							$startX = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["startX"];
							$startY = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["startY"];
							$startZ = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["startZ"];
							$endX = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["endX"];
							$endY = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["endY"];
							$endZ = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["endZ"];
							$areaId = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["areaId"];
							$rentPrice = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["rentPrice"];
							$startLevel = $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ["startLevel"];
							$this->rentManager->create ( $player, $startLevel, $startX, $endX, $startY, $endY, $startZ, $endZ, $areaId, $rentPrice );
							if (isset ( $this->queue ["rentCreate"] [strtolower ( $player->getName () )] ))
								unset ( $this->queue ["rentCreate"] [strtolower ( $player->getName () )] );
							return true;
						}
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-rent-create-help" ) );
							return true;
						}
						$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
						if (! $area instanceof AreaSection) {
							$this->alert ( $player, $this->get ( "cant-find-area-rent-failed" ) );
							return true;
						}
						if (! $player->isOp ()) {
							if ($area->getOwner () != strtolower ( $player->getName () )) {
								$this->alert ( $player, $this->get ( "youre-not-owner-rent-failed" ) );
								return true;
							}
						}
						$this->queue ["rentCreate"] [strtolower ( $player->getName () )] = [ 
								"startX" => null,
								"startY" => null,
								"startZ" => null,
								"endX" => null,
								"endY" => null,
								"endZ" => null,
								"areaId" => $area->getId (),
								"rentPrice" => $args [1],
								"startLevel" => $player->getLevel ()->getFolderName () 
						];
						$this->message ( $player, $this->get ( "start-create-rent-area" ) );
						$this->message ( $player, $this->get ( "please-choose-two-pos" ) );
						$this->message ( $player, $this->get ( "you-can-stop-create-manual-area" ) );
						break;
					case $this->get ( "commands-rent-out" ) :
						if (! $player->hasPermission ( "simplearea.rent.out" ))
							return false;
						$this->rentManager->out ( $player );
						break;
					case $this->get ( "commands-rent-salelist" ) :
						if (! $player->hasPermission ( "simplearea.rent.salelist" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-rent-salelist-help" ) );
							$this->rentManager->saleList ( $player, 0 );
							return true;
						}
						$this->rentManager->saleList ( $player, $args [1] );
						break;
					case $this->get ( "commands-rent-welcome" ) :
						if (! $player->hasPermission ( "simplearea.rent.welcome" ))
							return false;
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-rent-welcome-help" ) );
							return true;
						}
						$this->rentManager->setWelcome ( $player, $args [1] );
						break;
					case "?" :
						if (! isset ( $args [1] )) {
							$this->message ( $player, $this->get ( "commands-rent-help-1" ) );
							$this->message ( $player, $this->get ( "commands-rent-help-2" ) );
							$this->message ( $player, $this->get ( "commands-rent-help-3" ) );
							$this->message ( $player, $this->get ( "commands-rent-help-4" ) );
							$this->message ( $player, $this->get ( "commands-rent-help-5" ) );
							return true;
						}
						switch ($args [1]) {
							case $this->get ( "commands-rent-move" ) :
								$this->message ( $player, $this->get ( "commands-rent-move-help" ) );
								break;
							case $this->get ( "commands-rent-list" ) :
								$this->message ( $player, $this->get ( "commands-rent-list-help" ) );
								break;
							case $this->get ( "commands-rent-create" ) :
								$this->message ( $player, $this->get ( "commands-rent-create-help" ) );
								break;
							case $this->get ( "commands-rent-out" ) :
								$this->message ( $player, $this->get ( "commands-rent-out-help" ) );
								break;
							case $this->get ( "commands-rent-salelist" ) :
								$this->message ( $player, $this->get ( "commands-rent-salelist-help" ) );
								break;
							case $this->get ( "commands-rent-welcome" ) :
								$this->message ( $player, $this->get ( "commands-rent-welcome-help" ) );
								break;
							default :
								$this->message ( $player, $this->get ( "commands-rent-help-1" ) );
								$this->message ( $player, $this->get ( "commands-rent-help-2" ) );
								$this->message ( $player, $this->get ( "commands-rent-help-3" ) );
								$this->message ( $player, $this->get ( "commands-rent-help-4" ) );
								$this->message ( $player, $this->get ( "commands-rent-help-5" ) );
								break;
						}
						break;
					default :
						$this->message ( $player, $this->get ( "commands-rent-help-1" ) );
						$this->message ( $player, $this->get ( "commands-rent-help-2" ) );
						$this->message ( $player, $this->get ( "commands-rent-help-3" ) );
						$this->message ( $player, $this->get ( "commands-rent-help-4" ) );
						$this->message ( $player, $this->get ( "commands-rent-help-5" ) );
						break;
				}
				break;
		}
		return true;
	}
	public function onBlockPlaceEvent(BlockPlaceEvent $event) {
		$this->onBlockChangeEvent ( $event );
	}
	public function onBlockBreakEvent(BlockBreakEvent $event) {
		$area = $this->areaProvider->getArea ( $event->getBlock ()->level, $event->getBlock ()->x, $event->getBlock ()->z );
		if ($area instanceof AreaSection) {
			$rent = $this->rentProvider->getRent ( $event->getBlock ()->level, $event->getBlock ()->x, $event->getBlock ()->y, $event->getBlock ()->z );
			if (($rent instanceof RentSection) and ! $rent->isBuySingNull ()) {
				
				$buySignPos = $rent->getBuySignPos ();
				$buySignPosString = "{$buySignPos->x}:{$buySignPos->y}:{$buySignPos->z}";
				
				$blockPos = $event->getBlock ();
				$blockPosString = "{$blockPos->x}:{$blockPos->y}:{$blockPos->z}";
				
				if ($buySignPosString != $blockPosString)
					return;
				
				if ($area->isOwner ( $event->getPlayer ()->getName () )) {
					$rent->deleteRent ();
					$this->message ( $event->getPlayer (), $this->get ( "rent-delete-complete" ) );
					return;
				}
				
				$event->setCancelled ();
				
				if (! $rent->isOwner ( $event->getPlayer ()->getName () )) {
					$this->message ( $event->getPlayer (), $this->get ( "rent-buy-sign-delete-must-be-owner" ) );
					return;
				}
				
				if (! isset ( $this->queue ["rentout"] [strtolower ( $event->getPlayer ()->getName () )] )) {
					$this->message ( $event->getPlayer (), $this->get ( "do-you-want-rent-out" ) );
					$this->message ( $event->getPlayer (), $this->get ( "if-you-want-do-break-again" ) );
					$this->queue ["rentout"] [strtolower ( $event->getPlayer ()->getName () )] = [ 
							"time" => $this->makeTimestamp () 
					];
					return;
				}
				$before = $this->queue ["rentout"] [strtolower ( $event->getPlayer ()->getName () )] ["time"];
				$after = $this->makeTimestamp ();
				$timeout = intval ( $after - $before );
				
				if ($timeout <= 10) {
					$rent->out ();
					$this->message ( $event->getPlayer (), $this->get ( "rent-out-complete" ) );
				} else {
					$this->message ( $event->getPlayer (), $this->get ( "rent-breaking-time-over" ) );
				}
				if (isset ( $this->queue ["rentout"] [strtolower ( $event->getPlayer ()->getName () )] ))
					unset ( $this->queue ["rentout"] [strtolower ( $event->getPlayer ()->getName () )] );
				return;
			}
		}
		switch (true) {
			case ($event->getBlock ()->getID () == Block::SIGN_POST) :
				$sign = $event->getPlayer ()->getLevel ()->getTile ( $event->getBlock () );
				break;
			case ($event->getBlock ()->getSide ( Vector3::SIDE_UP )->getId () == Block::SIGN_POST) :
				$sign = $event->getPlayer ()->getLevel ()->getTile ( $event->getBlock ()->getSide ( Vector3::SIDE_UP ) );
				break;
		}
		if (isset ( $sign )) {
			if (! $sign instanceof Sign)
				return;
			$lines = $sign->getText ();
			
			if ($lines [0] != $this->get ( "automatic-post-1" ) or $lines [3] != $this->get ( "automatic-post-4" ))
				return;
			if ($lines [2] != $this->get ( "automatic-post-3" ))
				return;
			
			if (! $event->getPlayer ()->isOp ()) {
				$event->setCancelled ();
				$this->alert ( $event->getPlayer (), $this->get ( "automatic-post-is-only-delete-op" ) );
				return;
			}
			
			if (! isset ( $this->queue ["autoPostDelete"] [strtolower ( $event->getPlayer ()->getName () )] )) {
				$event->setCancelled ();
				$this->queue ["autoPostDelete"] [strtolower ( $event->getPlayer ()->getName () )] = $this->makeTimestamp ();
				$this->message ( $event->getPlayer (), $this->get ( "do-you-want-delete-automatic-post" ) );
				$this->message ( $event->getPlayer (), $this->get ( "if-you-want-to-delete-automatic-post-do-again" ) );
				return;
			}
			$before = $this->queue ["autoPostDelete"] [strtolower ( $event->getPlayer ()->getName () )];
			$after = $this->makeTimestamp ();
			$timeout = intval ( $after - $before );
			
			if ($timeout <= 10) {
				$size = explode ( "x", strtolower ( $lines [1] ) );
				$dmg = $event->getBlock ()->getDamage ();
				if ($dmg != 0 and $dmg != 4 and $dmg != 8 and $dmg != 12) {
					if ($dmg < 4) {
						$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 0 ) );
					} else if ($dmg < 8) {
						$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 4 ) );
					} else if ($dmg < 12) {
						$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 8 ) );
					} else {
						$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 12 ) );
					}
				}
				$this->areaManager->destructPrivateArea ( $event->getPlayer (), $event->getBlock (), $size [0], $size [1], $event->getBlock ()->y - 1, $dmg );
				$this->message ( $event->getPlayer (), $this->get ( "automatic-post-deleted" ) );
			} else {
				$event->setCancelled ();
				$this->message ( $event->getPlayer (), $this->get ( "automatic-post-time-over" ) );
			}
			if (isset ( $this->queue ["autoPostDelete"] [strtolower ( $event->getPlayer ()->getName () )] ))
				unset ( $this->queue ["autoPostDelete"] [strtolower ( $event->getPlayer ()->getName () )] );
			return;
		}
		$this->onBlockChangeEvent ( $event );
	}
	public function onSignChangeEvent(SignChangeEvent $event) {
		$area = $this->areaProvider->getArea ( $event->getBlock ()->level, $event->getBlock ()->x, $event->getBlock ()->z );
		if ($area instanceof AreaSection) {
			$rent = $this->rentProvider->getRent ( $event->getBlock ()->level, $event->getBlock ()->x, $event->getBlock ()->y, $event->getBlock ()->z );
			if (($rent instanceof RentSection) and $rent->isBuySingNull ()) {
				if (! $area->isOwner ( $event->getPlayer ()->getName () )) {
					$this->alert ( $event->getPlayer (), $this->get ( "youre-not-owner" ) );
					return;
				}
				$event->setLine ( 0, TextFormat::LIGHT_PURPLE . $this->get ( "rent-buy-sign-1" ) );
				$event->setLine ( 1, TextFormat::LIGHT_PURPLE . $this->get ( "rent-buy-sign-2" ) );
				$event->setLine ( 2, TextFormat::LIGHT_PURPLE . $this->get ( "rent-buy-sign-3" ) );
				$event->setLine ( 3, TextFormat::LIGHT_PURPLE . $this->get ( "rent-buy-sign-4" ) );
				
				$rent->setBuySignPos ( $event->getBlock ()->x, $event->getBlock ()->y, $event->getBlock ()->z );
				$this->message ( $event->getPlayer (), $this->get ( "rent-buy-sign-set-complete" ) );
				return;
			}
		}
		if ($event->getLine ( 0 ) == $this->get ( "automatic-post-1" ))
			if ($event->getLine ( 2 ) == $this->get ( "automatic-post-3" ))
				if ($event->getLine ( 3 ) == $this->get ( "automatic-post-4" ))
					if (! $event->getPlayer ()->isOp ()) {
						$this->alert ( $event->getPlayer (), $this->get ( "automatic-post-is-only-create-op" ) );
						$event->setCancelled ();
						return;
					}
		if ($event->getLine ( 0 ) == $this->get ( "easy-automatic-post" )) {
			if (! $event->getPlayer ()->isOp ()) {
				$this->alert ( $event->getPlayer (), $this->get ( "automatic-post-is-only-create-op" ) );
				$event->setCancelled ();
				return;
			}
			if ($event->getBlock ()->getId () != Block::SIGN_POST) {
				$this->alert ( $event->getPlayer (), $this->get ( "wall-sign-doesnt-use-it" ) );
				$event->setCancelled ();
				return;
			}
			$size = strtolower ( $event->getLine ( 1 ) );
			$size = explode ( "x", $size );
			if (! isset ( $size [1] ) or ! is_numeric ( $size [0] ) or ! is_numeric ( $size [1] )) {
				$this->alert ( $event->getPlayer (), $this->get ( "automatic-post-fail-size-problem" ) );
				$event->setCancelled ();
				return;
			}
			$dmg = $event->getBlock ()->getDamage ();
			if ($dmg != 0 and $dmg != 4 and $dmg != 8 and $dmg != 12) {
				if ($dmg < 4) {
					$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 0 ) );
				} else if ($dmg < 8) {
					$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 4 ) );
				} else if ($dmg < 12) {
					$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 8 ) );
				} else {
					$event->getBlock ()->getLevel ()->setBlock ( $event->getBlock (), Block::get ( Block::SIGN_POST, 12 ) );
				}
			}
			$bool = $this->areaManager->initialPrivateArea ( $event->getPlayer (), $event->getBlock (), $size [0], $size [1], $event->getBlock ()->y - 1, $dmg );
			if (! $bool) {
				$event->setCancelled ();
				return;
			}
			$event->setLine ( 0, $this->get ( "automatic-post-1" ) );
			$event->setLine ( 1, $size [0] . $this->get ( "automatic-post-2" ) . $size [1] );
			$event->setLine ( 2, $this->get ( "automatic-post-3" ) );
			$event->setLine ( 3, $this->get ( "automatic-post-4" ) );
			return;
		}
		$this->onBlockChangeEvent ( $event );
	}
	public function onBlockChangeEvent(Event $event) {
		if ($event->getPlayer ()->isOp ())
			return;
		$area = $this->areaProvider->getArea ( $event->getBlock ()->getLevel (), $event->getBlock ()->x, $event->getBlock ()->z );
		if ($area instanceof AreaSection) {
			if ($area->isHome ())
				if ($area->isResident ( $event->getPlayer ()->getName () ))
					return;
			if ($area->isOwner ( $event->getPlayer ()->getName () ))
				return;
			$rent = $this->rentProvider->getRent ( $event->getBlock ()->getLevel (), $event->getBlock ()->x, $event->getBlock ()->y, $event->getBlock ()->z );
			if ($rent instanceof RentSection)
				if ($rent->isOwner ( $event->getPlayer ()->getName () ))
					return;
			if ($area->isProtected ()) {
				if ($area->isAllowOption ( $event->getBlock ()->getId (), $event->getBlock ()->getDamage () ))
					return;
				switch (true) {
					case $event instanceof BlockPlaceEvent :
						$type = AreaModifyEvent::PLACE_PROTECT_AREA;
						break;
					case $event instanceof BlockBreakEvent :
						$type = AreaModifyEvent::BREAK_PROTECT_AREA;
						break;
					case $event instanceof SignChangeEvent :
						$type = AreaModifyEvent::SIGN_CHANGE_PROTECT_AREA;
						break;
				}
				if (isset ( $type )) {
					$ev = new AreaModifyEvent ( $event->getPlayer (), $event->getBlock ()->getLevel ()->getFolderName (), $area->getId (), $event->getBlock (), $type );
					$this->plugin->getServer ()->getPluginManager ()->callEvent ( $ev );
					if ($ev->isCancelled ())
						return;
				}
				$event->setCancelled ();
				return;
			}
			if ($area->isForbidOption ( $event->getBlock ()->getId (), $event->getBlock ()->getDamage () )) {
				switch (true) {
					case $event instanceof BlockPlaceEvent :
						$type = AreaModifyEvent::PLACE_FORBID;
						break;
					case $event instanceof BlockBreakEvent :
						$type = AreaModifyEvent::BREAK_FORBID;
						break;
					case $event instanceof SignChangeEvent :
						$type = AreaModifyEvent::SIGN_CHANGE_FORBID;
						break;
				}
				if (isset ( $type )) {
					$ev = new AreaModifyEvent ( $event->getPlayer (), $event->getBlock ()->getLevel ()->getFolderName (), $area->getId (), $event->getBlock (), $type );
					$this->plugin->getServer ()->getPluginManager ()->callEvent ( $ev );
					if ($ev->isCancelled ())
						return;
				}
				$event->setCancelled ();
				return;
			}
			return;
		}
		
		$whiteWorld = $this->whiteWorldProvider->get ( $event->getBlock ()->getLevel () );
		if (! $whiteWorld instanceof WhiteWorldData)
			return;
		
		if ($whiteWorld->isProtected ()) {
			if ($whiteWorld->isAllowOption ( $event->getBlock ()->getId (), $event->getBlock ()->getDamage () ))
				return;
			switch (true) {
				case $event instanceof BlockPlaceEvent :
					$type = AreaModifyEvent::PLACE_WHITE;
					break;
				case $event instanceof BlockBreakEvent :
					$type = AreaModifyEvent::BREAK_WHITE;
					break;
				case $event instanceof SignChangeEvent :
					$type = AreaModifyEvent::SIGN_CHANGE_WHITE;
					break;
			}
			if (isset ( $type )) {
				$ev = new AreaModifyEvent ( $event->getPlayer (), $event->getBlock ()->getLevel ()->getFolderName (), null, $event->getBlock (), $type );
				$this->plugin->getServer ()->getPluginManager ()->callEvent ( $ev );
				if ($ev->isCancelled ())
					return;
			}
			$event->setCancelled ();
			return;
		}
		if ($whiteWorld->isForbidOption ( $event->getBlock ()->getId (), $event->getBlock ()->getDamage () )) {
			switch (true) {
				case $event instanceof BlockPlaceEvent :
					$type = AreaModifyEvent::PLACE_WHITE_FORBID;
					break;
				case $event instanceof BlockBreakEvent :
					$type = AreaModifyEvent::BREAK_WHITE_FORBID;
					break;
				case $event instanceof SignChangeEvent :
					$type = AreaModifyEvent::SIGN_CHANGE_WHITE_FORBID;
					break;
			}
			if (isset ( $type )) {
				$ev = new AreaModifyEvent ( $event->getPlayer (), $event->getBlock ()->getLevel ()->getFolderName (), null, $event->getBlock (), $type );
				$this->plugin->getServer ()->getPluginManager ()->callEvent ( $ev );
				if ($ev->isCancelled ())
					return;
			}
			$event->setCancelled ();
			return;
		}
	}
	public function onPlayerInteractEvent(PlayerInteractEvent $event) {
		if ($event->getAction () == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			if (isset ( $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] )) {
				$event->setCancelled ();
				$startLevel = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["startLevel"];
				if ($startLevel !== $event->getPlayer ()->getLevel ()->getFolderName ()) {
					$this->alert ( $event->getPlayer (), $this->get ( "manual-area-cant-cross-two-world" ) );
					$this->message ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
					return;
				}
				$startX = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"];
				if ($startX === null) {
					$this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"] = $event->getBlock ()->getX ();
					$this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["startZ"] = $event->getBlock ()->getZ ();
					$this->message ( $event->getPlayer (), $this->get ( "first-pos-choosed" ) );
					return;
				}
				$endX = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"];
				if ($endX === null) {
					$this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"] = $event->getBlock ()->getX ();
					$this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["endZ"] = $event->getBlock ()->getZ ();
					
					$pricePerBlock = $this->whiteWorldProvider->get ( $event->getPlayer ()->getLevel () )->getPricePerBlock ();
					
					$startX = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"];
					$endX = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"];
					$startZ = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["startZ"];
					$endZ = $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ["endZ"];
					
					$xSize = $endX - $startX;
					$zSize = $endZ - $startZ;
					
					$areaPrice = ($xSize * $zSize) * $pricePerBlock;
					$this->message ( $event->getPlayer (), $this->get ( "second-pos-choosed" ) );
					
					($event->getPlayer ()->isOp ()) ? $isHome = false : $isHome = true;
					$this->areaManager->manualCreate ( $event->getPlayer (), $startX, $endX, $startZ, $endZ, $isHome );
					if (isset ( $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] ))
						unset ( $this->queue ["manual"] [strtolower ( $event->getPlayer ()->getName () )] );
				}
			}
			if (isset ( $this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] )) {
				$event->setCancelled ();
				$startLevel = $this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["startLevel"];
				if ($startLevel !== $event->getPlayer ()->getLevel ()->getFolderName ()) {
					$this->alert ( $event->getPlayer (), $this->get ( "rent-area-cant-cross-two-world" ) );
					return;
				}
				
				$touched = $event->getBlock ();
				$area = $this->areaProvider->getArea ( $event->getPlayer ()->getLevel ()->getName (), $touched->x, $touched->z );
				if (! $area instanceof AreaSection) {
					$this->message ( $event->getPlayer (), $this->get ( "this-position-not-exist-area" ) );
					return;
				}
				
				$areaId = $this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["areaId"];
				if ($areaId != $area->getId ()) {
					$this->message ( $event->getPlayer (), $this->get ( "this-position-some-other-area-exist" ) );
					return;
				}
				
				$startX = $this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"];
				if ($startX === null) {
					$this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"] = $event->getBlock ()->getX ();
					$this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["startY"] = $event->getBlock ()->getY ();
					$this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["startZ"] = $event->getBlock ()->getZ ();
					$this->message ( $event->getPlayer (), $this->get ( "first-pos-choosed" ) );
					$this->message ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
					return;
				}
				
				$endX = $this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"];
				if ($endX === null) {
					$this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"] = $event->getBlock ()->getX ();
					$this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["endY"] = $event->getBlock ()->getY ();
					$this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["endZ"] = $event->getBlock ()->getZ ();
					
					$rentPrice = $this->queue ["rentCreate"] [strtolower ( $event->getPlayer ()->getName () )] ["rentPrice"];
					
					$this->message ( $event->getPlayer (), $this->get ( "second-pos-choosed" ) );
					$this->message ( $event->getPlayer (), $this->get ( "do-you-want-create-area" ) . $rentPrice );
					$this->message ( $event->getPlayer (), $this->get ( "if-you-want-to-create-try-again" ) );
					$this->message ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
					return;
				}
			}
			if (isset ( $this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] )) {
				$event->setCancelled ();
				$sizeUpData = $this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )];
				if (! $sizeUpData ["isTouched"]) {
					$area = $this->areaProvider->getAreaToId ( $sizeUpData ["startLevel"], $sizeUpData ["id"] );
					
					$startX = $area->get ( "startX" );
					$endX = $area->get ( "endX" );
					$startZ = $area->get ( "startZ" );
					$endZ = $area->get ( "endZ" );
					
					$touchX = $event->getBlock ()->x;
					$touchZ = $event->getBlock ()->z;
					
					$rstartX = 0;
					$rendX = 0;
					$rstartZ = 0;
					$rendZ = 0;
					
					if ($startX > $touchX) {
						$rstartX = $startX - $touchX;
					} else if ($endX < $touchX) {
						$rendX = $touchX - $endX;
					}
					if ($startZ > $touchZ) {
						$rstartZ = $startZ - $touchZ;
					} else if ($endZ < $touchZ) {
						$rendZ = $touchZ - $endZ;
					}
					
					if ($rstartX == 0 and $rendX == 0 and $rstartZ == 0 and $rendZ == 0) {
						$this->alert ( $event->getPlayer (), $this->get ( "you-need-touch-out-side" ) );
						$this->alert ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
						return;
					}
					
					$this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"] = $rstartX;
					$this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"] = $rendX;
					$this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] ["startZ"] = $rstartZ;
					$this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] ["endZ"] = $rendZ;
					$this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] ["isTouched"] = true;
					
					$resizePrice = 0;
					$xSize = $endX - $startX;
					$zSize = $endZ - $startZ;
					$whiteWorld = $this->whiteWorldProvider->get ( $sizeUpData ["startLevel"] );
					
					if (! $event->getPlayer ()->isOp ()) {
						if ($rstartX != 0)
							$resizePrice += (abs ( $rstartX * $zSize ) * $whiteWorld->getPricePerBlock ());
						if ($rendX != 0)
							$resizePrice += (abs ( $rendX * $zSize ) * $whiteWorld->getPricePerBlock ());
						if ($rstartZ != 0)
							$resizePrice += (abs ( $rstartZ * $xSize ) * $whiteWorld->getPricePerBlock ());
						if ($rendZ != 0)
							$resizePrice += (abs ( $rendZ * $xSize ) * $whiteWorld->getPricePerBlock ());
					}
					$this->queue ["areaSizeUp"] [strtolower ( $event->getPlayer ()->getName () )] ["resizePrice"] = $resizePrice;
					
					$this->message ( $event->getPlayer (), $this->get ( "do-you-want-size-up" ) . $resizePrice );
					$this->message ( $event->getPlayer (), $this->get ( "if-you-want-to-size-up-please-command" ) );
					$this->message ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
					return;
				}
			}
			if (isset ( $this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] )) {
				$event->setCancelled ();
				$sizeDownData = $this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )];
				if (! $sizeDownData ["isTouched"]) {
					$area = $this->areaProvider->getAreaToId ( $sizeDownData ["startLevel"], $sizeDownData ["id"] );
					
					$startX = $area->get ( "startX" );
					$endX = $area->get ( "endX" );
					$startZ = $area->get ( "startZ" );
					$endZ = $area->get ( "endZ" );
					
					$touchX = $event->getBlock ()->x;
					$touchZ = $event->getBlock ()->z;
					
					$rstartX = 0;
					$rendX = 0;
					$rstartZ = 0;
					$rendZ = 0;
					
					if ($startX < $touchX)
						$rstartX = $startX - $touchX;
					if ($endX > $touchX)
						$rendX = $touchX - $endX;
					if ($startZ < $touchZ)
						$rstartZ = $startZ - $touchZ;
					if ($endZ > $touchZ)
						$rendZ = $touchZ - $endZ;
					
					if ($rstartX >= 0 or $rendX >= 0 or $rstartZ >= 0 or $rendZ >= 0) {
						$this->alert ( $event->getPlayer (), $this->get ( "you-need-touch-in-side" ) );
						$this->alert ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
						return;
					}
					
					if ($rstartX > $rendX) {
						if ($rstartZ > $rendZ) {
							if ($rstartX > $rstartZ) {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"] = $rstartX;
							} else {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["startZ"] = $rstartZ;
							}
						} else { // $rstartZ < $rendZ
							if ($rstartX > $rendZ) {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["startX"] = $rstartX;
							} else {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["endZ"] = $rendZ;
							}
						}
					} else { // $rstartX < $rendX
						if ($rstartZ > $rendZ) {
							if ($rstartX > $rstartZ) {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"] = $rendX;
							} else {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["startZ"] = $rstartZ;
							}
						} else { // $rstartZ < $rendZ
							if ($rstartX > $rendZ) {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["endX"] = $rendX;
							} else {
								$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["endZ"] = $rendZ;
							}
						}
					}
					$this->queue ["areaSizeDown"] [strtolower ( $event->getPlayer ()->getName () )] ["isTouched"] = true;
					
					$this->message ( $event->getPlayer (), $this->get ( "do-you-want-size-down" ) );
					$this->message ( $event->getPlayer (), $this->get ( "if-you-want-to-size-down-please-command" ) );
					$this->message ( $event->getPlayer (), $this->get ( "you-can-stop-create-manual-area" ) );
					return;
				}
			}
			$rent = $this->rentProvider->getRent ( $event->getBlock ()->getLevel (), $event->getBlock ()->x, $event->getBlock ()->y, $event->getBlock ()->z );
			$area = $this->areaProvider->getArea ( $event->getBlock ()->getLevel (), $event->getBlock ()->x, $event->getBlock ()->z );
			if ($rent instanceof RentSection and $area instanceof AreaSection) {
				$buySignPos = $rent->getBuySignPos ();
				$buySignPosString = "{$buySignPos->x}:{$buySignPos->y}:{$buySignPos->z}";
				
				$blockPos = $event->getBlock ();
				$blockPosString = "{$blockPos->x}:{$blockPos->y}:{$blockPos->z}";
				
				if ($buySignPosString == $blockPosString) {
					$event->setCancelled ();
					if ($area->getOwner () == strtolower ( $event->getPlayer ()->getName () )) {
						$this->message ( $event->getPlayer (), $this->get ( "cant-owner-self-buying-rent-area" ) );
						return;
					}
					if (! $rent->isCanBuy ()) {
						if ($rent->getOwner () == strtolower ( $event->getPlayer ()->getName () )) {
							$this->message ( $event->getPlayer (), $this->get ( "this-rent-area-already-sold-you" ) );
						} else {
							$this->message ( $event->getPlayer (), $this->get ( "this-rent-area-already-sold" ) );
						}
						$this->message ( $event->getPlayer (), $this->get ( "this-rent-is-already-owner-exist" ) . $rent->getOwner () );
						return;
					}
					if (! isset ( $this->queue ["rentBuy"] [strtolower ( $event->getPlayer ()->getName () )] )) {
						$this->queue ["rentBuy"] [strtolower ( $event->getPlayer ()->getName () )] = [ 
								"time" => $this->makeTimestamp () 
						];
						$this->message ( $event->getPlayer (), $this->get ( "if-you-want-to-buying-this-rent-touch-again" ) );
						$this->message ( $event->getPlayer (), $this->get ( "rent-hour-per-price" ) . $rent->getPrice () );
					} else {
						$before = $this->queue ["rentBuy"] [strtolower ( $event->getPlayer ()->getName () )] ["time"];
						$after = $this->makeTimestamp ();
						$timeout = intval ( $after - $before );
						
						if ($timeout > 6) {
							if (isset ( $this->queue ["rentBuy"] [strtolower ( $event->getPlayer ()->getName () )] ))
								unset ( $this->queue ["rentBuy"] [strtolower ( $event->getPlayer ()->getName () )] );
							$this->message ( $event->getPlayer (), $this->get ( "rent-buying-time-over" ) );
							return;
						}
						
						$rent->buy ( $event->getPlayer () );
					}
				}
			}
			if ($event->getBlock ()->getID () == Block::SIGN_POST) {
				$sign = $event->getPlayer ()->getLevel ()->getTile ( $event->getBlock () );
				if (! $sign instanceof Sign)
					return;
				
				$lines = $sign->getText ();
				if ($lines [0] != $this->get ( "automatic-post-1" ) or $lines [3] != $this->get ( "automatic-post-4" ))
					return;
				if ($lines [2] != $this->get ( "automatic-post-3" ))
					return;
				
				$event->setCancelled ();
				
				$size = explode ( $this->get ( "automatic-post-2" ), $lines [1] );
				if (! isset ( $size [1] ) or ! is_numeric ( $size [0] ) or ! is_numeric ( $size [1] )) {
					$this->alert ( $event->getPlayer (), $this->get ( "automatic-post-fail-size-problem" ) );
					return;
				}
				
				$xSize = ($size [0] - 1);
				$zSize = ($size [1] - 1);
				
				switch ($sign->getBlock ()->getDamage ()) {
					case 0 : // 63:0 x+ z- XxZ
						$startX = ($event->getBlock ()->x + 1);
						$startZ = ($event->getBlock ()->z - 1);
						$endX = ($event->getBlock ()->x + $xSize - 1);
						$endZ = ($event->getBlock ()->z - $zSize + 1);
						break;
					case 4 : // 63:4 x+ z+ ZxX
						$startX = ($event->getBlock ()->x + 1);
						$startZ = ($event->getBlock ()->z + 1);
						$endX = ($event->getBlock ()->x + $zSize - 1);
						$endZ = ($event->getBlock ()->z + $xSize - 1);
						break;
					case 8 : // 63:8 x- z+ XxZ
						$startX = ($event->getBlock ()->x - 1);
						$startZ = ($event->getBlock ()->z + 1);
						$endX = ($event->getBlock ()->x - $xSize + 1);
						$endZ = ($event->getBlock ()->z + $zSize - 1);
						break;
					case 12 : // 63:12 x- z- ZxX
						$startX = ($event->getBlock ()->x - 1);
						$startZ = ($event->getBlock ()->z - 1);
						$endX = ($event->getBlock ()->x - $zSize + 1);
						$endZ = ($event->getBlock ()->z - $xSize + 1);
						break;
					default :
						$this->alert ( $event->getPlayer (), $this->get ( "automatic-post-fail-sign-problem" ) );
						return;
				}
				$level = $event->getBlock ()->getLevel ();
				
				$owner = "";
				$isHome = true;
				
				$area = $this->areaProvider->addArea ( $level, $startX, $endX, $startZ, $endZ, $owner, $isHome, false );
				
				if ($area instanceof AreaSection) {
					$this->message ( $event->getPlayer (), $area->getId () . $this->get ( "automatic-post-complete" ) );
					$this->message ( $event->getPlayer (), $this->get ( "you-can-use-area-buy-command" ) );
				} else {
					$overlap = $this->areaProvider->checkOverlap ( $level, $startX, $endX, $startZ, $endZ );
					if ($overlap instanceof AreaSection) {
						$this->message ( $event->getPlayer (), $overlap->getId () . $this->get ( "automatic-post-fail-overlap-problem" ) );
					} else {
						$this->message ( $event->getPlayer (), $this->get ( "automatic-post-fail-etc-problem-1" ) );
						$this->message ( $event->getPlayer (), $this->get ( "automatic-post-fail-etc-problem-2" ) );
					}
				}
				return;
			}
			if (! $event->getItem ()->canBeActivated () and ! $event->getBlock ()->canBeActivated ()) {
				if ($event->getPlayer ()->isOp ())
					return;
				if ($event->getBlock ()->getId () == Block::SIGN_POST)
					return;
				if ($event->getBlock ()->getId () == Block::WALL_SIGN)
					return;
				if ($event->getBlock ()->getId () == Block::CRAFTING_TABLE)
					return;
				if ($event->getBlock ()->getId () == Block::FURNACE)
					return;
			}
			$this->onBlockChangeEvent ( $event );
		}
	}
	public function onPlayerQuitEvent(PlayerQuitEvent $event) {
		$userName = strtolower ( $event->getPlayer ()->getName () );
		if (isset ( $this->queue ["movePos"] [$userName] ))
			unset ( $this->queue ["movePos"] [$userName] );
	}
	public function onPlayerMoveEvent(PlayerMoveEvent $event) {
		$userName = strtolower ( $event->getPlayer ()->getName () );
		if (! isset ( $this->queue ["movePos"] [$userName] )) {
			$this->queue ["movePos"] [$userName] = [ 
					"x" => ( int ) round ( $event->getPlayer ()->x ),
					"z" => ( int ) round ( $event->getPlayer ()->z ),
					"areaId" => null,
					"rentId" => null 
			];
			return;
		}
		$diff = abs ( ( int ) round ( $event->getPlayer ()->x - $this->queue ["movePos"] [$userName] ["x"] ) );
		$diff += abs ( ( int ) round ( $event->getPlayer ()->z - $this->queue ["movePos"] [$userName] ["z"] ) );
		if ($diff > 4) {
			$this->queue ["movePos"] [$userName] ["x"] = ( int ) round ( $event->getPlayer ()->x );
			$this->queue ["movePos"] [$userName] ["z"] = ( int ) round ( $event->getPlayer ()->z );
			
			$area = $this->areaProvider->getArea ( $event->getPlayer ()->getLevel (), $event->getPlayer ()->x, $event->getPlayer ()->z );
			if ($area instanceof AreaSection) {
				if (! $event->getPlayer ()->isOp ())
					if ($area->isAccessDeny () and ! $area->isResident ( $event->getPlayer ()->getName () )) {
						$x = $area->get ( "startX" ) - 2;
						$z = $area->get ( "startZ" ) - 2;
						$y = $event->getPlayer ()->getLevel ()->getHighestBlockAt ( $x, $z );
						$event->getPlayer ()->teleport ( new Vector3 ( $x, $y, $z ) );
						$this->alert ( $event->getPlayer (), $this->get ( "this-area-is-only-can-access-resident" ) );
					}
				if ($this->queue ["movePos"] [$userName] ["areaId"] != $area->getId ()) {
					$welcomeMsg = $area->getWelcome ();
					if ($area->isHome ()) {
						if ($area->isOwner ( $userName )) {
							$this->message ( $event->getPlayer (), $this->get ( "welcome-area-sir" ) );
							if ($welcomeMsg == null)
								$this->message ( $event->getPlayer (), $this->get ( "please-set-to-welcome-msg" ) );
						} else {
							if ($area->getOwner () != "")
								$this->message ( $event->getPlayer (), $this->get ( "here-is" ) . $area->getOwner () . $this->get ( "his-land" ) );
							if ($welcomeMsg != null)
								$this->message ( $event->getPlayer (), $welcomeMsg, $this->get ( "welcome-prefix" ) );
						}
					}
					if (! $area->isHome () and $event->getPlayer ()->isOp ()) {
						$this->message ( $event->getPlayer (), $this->get ( "welcome-op-area-sir" ) );
						if ($welcomeMsg == null)
							$this->message ( $event->getPlayer (), $this->get ( "please-set-to-welcome-msg" ) );
					}
					if ($area->isCanBuy ())
						$this->message ( $event->getPlayer (), $this->get ( "you-can-buy-here" ) . $area->getPrice () . " " . $this->get ( "show-buy-command" ) );
					$this->queue ["movePos"] [$userName] ["areaId"] = $area->getId ();
				}
			}
			$rent = $this->rentProvider->getRent ( $event->getPlayer ()->getLevel (), $event->getPlayer ()->x, $event->getPlayer ()->y, $event->getPlayer ()->z );
			if ($rent instanceof RentSection) {
				if ($this->queue ["movePos"] [$userName] ["rentId"] != $rent->getRentId ()) {
					$welcomeMsg = $rent->getWelcome ();
					if ($welcomeMsg != null)
						$this->message ( $event->getPlayer (), $welcomeMsg, $this->get ( "welcome-prefix" ) );
					
					if ($rent->isOwner ( $userName )) {
						$this->message ( $event->getPlayer (), $this->get ( "welcome-rent-area-sir" ) );
						if ($welcomeMsg == null)
							$this->message ( $event->getPlayer (), $this->get ( "please-set-to-welcome-msg" ) );
					}
					$this->queue ["movePos"] [$userName] ["rentId"] = $rent->getRentId ();
				}
			}
		}
	}
	public function onEntityDamageEvent(EntityDamageEvent $event) {
		if (! $event instanceof EntityDamageByEntityEvent)
			return;
		
		if (! $event->getDamager () instanceof Player)
			return;
		
		$player = $event->getEntity ();
		if (! $player instanceof Player)
			return;
		
		$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
		
		if ($area instanceof AreaSection) {
			if (! $area->isPvpAllow ()) {
				$event->setCancelled ();
			}
		} else {
			$whiteWorld = $this->whiteWorldProvider->get ( $player->getLevel () );
			if ($whiteWorld instanceof WhiteWorldData) {
				if (! $whiteWorld->isPvpAllow ())
					$event->setCancelled ();
			}
		}
	}
	public function onEntityCombustEvent(EntityCombustEvent $event) {
		if (! $event instanceof EntityCombustByBlockEvent)
			return;
		
		if (! $event->getCombuster () instanceof Fire)
			return;
		
		$player = $event->getEntity ();
		if (! $player instanceof Player)
			return;
		
		$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
		
		if ($area instanceof AreaSection) {
			if (! $area->isPvpAllow ()) {
				$event->setCancelled ();
			}
		} else {
			$whiteWorld = $this->whiteWorldProvider->get ( $player->getLevel () );
			if ($whiteWorld instanceof WhiteWorldData) {
				if (! $whiteWorld->isPvpAllow ())
					$event->setCancelled ();
			}
		}
	}
	public function onPlayerDeathEvent(PlayerDeathEvent $event) {
		$player = $event->getEntity ();
		
		$area = $this->areaProvider->getArea ( $player->getLevel (), $player->x, $player->z );
		if ($area instanceof AreaSection) {
			if ($area->isInvenSave ())
				$event->setKeepInventory ( true );
			return;
		}
		$whiteWorld = $this->whiteWorldProvider->get ( $player->getLevel () );
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isInvenSave ())
				$event->setKeepInventory ( true );
			return;
		}
	}
	public function onBlockUpdateEvent(BlockUpdateEvent $event) {
		if ($event->getBlock ()->getId () != 8 and $event->getBlock ()->getId () != 10)
			return;
		$updatedArea = $this->areaProvider->getArea ( $event->getBlock ()->getLevel (), $event->getBlock ()->x, $event->getBlock ()->z );
		if (! $updatedArea instanceof AreaSection)
			return;
		$sides = [ 
				Vector3::SIDE_EAST,
				Vector3::SIDE_WEST,
				Vector3::SIDE_NORTH,
				Vector3::SIDE_SOUTH 
		];
		foreach ( $sides as $side ) {
			$block = $event->getBlock ()->getSide ( $side );
			$searchArea = $this->areaProvider->getArea ( $block->getLevel (), $block->x, $block->z );
			if ($searchArea instanceof AreaSection) {
				// overflow (area->other area) -> prevent
				// (overflow (home->other home) -> prevent)
				// (overflow (op area->other home) -> prevent)
				// (overflow (home->other op area) -> prevent)
				if ($searchArea->getId () != $updatedArea->getId ()) {
					$event->setCancelled ();
					$event->getBlock ()->getLevel ()->setBlock ( $block, $block );
					if (isset ( $this->waterLists [$updatedArea->getId ()] )) {
						foreach ( $this->waterLists [$updatedArea->getId ()] as $key => $value ) {
							$pos = explode ( ":", $key );
							$pos = new Vector3 ( $pos [0], $pos [1], $pos [2] );
							if ($block->getLevel ()->getBlockIdAt ( $pos->x, $pos->y, $pos->z ) == 8 or $block->getLevel ()->getBlockIdAt ( $pos->x, $pos->y, $pos->z ) == 10)
								$block->getLevel ()->setBlock ( $pos, Block::get ( Block::AIR ) );
						}
						unset ( $this->waterLists [$updatedArea->getId ()] );
					}
					break;
				}
			} else {
				// overflow (none->home) -> prevent
				// (it is same overflow (home->none) prevent)
				$event->setCancelled ();
				$this->waterAbsorption ( $event->getBlock () );
				if (isset ( $this->waterLists [$updatedArea->getId ()] )) {
					foreach ( $this->waterLists [$updatedArea->getId ()] as $key => $value ) {
						$pos = explode ( ":", $key );
						$pos = new Vector3 ( $pos [0], $pos [1], $pos [2] );
						if ($block->getLevel ()->getBlockIdAt ( $pos->x, $pos->y, $pos->z ) == 8 or $block->getLevel ()->getBlockIdAt ( $pos->x, $pos->y, $pos->z ) == 10)
							$block->getLevel ()->setBlock ( $pos, Block::get ( Block::AIR ) );
					}
					unset ( $this->waterLists [$updatedArea->getId ()] );
				}
			}
		}
		if (! $event->isCancelled ()) {
			if ($block->getLevel ()->getBlockIdAt ( $block->x, $block->y + 1, $block->z ) == 8)
				$this->waterLists [$updatedArea->getId ()] [$block->x . ":" . ($block->y + 1) . ":" . $block->z] = true;
			if ($block->getLevel ()->getBlockIdAt ( $block->x, $block->y - 1, $block->z ) == 8)
				$this->waterLists [$updatedArea->getId ()] [$block->x . ":" . ($block->y - 1) . ":" . $block->z] = true;
			if ($block->getLevel ()->getBlockIdAt ( $block->x + 1, $block->y, $block->z ) == 8)
				$this->waterLists [$updatedArea->getId ()] [($block->x + 1) . ":" . $block->y . ":" . $block->z] = true;
			if ($block->getLevel ()->getBlockIdAt ( $block->x - 1, $block->y, $block->z ) == 8)
				$this->waterLists [$updatedArea->getId ()] [($block->x - 1) . ":" . $block->y . ":" . $block->z] = true;
			if ($block->getLevel ()->getBlockIdAt ( $block->x, $block->y, $block->z + 1 ) == 8)
				$this->waterLists [$updatedArea->getId ()] [$block->x . ":" . $block->y . ":" . ($block->z + 1)] = true;
			if ($block->getLevel ()->getBlockIdAt ( $block->x, $block->y, $block->z - 1 ) == 8)
				$this->waterLists [$updatedArea->getId ()] [$block->x . ":" . $block->y . ":" . ($block->z - 1)] = true;
			if ($block->getLevel ()->getBlockIdAt ( $block->x, $block->y, $block->z ) == 8)
				$this->waterLists [$updatedArea->getId ()] [$block->x . ":" . $block->y . ":" . $block->z] = true;
		}
	}
	public function waterAbsorption(Block $block) {
		$result = $this->checkWaterAbsorption ( $block, [ 
				"nestingDepth" => 0 
		] );
		$nestingDepth = 0;
		foreach ( $result as $pos => $bool ) {
			$nestingDepth ++;
			if ($nestingDepth >= 20)
				break;
			$pos = explode ( ":", $pos );
			if (isset ( $pos [2] ))
				$block->getLevel ()->setBlock ( new Vector3 ( $pos [0], $pos [1], $pos [2] ), Block::get ( Block::AIR ) );
		}
	}
	public function checkWaterAbsorption(Block $block, $data) {
		$data ["nestingDepth"] ++;
		if ($data ["nestingDepth"] >= 20)
			return $data;
		$sides = [ 
				Vector3::SIDE_EAST,
				Vector3::SIDE_WEST,
				Vector3::SIDE_NORTH,
				Vector3::SIDE_SOUTH,
				Vector3::SIDE_UP,
				Vector3::SIDE_DOWN 
		];
		$blockPos = "{$block->x}:{$block->y}:{$block->z}";
		if (! isset ( $data [$blockPos] ))
			$data [$blockPos] = true;
		foreach ( $sides as $side ) {
			if ($data ["nestingDepth"] >= 20)
				break;
			$sideBlock = $block->getSide ( $side );
			$sideBlockPos = "{$sideBlock->x}:{$sideBlock->y}:{$sideBlock->z}";
			if (isset ( $data [$sideBlockPos] ))
				continue;
			$id = $sideBlock->getId ();
			if ($id == 8 or $id == 9 or $id == 10 or $id == 11) {
				$data [$sideBlockPos] = true;
				$returns = $this->checkWaterAbsorption ( $sideBlock, $data );
				if ($returns ["nestingDepth"] >= 20)
					break;
				foreach ( $returns as $returnPos => $bool )
					if (! isset ( $data [$returnPos] ))
						$data [$returnPos] = true;
			}
		}
		return $data;
	}
	public function get($var) {
		return $this->plugin->get ( $var );
	}
	public function message(CommandSender $player, $text = "", $mark = null) {
		$this->plugin->message ( $player, $text, $mark );
	}
	public function alert(CommandSender $player, $text = "", $mark = null) {
		$this->plugin->alert ( $player, $text, $mark );
	}
	public function makeTimestamp($date = null) {
		if ($date === null)
			$date = date ( "Y-m-d H:i:s" );
		$yy = substr ( $date, 0, 4 );
		$mm = substr ( $date, 5, 2 );
		$dd = substr ( $date, 8, 2 );
		$hh = substr ( $date, 11, 2 );
		$ii = substr ( $date, 14, 2 );
		$ss = substr ( $date, 17, 2 );
		return mktime ( $hh, $ii, $ss, $mm, $dd, $yy );
	}
}

?>