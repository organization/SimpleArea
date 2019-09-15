<?php
namespace ifteam\SimpleArea\database\world;

use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\SimpleArea;
use pocketmine\command\CommandSender;

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
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance();
	}

	/**
	 *
	 * @return AreaProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}

	public function protect($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isProtected()) {
				$whiteWorld->setProtect(false);
				$this->message($player, $this->get("whiteworld-unprotected"));
			} else {
				$whiteWorld->setProtect(true);
				$this->message($player, $this->get("whiteworld-protected"));
			}
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function message(CommandSender $player, $text = "", $mark = null) {
		$this->plugin->message($player, $text, $mark);
	}

	public function get($var) {
		return $this->plugin->get($var);
	}

	public function info($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if (!$whiteWorld instanceof WhiteWorldData) {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		($whiteWorld->isProtected()) ? $bool = $this->get("yes") : $bool = $this->get("no");
		$fence = $whiteWorld->getDefaultFenceType();
		$fence = "{$fence[0]}:{$fence[1]}";
		$this->message($player, $this->get("isprotected") . " : {$bool}, " . $this->get("default-wall") . $fence);

		$this->message($player, $this->get("default-area-price") . " : " . $whiteWorld->getDefaultAreaPrice());

		($whiteWorld->isPvpAllow()) ? $bool = $this->get("yes") : $bool = $this->get("no");
		$this->message($player, $this->get("maximum-holds-area") . " : " . $whiteWorld->getAreaHoldLimit() . ", " . $this->get("is-pvp-allow") . " : " . $bool);

		$areaInfo = AreaProvider::getInstance()->getAreasInfo($world);
		$rentInfo = RentProvider::getInstance()->getRentsInfo($world);

		$this->message($player, $this->get("user-area-count") . " : " . $areaInfo["userArea"] . ", " . $this->get("can-buy-user-area-count") . " : " . $areaInfo["buyableArea"]);
		$this->message($player, $this->get("rent-area-count") . " : " . $rentInfo["userRent"] . ", " . $this->get("can-buy-rent-area-count") . " : " . $rentInfo["buyableRent"]);
		$this->message($player, $this->get("op-land-count") . " : " . $areaInfo["adminArea"] . ", " . $this->get("areatax-count") . " : " . $whiteWorld->getAreaTax());

		($whiteWorld->isManualCreateAllow()) ? $bool = $this->get("yes") : $bool = $this->get("no");
		$this->message($player, $this->get("is-manual-area-allow") . " : " . $bool);

		($whiteWorld->isAutoCreateAllow()) ? $bool = $this->get("yes") : $bool = $this->get("no");
		$this->message($player, $this->get("is-auto-area-allow") . " : " . $bool);

		$size = $whiteWorld->getDefaultAreaSize();
		$this->message($player, $this->get("default-auto-area-size") . " : " . "{$size[0]}x{$size[1]}");

		$this->message($player, $this->get("manual-block-per-price") . " : " . $whiteWorld->getPricePerBlock() . "$");
		return true;
	}

	public function areaPrice($world, $price, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if (!is_numeric($price)) {
				$this->message($player, $this->get("whiteworld-price-must-be-numeric"));
				return false;
			}
			$whiteWorld->setDefaultAreaPrice($price);
			$this->message($player, $this->get("whiteworld-areaprice-changed"));
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function setFence($world, $block, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			$block = explode(":", $block);
			if (isset($block[1])) {
				if (!is_numeric($block[0]) or !is_numeric($block[1])) {
					$this->alert($player, $this->get("wrong-block-id-and-damage"));
					return false;
				}
			} else {
				if (!is_numeric($block[0])) {
					$this->alert($player, $this->get("wrong-block-id-and-damage"));
					return false;
				}
				$block[1] = 0;
			}
			$whiteWorld->setDefaultFence($block[0], $block[1]);
			$this->message($player, $block[0] . ":" . $block[1] . " " . $this->get("fence-id-changed"));
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function alert(CommandSender $player, $text = "", $mark = null) {
		$this->plugin->alert($player, $text, $mark);
	}

	public function setInvenSave($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isInvenSave()) {
				$whiteWorld->setInvenSave(false);
				$this->message($player, $this->get("whiteworld-invensave-disabled"));
			} else {
				$whiteWorld->setInvenSave(true);
				$this->message($player, $this->get("whiteworld-invensave-enabled"));
			}
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function setAutoCreateAllow($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isAutoCreateAllow()) {
				$whiteWorld->setAutoCreateAllow(false);
				$this->message($player, $this->get("whiteworld-autocreate-disabled"));
			} else {
				$whiteWorld->setAutoCreateAllow(true);
				$this->message($player, $this->get("whiteworld-autocreate-enabled"));
			}
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function setCountShareArea($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isCountShareArea()) {
				$whiteWorld->setCountShareArea(false);
				$this->message($player, $this->get("whiteworld-countshare-disabled"));
			} else {
				$whiteWorld->setCountShareArea(true);
				$this->message($player, $this->get("whiteworld-countshare-enabled"));
			}
		} else {
			$this->message($player, $this->get("whiteworld-countshare-disabled"));
			return false;
		}
		return true;
	}

	public function setManualCreate($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if ($whiteWorld->isManualCreateAllow()) {
				$whiteWorld->setManualCreate(false);
				$this->message($player, $this->get("whiteworld-manualcreate-disabled"));
			} else {
				$whiteWorld->setManualCreate(true);
				$this->message($player, $this->get("whiteworld-manualcreate-enabled"));
			}
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function pvpAllow($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if (!$whiteWorld instanceof WhiteWorldData) {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		if ($whiteWorld->isPvpAllow()) {
			$whiteWorld->setPvpAllow(false);
			$this->message($player, $this->get("whiteworld-pvp-forbid"));
		} else {
			$whiteWorld->setPvpAllow(true);
			$this->message($player, $this->get("whiteworld-pvp-allowed"));
		}
		return true;
	}

	public function setAccessDeny($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if (!$whiteWorld instanceof WhiteWorldData) {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		if ($whiteWorld->isAllowAccessDeny()) {
			$whiteWorld->setAllowAccessDeny(false);
			$this->message($player, $this->get("whiteworld-accessdeny-forbid"));
		} else {
			$whiteWorld->setAllowAccessDeny(true);
			$this->message($player, $this->get("whiteworld-accessdeny-allowed"));
		}
		return true;
	}

	public function setAreaSizeUp($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if (!$whiteWorld instanceof WhiteWorldData) {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		if ($whiteWorld->isAllowAreaSizeUp()) {
			$whiteWorld->setAllowAreaSizeUp(false);
			$this->message($player, $this->get("whiteworld-areasizeup-forbid"));
		} else {
			$whiteWorld->setAllowAreaSizeUp(true);
			$this->message($player, $this->get("whiteworld-areasizeup-allowed"));
		}
		return true;
	}

	public function setAreaSizeDown($world, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if (!$whiteWorld instanceof WhiteWorldData) {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		if ($whiteWorld->isAllowAreaSizeDown()) {
			$whiteWorld->setAllowAreaSizeDown(false);
			$this->message($player, $this->get("whiteworld-areasizedown-forbid"));
		} else {
			$whiteWorld->setAllowAreaSizeDown(true);
			$this->message($player, $this->get("whiteworld-areasizedown-allowed"));
		}
		return true;
	}

	public function setPricePerBlock($world, CommandSender $player, $price) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if (!$whiteWorld instanceof WhiteWorldData) {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		if (!is_numeric($price)) {
			$this->message($player, $this->get("whiteworld-priceperblock-must-be-numeric"));
			return false;
		}
		$whiteWorld->setPricePerBlock($price);
		$this->message($player, $this->get("whiteworld-priceperblock-changed"));
		return true;
	}

	public function areaHoldLimit($world, $limit, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if (!is_numeric($limit)) {
				$this->alert($player, $this->get("arealimit-must-be-numeric"));
				return false;
			}
			$whiteWorld->setAreaHoldLimit($limit);
			$this->alert($player, $this->get("whiteworld-arealimit-has-changed"));
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function defaultAreaSize($world, $x, $z, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			if (!is_numeric($x) or !is_numeric($z)) {
				$this->message($player, $this->get("whiteworld-areasize-must-be-numeric"));
				return false;
			}
			$whiteWorld->setDefaultAreaSize($x, $z);
			$this->message($player, $this->get("whiteworld-areasize-has-changed"));
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}

	public function setManualCreateMaxSize($world, $size, CommandSender $player) {
		$whiteWorld = $this->whiteWorldProvider->get($world);
		if ($whiteWorld instanceof WhiteWorldData) {
			$x = explode(":", $size)[0];
			$z = explode(":", $size)[1];
			if (!is_numeric($x)) {
				$this->message($player, "수동생성 최대 사이즈는 숫자이어야 합니다.");
				return false;
			}
			$whiteWorld->setManualCreateMaxSize($x, $z);
			$this->message($player, "성공적으로 수동생성 최대 사이즈를 변경하였습니다.");
		} else {
			$this->message($player, $this->get("whiteworld-not-exist"));
			return false;
		}
		return true;
	}
}

?>
