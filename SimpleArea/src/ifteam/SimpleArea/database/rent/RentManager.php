<?php

namespace ifteam\SimpleArea\database\rent;

use ifteam\SimpleArea\SimpleArea;
use ifteam\SimpleArea\database\user\UserProperties;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RentManager {
	private static $instance = null;
	private $plugin;
	/**
	 *
	 * @var RentProvider
	 */
	private $rentProvider;
	/**
	 *
	 * @var \onebone\economyapi\EconomyAPI
	 */
	private $economy;
	/**
	 *
	 * @var UserProperties
	 */
	private $properties;
	public function __construct(SimpleArea $plugin) {
		if (self::$instance == null)
			self::$instance = $this;
		$this->plugin = $plugin;
		$this->rentProvider = RentProvider::getInstance ();
		$this->properties = UserProperties::getInstance ();
		$this->economy = $this->plugin->otherApi->economyAPI->getPlugin ();
	}
	public function move(Player $player, $target) {
		if (is_numeric ( $target )) {
			$rent = $this->rentProvider->getRentToId ( $player->getLevel (), $target );
			if ($rent instanceof RentSection) {
				$rentData = $rent->getAll ();
				$x = $rentData ["startX"] + 1;
				$y = $rentData ["startY"] + 1;
				$z = $rentData ["startZ"] + 1;
				$player->teleport ( new Vector3 ( $x, $y, $z ) );
			} else {
				$this->alert ( $player, $this->get ( "rent-number-doesent-exist" ) );
				return false;
			}
		} else {
			$properties = $this->properties->getUserRentProperties ( $target, $player->getLevel () );
			$this->message ( $player, $this->get ( "show-the-rents-with-the-user" ) );
			$listString = "";
			foreach ( $properties as $propertie => $bool )
				$listString .= "<{$propertie}> ";
			$this->message ( $player, $listString, "" );
			return false;
		}
		return true;
	}
	public function getList(Player $player) {
		$properties = $this->properties->getUserRentProperties ( $player->getName (), $player->getLevel ()->getName () );
		$this->message ( $player, $this->get ( "show-the-rents" ) );
		$listString = "";
		foreach ( $properties as $propertie => $bool )
			$listString .= "<{$propertie}> ";
		$this->message ( $player, $listString, "" );
	}
	public function create(Player $player, $level, $startX, $endX, $startY, $endY, $startZ, $endZ, $areaId, $rentPrice) {
		$checkOverlap = $this->rentProvider->checkOverlap ( $level, $startX, $endX, $startY, $endY, $startZ, $endZ );
		if ($checkOverlap === null) {
			$this->rentProvider->addRent ( $level, $startX, $endX, $startY, $endY, $startZ, $endZ, $areaId, $rentPrice );
			$this->message ( $player, $this->get ( "rent-create-success" ) );
			$this->message ( $player, $this->get ( "please-set-rent-sign" ) );
			$this->message ( $player, $this->get ( "rent-sign-how-to-set" ) );
		} else {
			$this->message ( $player, $this->get ( "rent-create-failed-rent-is-overlap" ) );
			return false;
		}
		return true;
	}
	public function buy(Player $player, Block $touched) {
		$rent = $this->rentProvider->getRent ( $touched->getLevel (), $touched->x, $touched->y, $touched->z );
		if ($rent instanceof RentSection) {
			if (! $rent->isCanBuy ()) {
				$this->message ( $player, $this->get ( "already-someone-to-buyrent" ) );
				return false;
			}
			if ($this->economy !== null and ! $player->isOp ()) {
				$money = $this->economy->myMoney ( $player );
				if ($money < $rent->getPrice ()) {
					$string = $this->get ( "not-enough-money-to-buyrent-1" ) . $rent->getPrice () - $money . $this->get ( "not-enough-money-to-buyrent-1" );
					$this->message ( $player, $string );
					return false;
				} else {
					$this->economy->reduceMoney ( $player, $rent->getPrice () );
				}
			}
			$rent->buy ( $player );
			$this->message ( $player, $this->get ( "buyrent-success" ) );
		} else {
			$this->message ( $player, $this->get ( "failed-buyrent-rent-is-overlap" ) );
			return false;
		}
		return true;
	}
	public function out(Player $player) {
		$rent = $this->rentProvider->getRent ( $player->getLevel (), $player->x, $player->y, $player->z );
		$rent = $this->rentProvider->getRent ( $player->getLevel (), $player->x, $player->y, $player->z );
		if ($rent instanceof RentSection) {
			if ($rent->getOwner () == strtolower ( $player->getName () )) {
				$rent->out ();
				$this->message ( $player, $this->get ( "rent-out-complete" ) );
			} else {
				$this->alert ( $player, $this->get ( "youre-not-this-rent-owner" ) );
				return false;
			}
		}
	}
	public function saleList(Player $player, $index) {
		$oncePrint = 20;
		$target = $this->properties->getRentSaleList ( $player->getLevel () );
		
		$indexCount = count ( $target );
		$indexKey = array_keys ( $target );
		$fullIndex = floor ( $indexCount / $oncePrint );
		
		if ($indexCount > $fullIndex * $oncePrint)
			$fullIndex ++;
		
		if ($index > $fullIndex) {
			$this->message ( $player, $this->get ( "there-is-no-list" ) );
			return false;
		}
		$this->message ( $player, $this->get ( "now-list-show" ) . " ({$index}/{$fullIndex}) " . $this->get ( "index_count" ) . ": {$indexCount}" );
		
		$message = null;
		for($forI = $oncePrint; $forI >= 1; $forI --) {
			$nowIndex = $index * $oncePrint - $forI;
			if (! isset ( $indexKey [$nowIndex] ))
				break;
			$nowKey = $indexKey [$nowIndex];
			$message .= TextFormat::DARK_AQUA . "[" . $nowKey . $this->get ( "arealist-name" ) . "] ";
		}
		$this->message ( $player, $message );
		return true;
	}
	public function setWelcome(Player $player, $welcome) {
		$rent = $this->rentProvider->getRent ( $player->getLevel (), $player->x, $player->z );
		if (! $rent instanceof RentSection) {
			$this->alert ( $player, $this->get ( "rent-doesent-exist" ) );
			return false;
		}
		if (! $player->isOp ()) {
			if ($rent->getOwner () !== strtolower ( $player->getName () )) {
				$this->alert ( $player, $this->get ( "youre-not-this-rent-owner" ) );
				return false;
			}
		}
		$rent->setWelcome ( $welcome );
		$this->message ( $player, $this->get ( "set-welcome-complete" ) );
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
	 * @return RentManager
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>