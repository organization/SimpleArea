<?php

namespace ifteam\SimpleArea\database\area;

use ifteam\SimpleArea\database\user\UserProperties;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\SimpleArea;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class AreaManager {
    private static $instance = null;
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
        $this->areaProvider = AreaProvider::getInstance();
        $this->whiteWorldProvider = WhiteWorldProvider::getInstance();
        $this->properties = UserProperties::getInstance();
        $this->economy = $this->plugin->otherApi->economyAPI->getPlugin();
    }

    /**
     *
     * @return AreaManager
     */
    public static function getInstance() {
        return static::$instance;
    }

    public function move(Player $player, $target) {
        if (is_numeric($target)) {
            $area = $this->areaProvider->getAreaToId($player->getWorld(), $target);
            if (!$area instanceof AreaSection) {
                $this->alert($player, $this->get("area-number-doesent-exist"));
                return false;
            }
            $areaData = $area->getAll();
            $x = $areaData ["startX"] + 1;
            $z = $areaData ["startZ"] + 1;
            if ($player->getWorld()->getFolderName() == "minefarm") {
                $center = $area->getCenter();
                $x = $center->x;
                $z = $center->z;
            }
            $y = ($player->getWorld()->getHighestBlockAt($x, $z) + 2);
            $player->teleport(new Vector3 ($x, $y, $z));
        } else {
            $properties = $this->properties->getUserProperties($target, $player->getWorld());
            $this->message($player, $this->get("show-the-areas-with-the-user"));
            $listString = "";
            foreach ($properties as $propertie => $bool)
                $listString .= "<{$propertie}> ";
            $this->message($player, $listString, "");
        }
        return true;
    }

    public function alert(CommandSender $player, $text = "", $mark = null) {
        $this->plugin->alert($player, $text, $mark);
    }

    public function get($var) {
        return $this->plugin->get($var);
    }

    public function message(CommandSender $player, $text = "", $mark = null) {
        $this->plugin->message($player, $text, $mark);
    }

    public function getList(Player $player) {
        $properties = $this->properties->getUserProperties($player->getName(), $player->getWorld());
        $this->message($player, $this->get("show-the-areas"));
        $listString = "";
        foreach ($properties as $propertie => $bool)
            $listString .= "<{$propertie}> ";
        $this->message($player, $listString, "");
    }

    public function autoCreate(Player $player) {
        $whiteWorld = $this->whiteWorldProvider->get($player->getWorld());
        if (!$player->isOp()) {
            if (!$whiteWorld->isAutoCreateAllow()) {
                $this->alert($player, $this->get("whiteworld-manualcreate-not-allowed"));
                return false;
            }
        }
        $areaHoldLimit = $this->whiteWorldProvider->get($player->getWorld())->getAreaHoldLimit();
        $userHoldCount = count($this->properties->getUserProperties($player->getName(), $player->getWorld()));
        if (!$player->isOp()) {
            if ($userHoldCount >= $areaHoldLimit) {
                $this->alert($player, $this->get("no-more-buying-area"));
                return false;
            }
        }

        $defaultSize = $this->whiteWorldProvider->get($player->getWorld())->getDefaultAreaSize();
        $defaultXSize = ( int ) round($defaultSize [0] / 2);
        $defaultZSize = ( int ) round($defaultSize [1] / 2);

        $startX = ( int ) round($player->getLocation()->getX() - $defaultXSize);
        $endX = ( int ) round($player->getLocation()->getX() + $defaultXSize);
        $startZ = ( int ) round($player->getLocation()->getZ() - $defaultZSize);
        $endZ = ( int ) round($player->getLocation()->getZ() + $defaultZSize);

        $areaPrice = $this->whiteWorldProvider->get($player->getWorld())->getDefaultAreaPrice();

        $getOverlap = $this->areaProvider->checkOverlap($player->getWorld(), $startX, $endX, $startZ, $endZ);
        if ($getOverlap instanceof AreaSection) {
            $this->alert($player, $getOverlap->getId() . $this->get("automatic-post-fail-overlap-problem"));
            return false;
        }
        if ($this->economy !== null and !$player->isOp()) {
            $money = $this->economy->myMoney($player);
            if ($money < $areaPrice) {
                $string = $this->get("not-enough-money-to-buyarea-1") . ($areaPrice - $money) . $this->get("not-enough-money-to-buyarea-2");
                $this->message($player, $string);
                return false;
            }
            $this->economy->reduceMoney($player, $areaPrice);
        }
        $this->areaProvider->addArea($player->getWorld(), $startX, $endX, $startZ, $endZ, $player->getName(), true);
        $this->message($player, $this->get("areaset-success"));
        $this->message($player, $this->get("you-can-use-area-rent-command"));
        return true;
    }

    public function manualCreate(Player $player, $startX, $endX, $startZ, $endZ, $isHome = true) {
        $whiteWorld = $this->whiteWorldProvider->get($player->getWorld());
        if (!$player->isOp()) {
            if (!$whiteWorld->isManualCreateAllow()) {
                $this->alert($player, $this->get("whiteworld-manualcreate-not-allowed"));
                return false;
            }
        }
        $areaHoldLimit = $this->whiteWorldProvider->get($player->getWorld())->getAreaHoldLimit();
        $userHoldCount = count($this->properties->getUserProperties($player->getName(), $player->getWorld()));
        if (!$player->isOp()) {
            if ($userHoldCount >= $areaHoldLimit) {
                $this->alert($player, $this->get("no-more-buying-area"));
                return false;
            }
        }

        $pricePerBlock = $whiteWorld->getPricePerBlock();

        $xSize = $endX - $startX;
        $zSize = $endZ - $startZ;
        $areaPrice = ($xSize * $zSize) * $pricePerBlock;

        $getOverlap = $this->areaProvider->checkOverlap($player->getWorld(), $startX, $endX, $startZ, $endZ);
        if ($getOverlap instanceof AreaSection) {
            $this->alert($player, $getOverlap->getId() . $this->get("automatic-post-fail-overlap-problem"));
            return false;
        }
        if ($this->economy !== null and !$player->isOp()) {
            $money = $this->economy->myMoney($player);
            if ($money < $areaPrice) {
                $string = $this->get("not-enough-money-to-buyarea-1") . ($areaPrice - $money) . $this->get("not-enough-money-to-buyarea-2");
                $this->message($player, $string);
                return false;
            }
            $this->economy->reduceMoney($player, $areaPrice);
        }
        $this->areaProvider->addArea($player->getWorld(), $startX, $endX, $startZ, $endZ, $player->getName(), $isHome);
        $this->message($player, $this->get("areaset-success"));
        $this->message($player, $this->get("you-can-use-area-rent-command"));

        return true;
    }

    public function buy(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->getX(), $player->getLocation()->getZ());
        if ($area instanceof AreaSection) {
            if (!$area->isCanBuy()) {
                $this->alert($player, $this->get("already-someone-to-buyarea"));
                return false;
            }
            $areaHoldLimit = $this->whiteWorldProvider->get($player->getWorld())->getAreaHoldLimit();
            $userHoldCount = count($this->properties->getUserProperties($player->getName(), $player->getWorld()));
            if (!$player->isOp()) {
                if ($userHoldCount >= $areaHoldLimit) {
                    $this->alert($player, $this->get("no-more-buying-area"));
                    return false;
                }
            }
            if ($this->economy !== null and !$player->isOp()) {
                $money = $this->economy->myMoney($player);
                if ($money < $area->getPrice()) {
                    $string = $this->get("not-enough-money-to-buyarea-1") . $area->getPrice() . $this->get("not-enough-money-to-buyarea-2");
                    $this->alert($player, $string);
                    return false;
                } else {
                    $this->economy->reduceMoney($player, $area->getPrice());
                }
            }
            $area->buy($player);
            $this->message($player, $this->get("buyarea-success"));
            $this->message($player, $this->get("you-can-use-area-rent-command"));
        } else {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        return true;
    }

    public function sell(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->getX(), $player->getLocation()->getZ());
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        if (!$area->isHome() and $player->isOp()) {
            $area->sell();
            $this->alert($player, $this->get("op-land-is-cant-buy-and-sell"));
            return false;
        }
        $area->sell();
        $this->message($player, $this->get("sellarea-complete"));

        if ($this->economy !== null) {
            $this->economy->addMoney($player, $area->getPrice() / 2);
            $this->message($player, $this->get("half-price-gived") . $area->getPrice() / 2);
        }
        return true;
    }

    public function give(Player $player, $target) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        $target = $this->plugin->getServer()->getPlayer($target);
        if ($target instanceof Player) {
            $areaHoldLimit = $this->whiteWorldProvider->get($target->getWorld())->getAreaHoldLimit();
            $userHoldCount = count($this->properties->getUserProperties($target->getName(), $target->getWorld()));
            if ($userHoldCount >= $areaHoldLimit) {
                $this->alert($player, $this->get("no-more-giving-area"));
                return false;
            }
            $area->setOwner($target->getName());
            $this->message($player, $target->getName() . $this->get("givearea-success"));
            $this->message($target, $player->getName() . $this->get("gaved-some-area") . "[ ID:" . $area->getId() . " ]");
            $this->message($target, $this->get("you-can-use-area-rent-command"));
        } else {
            $this->alert($player, $this->get("target-is-offline"));
        }
        return true;
    }

    public function info(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        ($area->isHome()) ? $type = $this->get("private-area") : $type = $this->get("op-area");
        $this->message($player, $this->get("areanum") . " : " . $area->getId() . ", " . $this->get("areatype") . " : " . $type);
        $xSize = $area->get("endX") - $area->get("startX");
        $zSize = $area->get("endZ") - $area->get("startZ");
        ($area->isProtected()) ? $bool = $this->get("yes") : $bool = $this->get("no");
        $this->message($player, $this->get("areasize") . " : " . "{$xSize}x{$zSize} ," . $this->get("isprotected") . " : " . $bool);
        if ($area->getWelcome() != null)
            $this->message($player, $this->get("welcome-prefix") . " " . $area->getWelcome());
        $this->message($player, $this->get("owner") . " : " . "<" . $area->getOwner() . ">");
        $sharedString = "";
        foreach ($area->getResident() as $resident => $bool)
            $sharedString .= "<{$resident}> ";
        $this->message($player, $this->get("shared") . " : " . $sharedString);
        return true;
    }

    public function share(Player $player, $target) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        $target = $this->plugin->getServer()->getPlayer($target);
        if ($target instanceof Player) {
            $residents = $area->getResident();
            if (isset ($residents [$target->getName()])) {
                $this->message($player, $this->get("already-shared"));
            } else {
                $area->changeResident([
                        $target->getName()
                ]);
                $this->message($player, $this->get("share-complete"));
            }
        } else {
            $this->alert($player, $this->get("target-is-offline"));
            return false;
        }
        return true;
    }

    public function deport(Player $player, $target) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        $residents = $area->getResident();
        if (isset ($residents [strtolower($target)])) {
            $area->changeResident([], [
                    strtolower($target)
            ]);
            $this->message($player, $this->get("deport-complete"));
        } else {
            $this->alert($player, $this->get("deport-fail"));
            return false;
        }
        return true;
    }

    public function shareList(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if ($area instanceof AreaSection) {
            $residents = $area->getResident();
            $this->message($player, $this->get("show-share-list"));
            $listString = "";
            foreach ($residents as $resident => $bool)
                $listString .= "<" . $resident . "> ";
            $this->message($player, $listString, "");
        } else {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        return true;
    }

    public function saleList(Player $player, $index = 1) {
        $oncePrint = 20;
        $target = $this->properties->getSaleList($player->getWorld());

        if ($target == null) {
            $this->message($player, $this->get("there-is-no-list"));
            return false;
        }

        $indexCount = count($target);
        $indexKey = array_keys($target);
        $fullIndex = floor($indexCount / $oncePrint);

        if ($indexCount > $fullIndex * $oncePrint)
            $fullIndex++;

        if ($index > $fullIndex) {
            $this->message($player, $this->get("there-is-no-list"));
            return false;
        }
        $this->message($player, $this->get("now-list-show") . " ({$index}/{$fullIndex}) " . $this->get("index_count") . ": {$indexCount}");

        $message = null;
        for ($forI = $oncePrint; $forI >= 1; $forI--) {
            $nowIndex = $index * $oncePrint - $forI;
            if (!isset ($indexKey [$nowIndex]))
                break;
            $nowKey = $indexKey [$nowIndex];
            $message .= TextFormat::DARK_AQUA . "[" . $nowKey . $this->get("arealist-name") . "] ";
        }
        $this->message($player, $message);
        return true;
    }

    public function pvpallow(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if (strtolower($area->getOwner()) !== strtolower($player->getName())) {
                $this->alert($player, $this->get("here-is-not-your-area"));
                return false;
            }
        }
        if ($area->isPvpAllow()) {
            $area->setPvpAllow(false);
            $this->message($player, $this->get("area-pvp-forbid"));
        } else {
            $area->setPvpAllow(true);
            $this->message($player, $this->get("area-pvp-allowed"));
        }
        return true;
    }

    public function accessDeny(Player $player) {
        $whiteWorld = $this->whiteWorldProvider->get($player->getWorld());
        if (!$player->isOp()) {
            if (!$whiteWorld->isAllowAccessDeny()) {
                $this->alert($player, $this->get("whiteworld-accessdeny-not-allowed"));
                return false;
            }
        }
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        if ($area->isAccessDeny()) {
            $area->setAccessDeny(false);
            $this->message($player, $this->get("area-access-allowed"));
        } else {
            $area->setAccessDeny(true);
            $this->message($player, $this->get("area-access-forbid"));
        }
        return true;
    }

    public function areaSizeUp(Player $player, $world, $id, $startX, $endX, $startZ, $endZ, $price) {
        $whiteWorld = $this->whiteWorldProvider->get($player->getWorld());
        if (!$player->isOp()) {
            if (!$whiteWorld->isAllowAreaSizeUp()) {
                $this->alert($player, $this->get("whiteworld-areasizeup-not-allowed"));
                return false;
            }
        }
        $area = $this->areaProvider->getAreaToId($world, $id);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        if ($this->economy !== null and !$player->isOp()) {
            $money = $this->economy->myMoney($player);
            if ($money < $price) {
                $this->alert($player, $this->get("not-enough-money-to-size-up") . ($price - $money));
                return false;
            } else {
                $this->economy->reduceMoney($player, $price);
            }
        }
        ($area->isHome()) ? $resetFence = true : $resetFence = false;
        $bool = $this->areaProvider->resizeArea($world, $id, $startX, $endX, $startZ, $endZ, $resetFence);
        if ($bool) {
            $this->message($player, $this->get("area-size-up-complete"));
            return true;
        } else {
            $this->alert($player, $this->get("area-size-up-failed"));
            return false;
        }
    }

    public function areaSizeDown(Player $player, $world, $id, $startX, $endX, $startZ, $endZ) {
        $whiteWorld = $this->whiteWorldProvider->get($player->getWorld());
        if (!$player->isOp()) {
            if (!$whiteWorld->isAllowAreaSizeUp()) {
                $this->alert($player, $this->get("whiteworld-areasizedown-not-allowed"));
                return false;
            }
        }
        $area = $this->areaProvider->getAreaToId($world, $id);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        ($area->isHome()) ? $resetFence = true : $resetFence = false;
        $bool = $this->areaProvider->resizeArea($world, $id, $startX, $endX, $startZ, $endZ, $resetFence);
        if ($bool) {
            $this->message($player, $this->get("area-size-down-complete"));
            return true;
        } else {
            $this->alert($player, $this->get("area-size-down-failed"));
            return false;
        }
    }

    public function welcome(Player $player, $welcome) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        $area->setWelcome($welcome);
        $this->message($player, $this->get("set-welcome-complete"));
        return true;
    }

    public function protect(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        if ($area->isProtected()) {
            $area->setProtect(false);
            $this->message($player, $this->get("unprotect-complete"));
        } else {
            $area->setProtect(true);
            $this->message($player, $this->get("protect-complete"));
        }
        return true;
    }

    public function areaPrice(Player $player, $price) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!is_numeric($price)) {
            $this->message($player, $this->get("areaprice-must-numeric"));
            return false;
        }
        $area->setPrice($price);
        $this->message($player, $price . $this->get("areaprice-changed"));
        return true;
    }

    public function setFence(Player $player, $block = null) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() !== strtolower($player->getName())) {
                $this->alert($player, $this->get("youre-not-owner"));
                return false;
            }
        }
        if ($block == null) {
            $area->setFence();
        } else {
            $block = explode(":", $block);
            if (isset ($block [1])) {
                if (!is_numeric($block [0]) or !is_numeric($block [1])) {
                    $this->alert($player, $this->get("wrong-block-id-and-damage"));
                    return false;
                }
            } else {
                if (!is_numeric($block [0])) {

                    $this->alert($player, $this->get("wrong-block-id-and-damage"));
                    return false;
                }
                $block [1] = 0;
            }
            $area->setFence(3, $block [0], $block [1]);
        }
        $this->message($player, $block [0] . ":" . $block [1] . $this->get("fence-id-changed"));
        return true;
    }

    public function initialPrivateArea(Player $player, Block $pos, $xSize, $zSize, $defaultY, $dmg) {
        $xSize--;
        $zSize--;
        switch ($dmg) {
            case 0 : // 63:0 x+ z- XxZ
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x + $xSize);
                $endZ = ($pos->getPos()->z - $zSize);
                break;
            case 4 : // 63:4 x+ z+ ZxX
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x + $zSize);
                $endZ = ($pos->getPos()->z + $xSize);
                break;
            case 8 : // 63:8 x- z+ XxZ
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x - $xSize);
                $endZ = ($pos->getPos()->z + $zSize);
                break;
            case 12 : // 63:12 x- z- ZxX
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x - $zSize);
                $endZ = ($pos->getPos()->z - $xSize);
                break;
            default :
                $this->alert($player, $this->get("automatic-post-fail-sign-problem"));
                return false;
        }
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

        $getOverlap = $this->areaProvider->checkOverlap($pos->getPos()->getWorld(), $startX, $endX, $startZ, $endZ);
        if ($getOverlap instanceof AreaSection) {
            $this->alert($player, $getOverlap->getId() . $this->get("automatic-post-fail-overlap-problem"));
            return false;
        }

        $defaultFenceData = WhiteWorldProvider::getInstance()->get($pos->getPos()->getWorld())->getDefaultFenceType();
        $fenceId = $defaultFenceData [0];
        $fenceDamange = $defaultFenceData [1];

        $fenceBlock = BlockFactory::get($fenceId, $fenceDamange);
        $grassBlock = BlockFactory::get(BlockLegacyIds::GRASS);

        $world = $pos->getPos()->getWorld();
        $world->getPos()->setBlock($pos->getSide(Facing::UP), $fenceBlock);

        $blockPos = new Vector3 ($pos->getPos()->x, $defaultY, $pos->getPos()->z);

        for ($x = $startX; $x <= $endX; $x++)
            for ($z = $startZ; $z <= $endZ; $z++)
                $world->setBlock($blockPos->setComponents($x, $defaultY - 1, $z), $grassBlock);

        $this->setSideFence($world, $defaultY, $startX, $startX, $startZ, $endZ, 2, $fenceBlock); // UP
        $this->setSideFence($world, $defaultY, $endX, $endX, $startZ, $endZ, 2, $fenceBlock); // DOWN
        $this->setSideFence($world, $defaultY, $startX, $endX, $startZ, $startZ, 2, $fenceBlock); // WEST
        $this->setSideFence($world, $defaultY, $startX, $endX, $endZ, $endZ, 2, $fenceBlock); // EAST
        $this->message($player, $this->get("easy-automatic-post-complete"));
        return true;
    }

    public function setSideFence(World $world, $defaultY, $startX, $endX, $startZ, $endZ, $length, Block $fenceBlock) {
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

        $blockPos = new Vector3 (0, $defaultY, 0);
        $fenceQueue = 0;
        $emptyQueue = 0;

        for ($x = $startX; $x <= $endX; $x++)
            for ($z = $startZ; $z <= $endZ; $z++)
                if ($fenceQueue <= $length) {
                    $fenceQueue++;
                    $world->setBlock($blockPos->setComponents($x, $defaultY, $z), $fenceBlock);
                } else {
                    if (((($startX - $length - 1) <= $x and $x >= ($startX + $length - 1)) and (($startZ - $length - 1) <= $z and $z >= ($startZ + $length - 1))) or ((($endX - $length - 1) <= $x and $x >= ($endX + $length - 1)) and (($endZ - $length - 1) <= $z and $z >= ($endZ + $length - 1)))) {
                        $world->setBlock($blockPos->setComponents($x, $defaultY, $z), $fenceBlock);
                    }
                    if ($emptyQueue < ($length - 1)) {
                        $emptyQueue++;
                        if ($emptyQueue >= ($length - 1)) {
                            $fenceQueue = 0;
                            $emptyQueue = 0;
                        }
                        continue;
                    }
                }
    }

    public function destructPrivateArea(Player $player, Block $pos, $xSize, $zSize, $defaultY, $dmg) {
        $xSize--;
        $zSize--;
        switch ($dmg) {
            case 0 : // 63:0 x+ z- XxZ
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x + $xSize);
                $endZ = ($pos->getPos()->z - $zSize);
                break;
            case 4 : // 63:4 x+ z+ ZxX
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x + $zSize);
                $endZ = ($pos->getPos()->z + $xSize);
                break;
            case 8 : // 63:8 x- z+ XxZ
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x - $xSize);
                $endZ = ($pos->getPos()->z + $zSize);
                break;
            case 12 : // 63:12 x- z- ZxX
                $startX = $pos->getPos()->x;
                $startZ = $pos->getPos()->z;
                $endX = ($pos->getPos()->x - $zSize);
                $endZ = ($pos->getPos()->z - $xSize);
                break;
            default :
                return false;
        }
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

        $getArea = $this->areaProvider->checkOverlap($pos->getPos()->getWorld(), $startX + 1, ($endX - 1), $startZ + 1, ($endZ - 1));
        if ($getArea instanceof AreaSection)
            $getArea->deleteArea();

        $defaultFenceData = WhiteWorldProvider::getInstance()->get($pos->getPos()->getWorld())->getDefaultFenceType();
        $fenceId = $defaultFenceData [0];
        $fenceDamange = $defaultFenceData [1];

        $airBlock = BlockFactory::get(BlockLegacyIds::AIR);

        $world = $pos->getPos()->getWorld();
        $world->setBlock($pos->getPos()->getSide(Facing::DOWN), $airBlock);

        $blockPos = new Vector3 ($pos->getPos()->x, $defaultY, $pos->getPos()->z);

        if ($world->getBlock(new Vector3 ($startX, ($defaultY - 2), $startZ))->getId() == BlockLegacyIds::AIR)
            for ($x = $startX; $x <= $endX; $x++)
                for ($z = $startZ; $z <= $endZ; $z++)
                    $world->setBlock($blockPos->setComponents($x, $defaultY - 1, $z), $airBlock);

        $this->setSideFence($world, $defaultY, $startX, $startX, $startZ, $endZ, 2, $airBlock); // UP
        $this->setSideFence($world, $defaultY, $endX, $endX, $startZ, $endZ, 2, $airBlock); // DOWN
        $this->setSideFence($world, $defaultY, $startX, $endX, $startZ, $startZ, 2, $airBlock); // WEST
        $this->setSideFence($world, $defaultY, $startX, $endX, $endZ, $endZ, 2, $airBlock); // EAST
        $this->message($player, $this->get("easy-automatic-post-complete"));
        return true;
    }

    public function setInvenSave(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if ($area->isInvenSave()) {
            $area->setInvenSave(false);
            $this->message($player, $this->get("invensave-disabled"));
        } else {
            $area->setInvenSave(true);
            $this->message($player, $this->get("invensave-enabled"));
        }
        return true;
    }

    public function changeMode(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if ($area->isHome()) {
            $area->setHome(false);
            $area->removeFence();
            $this->message($player, $this->get("changemode-to-protect-area"));
        } else {
            $area->setHome(true);
            $area->setFence();
            $this->message($player, $this->get("changemode-to-lifegame-area"));
        }
        return true;
    }

    public function abandon(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if ($area->getOwner() == strtolower($player->getName())) {
            $this->alert($player, $this->get("owner-cant-abandon"));
            $this->alert($player, $this->get("commands-area-sell-help"));
            return false;
        }
        if (!$area->isResident($player->getName())) {
            $this->alert($player, $this->get("youre-not-resident"));
            return false;
        }
        $area->changeResident([], [
                $player->getName()
        ]);
        $this->message($player, $this->get("abandon-success"));
        return true;
    }

    public function delete(Player $player) {
        $area = $this->areaProvider->getArea($player->getWorld(), $player->getLocation()->x, $player->getLocation()->z);
        if (!$area instanceof AreaSection) {
            $this->alert($player, $this->get("area-doesent-exist"));
            $this->alert($player, $this->get("commands-area-info-help"));
            return false;
        }
        if (!$player->isOp()) {
            if ($area->getOwner() != strtolower($player->getName())) {
                $this->message($player, $this->get("youre-not-owner-delete-failed"));
                return false;
            }
        }
        $area->deleteArea();
        $this->message($player, $this->get("area-delete-complete"));
        return true;
    }
}