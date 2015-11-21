<?php

namespace ifteam\SimpleArea\database\minefarm;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\level\Position;
use ifteam\SimpleArea\database\minefarm\MineFarmLoader;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\area\AreaSection;
use ifteam\SimpleArea\database\area\AreaManager;
use pocketmine\Player;
use ifteam\SimpleArea\SimpleArea;
use ifteam\SimpleArea\database\user\UserProperties;

class MineFarmManager {
	private static $instance = null;
	private $plugin;
	/**
	 *
	 * @var MineFarmLoader
	 */
	private $mineFarmLoader;
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
	 * @var \onebone\economyapi\EconomyAPI
	 */
	private $economy;
	/**
	 *
	 * @var Server
	 */
	private $server;
	/**
	 *
	 * @var UserProperties
	 */
	private $properties;
	public function __construct(SimpleArea $plugin) {
		if (self::$instance == null)
			self::$instance = $this;
		$this->plugin = $plugin;
		$this->mineFarmLoader = new MineFarmLoader ( $plugin );
		$this->areaProvider = AreaProvider::getInstance ();
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance ();
		$this->properties = UserProperties::getInstance ();
		$this->economy = $this->plugin->otherApi->economyAPI->getPlugin ();
		$this->server = Server::getInstance ();
	}
	public function start(CommandSender $player) {
		$bool = $this->mineFarmLoader->createWorld ();
		if ($bool) {
			$this->message ( $player, $this->get ( "minefarm-create-complete" ) );
			$this->message ( $player, $this->get ( "minefarm-you-can-buy-minefarm" ) );
		} else {
			$this->alert ( $player, $this->get ( "minefarm-already-created" ) );
			return false;
		}
		return true;
	}
	public function buy(CommandSender $player) {
		$level = $this->server->getLevelByName ( "minefarm" );
		if (! $level instanceof Level) {
			$this->message ( $player, $this->get ( "minefarm-not-exist" ) );
			if ($player->isOp ())
				$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		$price = $this->whiteWorldProvider->get ( "minefarm" )->getDefaultAreaPrice ();
		if ($this->economy !== null and ! $player->isOp ()) {
			$money = $this->economy->myMoney ( $player );
			if ($money < $price) {
				$this->alert ( $player, "not-enough-money" );
				return false;
			}
		}
		$areaHoldLimit = $this->whiteWorldProvider->get ( "minefarm" )->getAreaHoldLimit ();
		$userHoldCount = count ( $this->properties->getUserProperties ( $player->getName (), "minefarm" ) );
		if (! $player->isOp ()) {
			if ($userHoldCount >= $areaHoldLimit) {
				$this->alert ( $player, $this->get ( "no-more-buying-area" ) );
				return false;
			}
		}
		if ($this->economy !== null and ! $player->isop ()) {
			$this->economy->reduceMoney ( $player, $price );
		}
		$areaId = $this->mineFarmLoader->addMineFarm ( $player );
		$this->message ( $player, $areaId . " " . $this->get ( "minefarm-buying-that-minefarm" ) );
		$this->message ( $player, $this->get ( "minefarm-you-can-teleport" ) );
		return true;
	}
	public function delete(CommandSender $player) {
		$level = $this->server->getLevelByName ( "minefarm" );
		if (! $level instanceof Level) {
			$this->message ( $player, $this->get ( "minefarm-not-exist" ) );
			if ($player->isOp ())
				$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		if (! $player instanceof Player) {
			$this->alert ( $player, $this->get ( "minefarm-ingame-only" ) );
			return false;
		}
		AreaManager::getInstance ()->delete ( $player );
		return true;
	}
	public function move(CommandSender $player, $target) {
		$level = $this->server->getLevelByName ( "minefarm" );
		if (! $level instanceof Level) {
			$this->message ( $player, $this->get ( "minefarm-not-exist" ) );
			if ($player->isOp ())
				$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		if (! $player instanceof Player) {
			$this->alert ( $player, $this->get ( "minefarm-ingame-only" ) );
			return false;
		}
		if (! is_numeric ( $target )) {
			$list = $this->mineFarmLoader->getMineFarmList ( $target );
			$this->message ( $player, $this->get ( "minefarm-show-minefarm-list" ) );
			$listString = "";
			foreach ( $list as $item => $bool )
				$listString .= "<{$item}> ";
			$this->message ( $player, $listString, "" );
			return false;
		}
		$area = $this->areaProvider->getAreaToId ( "minefarm", $target );
		if (! $area instanceof AreaSection) {
			$this->alert ( $player, $this->get ( "minefarm-farm-not-exist" ) );
			if ($player->isOp ())
				$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		$areaCenter = $area->getCenter ();
		if ($player->getLevel ()->getFolderName () !== "minefarm") {
			$player->teleport ( new Position ( $areaCenter->x, $areaCenter->y, $areaCenter->z, $level ) );
		} else {
			$player->teleport ( $areaCenter );
		}
		return true;
	}
	public function getList(CommandSender $player) {
		$level = $this->server->getLevelByName ( "minefarm" );
		if (! $level instanceof Level) {
			$this->message ( $player, $this->get ( "minefarm-not-exist" ) );
			if ($player->isOp ())
				$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		$list = $this->mineFarmLoader->getMineFarmList ( $player );
		$this->message ( $player, $this->get ( "minefarm-show-minefarm-list" ) );
		$listString = "";
		foreach ( $list as $item => $bool )
			$listString .= "<{$item}> ";
		$this->message ( $player, $listString, "" );
		return true;
	}
	public function setPrice(CommandSender $player, $price) {
		$level = $this->server->getLevelByName ( "minefarm" );
		if (! $level instanceof Level) {
			$this->message ( $player, $this->get ( "minefarm-not-exist" ) );
			$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		if (! is_numeric ( $price )) {
			$this->alert ( $player, $this->get ( "minefarm-areaprice-must-be-numeric" ) );
			return false;
		}
		$this->whiteWorldProvider->get ( "minefarm" )->setDefaultAreaPrice ( $price );
		$this->message ( $player, $this->get ( "minefarm-farm-price-changed" ) );
		return true;
	}
	public function farmHoldLimit(CommandSender $player, $limit) {
		$level = $this->server->getLevelByName ( "minefarm" );
		if (! $level instanceof Level) {
			$this->message ( $player, $this->get ( "minefarm-not-exist" ) );
			$this->message ( $player, $this->get ( "minefarm-can-create-minefarm" ) );
			return false;
		}
		if (! is_numeric ( $limit )) {
			$this->message ( $player, $this->get ( "minefarm-holdlimit-must-be-numeric" ) );
			return false;
		}
		$this->whiteWorldProvider->get ( "minefarm" )->setAreaHoldLimit ( $limit );
		$this->message ( $player, $this->get ( "minefarm-holdlimit-changed" ) );
		return true;
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
	/**
	 *
	 * @return MineFarmManager
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>
