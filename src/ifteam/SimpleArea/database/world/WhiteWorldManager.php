<?php

namespace ifteam\SimpleArea\database\world;

use ifteam\SimpleArea\SimpleArea;
use pocketmine\command\CommandSender;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\rent\RentProvider;

class WhiteWorldManager {
	private static $instance = null;
	private $plugin;
	/**
	 *
	 * @var WhiteWorldProvider
	 */
	private $whiteWorldProvider;
	public function __construct(SimpleArea $plugin) {
		if (self::$instance == null)
			self::$instance = $this;
		$this->plugin = $plugin;
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance ();
	}
	public function protect($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isProtected ()) {
				$whiteWorld->setProtect ( false );
				$this->message ( $player, $this->get ( "whiteworld-unprotected" ) );
			} else {
				$whiteWorld->setProtect ( true );
				$this->message ( $player, $this->get ( "whiteworld-protected" ) );
			}
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function allowBlock($level, $block, $bool, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			$block = explode ( ":", $block );
			if (isset ( $block [1] )) {
				if (! is_numeric ( $block [0] ) or ! is_numeric ( $block [1] )) {
					$this->alert ( $player, $this->get ( "wrong-block-id-and-damage" ) );
					return false;
				}
			} else {
				if (! is_numeric ( $block [0] )) {
					$this->alert ( $player, $this->get ( "wrong-block-id-and-damage" ) );
					return false;
				}
				$block [1] = 0;
			}
			$whiteWorld->setAllowOption ( $bool, $block [0], $block [1] );
			$this->message ( $player, $this->get ( "whiteworld-allowblock-added" ) );
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function allowBlockList($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		$allowBlockList = $whiteWorld->getAllowOption ();
		$this->message ( $player, $this->get ( "show-allowed-block-list" ) );
		$listString = "";
		foreach ( $allowBlockList as $allowBlock => $bool )
			$listString = "<{$allowBlock}> ";
		$this->message ( $player, $listString );
		return true;
	}
	public function allowBlockClear($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		$allowBlockList = $whiteWorld->getAllowOption ();
		foreach ( $allowBlockList as $allowBlock => $bool ) {
			$block = explode ( ":", $allowBlock );
			$area->setAllowOption ( false, $block [0], $block [1] );
		}
		$this->message ( $player, $this->get ( "allowed-block-list-cleared" ) );
		return true;
	}
	public function forbidBlock($level, $block, $bool, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			$block = explode ( ":", $block );
			if (isset ( $block [1] )) {
				if (! is_numeric ( $block [0] ) or ! is_numeric ( $block [1] )) {
					$this->alert ( $player, $this->get ( "wrong-block-id-and-damage" ) );
					return false;
				}
			} else {
				if (! is_numeric ( $block [0] )) {
					$this->alert ( $player, $this->get ( "wrong-block-id-and-damage" ) );
					return false;
				}
				$block [1] = 0;
			}
			$whiteWorld->setForbidOption ( $bool, $block [0], $block [1] );
			$this->message ( $player, $this->get ( "whiteworld-forbidblock-added" ) );
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function forbidBlockList($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		$forbidBlockList = $whiteWorld->getForbidOption ();
		$this->message ( $player, $this->get ( "show-forbid-block-list" ) );
		$listString = "";
		foreach ( $forbidBlockList as $forbidBlock => $bool )
			$listString = "<{$forbidBlock}> ";
		$this->message ( $player, $listString );
		return true;
	}
	public function forbidBlockClear($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		$allowBlockList = $whiteWorld->getForbidOption ();
		foreach ( $allowBlockList as $allowBlock => $bool ) {
			$block = explode ( ":", $allowBlock );
			$whiteWorld->setForbidOption ( false, $block [0], $block [1] );
		}
		$this->message ( $player, $this->get ( "forbid-block-list-cleared" ) );
		return true;
	}
	public function info($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		($whiteWorld->isProtected ()) ? $bool = $this->get ( "yes" ) : $bool = $this->get ( "no" );
		$fence = $whiteWorld->getDefaultFenceType ();
		$fence = "{$fence[0]}:{$fence[1]}";
		$this->message ( $player, $this->get ( "isprotected" ) . " : {$bool}, " . $this->get ( "default-wall" ) . $fence );
		
		$this->message ( $player, $this->get ( "default-area-price" ) . " : " . $whiteWorld->getDefaultAreaPrice () );
		
		($whiteWorld->isPvpAllow ()) ? $bool = $this->get ( "yes" ) : $bool = $this->get ( "no" );
		$this->message ( $player, $this->get ( "maximum-holds-area" ) . " : " . $whiteWorld->getAreaHoldLimit () . ", " . $this->get ( "is-pvp-allow" ) . " : " . $bool );
		
		$areaInfo = AreaProvider::getInstance ()->getAreasInfo ( $level );
		$rentInfo = RentProvider::getInstance ()->getRentsInfo ( $level );
		
		$this->message ( $player, $this->get ( "user-area-count" ) . " : " . $areaInfo ["userArea"] . ", " . $this->get ( "can-buy-user-area-count" ) . " : " . $areaInfo ["buyableArea"] );
		$this->message ( $player, $this->get ( "rent-area-count" ) . " : " . $rentInfo ["userRent"] . ", " . $this->get ( "can-buy-rent-area-count" ) . " : " . $rentInfo ["buyableRent"] );
		$this->message ( $player, $this->get ( "op-land-count" ) . " : " . $areaInfo ["adminArea"] . ", " . $this->get ( "areatax-count" ) . " : " . $whiteWorld->getAreaTax () );
		
		($whiteWorld->isManualCreateAllow ()) ? $bool = $this->get ( "yes" ) : $bool = $this->get ( "no" );
		$this->message ( $player, $this->get ( "is-manual-area-allow" ) . " : " . $bool );
		
		($whiteWorld->isAutoCreateAllow ()) ? $bool = $this->get ( "yes" ) : $bool = $this->get ( "no" );
		$this->message ( $player, $this->get ( "is-auto-area-allow" ) . " : " . $bool );
		
		$size = $whiteWorld->getDefaultAreaSize ();
		$this->message ( $player, $this->get ( "default-auto-area-size" ) . " : " . "{$size[0]}x{$size[1]}" );
		
		$this->message ( $player, $this->get ( "manual-block-per-price" ) . " : " . $whiteWorld->getPricePerBlock () . "$" );
		
		$allowBlockString = "";
		foreach ( $whiteWorld->getAllowOption () as $allowOption => $bool )
			$allowBlockString .= "<{$allowOption}> ";
		if ($allowBlockString == "")
			$allowBlockString = $this->get ( "none" );
		$this->message ( $player, $this->get ( "default-allowed-block" ) . " : " . $allowBlockString );
		
		$forbidBlockString = "";
		foreach ( $whiteWorld->getForbidOption () as $forbidOption => $bool )
			$forbidBlockString .= "<{$forbidOption}> ";
		if ($forbidBlockString == "")
			$forbidBlockString = $this->get ( "none" );
		$this->message ( $player, $this->get ( "default-forbid-block" ) . " : " . $forbidBlockString );
		return true;
	}
	public function areaPrice($level, $price, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if (! is_numeric ( $price )) {
				$this->message ( $player, $this->get ( "whiteworld-price-must-be-numeric" ) );
				return false;
			}
			$whiteWorld->setDefaultAreaPrice ( $price );
			$this->message ( $player, $this->get ( "whiteworld-areaprice-changed" ) );
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function setFence($level, $block, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			$block = explode ( ":", $block );
			if (isset ( $block [1] )) {
				if (! is_numeric ( $block [0] ) or ! is_numeric ( $block [1] )) {
					$this->alert ( $player, $this->get ( "wrong-block-id-and-damage" ) );
					return false;
				}
			} else {
				if (! is_numeric ( $block [0] )) {
					$this->alert ( $player, $this->get ( "wrong-block-id-and-damage" ) );
					return false;
				}
				$block [1] = 0;
			}
			$whiteWorld->setDefaultFence ( $block [0], $block [1] );
			$this->message ( $player, $block [0] . ":" . $block [1] . " " . $this->get ( "fence-id-changed" ) );
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function setInvenSave($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isInvenSave ()) {
				$whiteWorld->setInvenSave ( false );
				$this->message ( $player, $this->get ( "whiteworld-invensave-disabled" ) );
			} else {
				$whiteWorld->setInvenSave ( true );
				$this->message ( $player, $this->get ( "whiteworld-invensave-enabled" ) );
			}
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function setAutoCreateAllow($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isAutoCreateAllow ()) {
				$whiteWorld->setAutoCreateAllow ( false );
				$this->message ( $player, $this->get ( "whiteworld-autocreate-disabled" ) );
			} else {
				$whiteWorld->setAutoCreateAllow ( true );
				$this->message ( $player, $this->get ( "whiteworld-autocreate-enabled" ) );
			}
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function setCountShareArea($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isCountShareArea ()) {
				$whiteWorld->setCountShareArea ( false );
				$this->message ( $player, $this->get ( "whiteworld-countshare-disabled" ) );
			} else {
				$whiteWorld->setCountShareArea ( true );
				$this->message ( $player, $this->get ( "whiteworld-countshare-enabled" ) );
			}
		} else {
			$this->message ( $player, $this->get ( "whiteworld-countshare-disabled" ) );
			return false;
		}
		return true;
	}
	public function setManualCreate($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isManualCreateAllow ()) {
				$whiteWorld->setManualCreate ( false );
				$this->message ( $player, $this->get ( "whiteworld-manualcreate-disabled" ) );
			} else {
				$whiteWorld->setManualCreate ( true );
				$this->message ( $player, $this->get ( "whiteworld-manualcreate-enabled" ) );
			}
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function pvpAllow($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		if ($whiteWorld->isPvpAllow ()) {
			$whiteWorld->setPvpAllow ( false );
			$this->message ( $player, $this->get ( "whiteworld-pvp-forbid" ) );
		} else {
			$whiteWorld->setPvpAllow ( true );
			$this->message ( $player, $this->get ( "whiteworld-pvp-allowed" ) );
		}
		return true;
	}
	public function setAccessDeny($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		if ($whiteWorld->isAllowAccessDeny ()) {
			$whiteWorld->setAllowAccessDeny ( false );
			$this->message ( $player, $this->get ( "whiteworld-accessdeny-forbid" ) );
		} else {
			$whiteWorld->setAllowAccessDeny ( true );
			$this->message ( $player, $this->get ( "whiteworld-accessdeny-allowed" ) );
		}
		return true;
	}
	public function setAreaSizeUp($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		if ($whiteWorld->isAllowAreaSizeUp ()) {
			$whiteWorld->setAllowAreaSizeUp ( false );
			$this->message ( $player, $this->get ( "whiteworld-areasizeup-forbid" ) );
		} else {
			$whiteWorld->setAllowAreaSizeUp ( true );
			$this->message ( $player, $this->get ( "whiteworld-areasizeup-allowed" ) );
		}
		return true;
	}
	public function setAreaSizeDown($level, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		if ($whiteWorld->isAllowAreaSizeDown ()) {
			$whiteWorld->setAllowAreaSizeDown ( false );
			$this->message ( $player, $this->get ( "whiteworld-areasizedown-forbid" ) );
		} else {
			$whiteWorld->setAllowAreaSizeDown ( true );
			$this->message ( $player, $this->get ( "whiteworld-areasizedown-allowed" ) );
		}
		return true;
	}
	public function setPricePerBlock($level, CommandSender $player, $price) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if (! $whiteWorld instanceof WhiteWorldData) {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		if (! is_numeric ( $price )) {
			$this->message ( $player, $this->get ( "whiteworld-priceperblock-must-be-numeric" ) );
			return false;
		}
		$whiteWorld->setPricePerBlock ( $price );
		$this->message ( $player, $this->get ( "whiteworld-priceperblock-changed" ) );
		return true;
	}
	public function areaHoldLimit($level, $limit, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if (! is_numeric ( $limit )) {
				$this->alert ( $player, $this->get ( "arealimit-must-be-numeric" ) );
				return false;
			}
			$whiteWorld->setAreaHoldLimit ( $limit );
			$this->alert ( $player, $this->get ( "whiteworld-arealimit-has-changed" ) );
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
		return true;
	}
	public function defaultAreaSize($level, $x, $z, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get ( $level );
		if ($whiteWorld instanceof WhiteWorldData) {
			if (! is_numeric ( $x ) or ! is_numeric ( $z )) {
				$this->message ( $player, $this->get ( "whiteworld-areasize-must-be-numeric" ) );
				return false;
			}
			$whiteWorld->setDefaultAreaSize ( $x, $z );
			$this->message ( $player, $this->get ( "whiteworld-areasize-has-changed" ) );
		} else {
			$this->message ( $player, $this->get ( "whiteworld-not-exist" ) );
			return false;
		}
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
	 * @return AreaProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>
