<?php
namespace ifteam\SimpleArea\database\area;

use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\event\AreaBuyEvent;
use ifteam\SimpleArea\event\AreaResidentEvent;
use ifteam\SimpleArea\event\AreaSellEvent;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class AreaSection {

    private $data = [];

    private $world;

    public function __construct(array &$data, $world) {
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
        foreach ($basicElements as $element)
            if (!isset($data[$element])) {
            }

        $whiteWorld = WhiteWorldProvider::getInstance()->get($world);

        if (!isset($data["protect"]))
            $data["protect"] = true;
        if (!isset($data["allowOption"]))
            $data["allowOption"] = [];
        if (!isset($data["forbidOption"]))
            $data["forbidOption"] = [];
        if (!isset($data["areaPrice"]))
            $data["areaPrice"] = $whiteWorld->getDefaultAreaPrice();
        if (!isset($data["welcome"]))
            $data["welcome"] = "";
        if (!isset($data["pvpAllow"]))
            $data["pvpAllow"] = $whiteWorld->isPvpAllow();
        if (!isset($data["invenSave"]))
            $data["invenSave"] = $whiteWorld->isInvenSave();
        if (!isset($data["accessDeny"]))
            $data["accessDeny"] = false;

        if ($data["owner"] !== "")
            $data["owner"] = strtolower($data["owner"]);

        $lowerResident = [];
        foreach ($data["resident"] as $resident => $bool)
            $lowerResident[strtolower($resident)] = $bool;
        $data["resident"] = $lowerResident;

        $this->world = $world;
        $this->data = &$data;
    }

    /**
     * Sell area
     *
     * @param string $player
     */
    public function sell($player = null) {
        if ($player instanceof Player)
            $player = $player->getName();
        $player = strtolower($player);
        $event = new AreaSellEvent($this->getOwner(), $this->getWorld(), $this->getId(), $player);
        $event->call();
        // Server::getInstance ()->getPluginManager ()->callEvent ( $event );
        if (!$event->isCancelled())
            $this->setOwner($player);
    }

    public function getOwner() {
        return $this->data["owner"];
    }

    public function getWorld() {
        return $this->world;
    }

    public function getId() {
        return $this->data["id"];
    }

    public function setOwner($name) {
        if ($name instanceof Player)
            $name = $name->getName();
        $name = strtolower($name);
        if ($this->data["owner"] != "")
            $this->setResident(false, $this->data["owner"]);
        $this->data["owner"] = strtolower($name);
        if ($name != "")
            $this->setResident(true, $name);
    }

    public function setResident($bool, $name) {
        if ($name instanceof Player)
            $name = $name->getName();
        $name = strtolower($name);
        if ($bool) {
            $this->changeResident([
                    $name
            ]);
            $this->data["resident"][$name] = true;
        } else {
            if (isset($this->data["resident"][$name])) {
                $this->changeResident([], [
                        $name
                ]);
                unset($this->data["resident"][$name]);
            }
        }
    }

    public function changeResident($add = [], $delete = []) {
        $ReaffirmedAdd = [];
        foreach ($add as $player) {
            if ($player instanceof Player)
                $player = $player->getName();
            $player = strtolower($player);
            $ReaffirmedAdd[] = $player;
        }

        $ReaffirmedDelete = [];
        foreach ($delete as $player) {
            if ($player instanceof Player)
                $player = $player->getName();
            $player = strtolower($player);
            if (isset($this->data["resident"][$player]))
                $ReaffirmedDelete[] = $player;
        }

        $event = new AreaResidentEvent($this->getOwner(), $this->getWorld(), $this->getId(), $ReaffirmedAdd, $ReaffirmedDelete);
        $event->call();
        // Server::getInstance ()->getPluginManager ()->callEvent ( $event );

        if (!$event->isCancelled()) {
            foreach ($ReaffirmedAdd as $player) {
                if ($player instanceof Player)
                    $player = $player->getName();
                $player = strtolower($player);
                $this->data["resident"][$player] = true;
            }
            foreach ($ReaffirmedDelete as $player) {
                if ($player instanceof Player)
                    $player = $player->getName();
                $player = strtolower($player);
                if (isset($this->data["resident"][$player]))
                    unset($this->data["resident"][$player]);
            }
        }
    }

    /**
     * Buy area
     *
     * @param string $player
     */
    public function buy($player) {
        if ($player instanceof Player)
            $player = $player->getName();
        $player = strtolower($player);
        $event = new AreaBuyEvent($this->getOwner(), $this->getWorld(), $this->getId(), $player);
        $event->call();
        // Server::getInstance ()->getPluginManager ()->callEvent ( $event );
        if (!$event->isCancelled())
            $this->setOwner($player);
    }

    public function get($key) {
        if (!isset($this->data[$key]))
            return null;
        return $this->data[$key];
    }

    public function getAll() {
        return $this->data;
    }

    public function getWelcome() {
        return $this->data["welcome"];
    }

    public function getResident() {
        return $this->data["resident"];
    }

    public function getPrice() {
        return $this->data["areaPrice"];
    }

    public function getCenter() {
        $xSize = $this->data["endX"] - $this->data["startX"];
        $zSize = $this->data["endZ"] - $this->data["startZ"];
        $x = $this->data["startX"] + ($xSize / 2);
        $z = $this->data["startZ"] + ($zSize / 2);
        $y = Server::getInstance()->getWorldManager()->getWorldByName($this->world)->getHighestBlockAt($x, $z);
        return new Vector3($x, $y, $z);
    }

    public function isProtected() {
        return $this->data["protect"] == true ? true : false;
    }

    public function isPvpAllow() {
        return $this->data["pvpAllow"] == true ? true : false;
    }

    public function isInvenSave() {
        return $this->data["invenSave"] == true ? true : false;
    }

    public function isResident($name) {
        if ($name instanceof Player)
            $name = $name->getName();
        $name = strtolower($name);
        return isset($this->data["resident"][strtolower($name)]) ? true : false;
    }

    public function isOwner($name) {
        if ($name instanceof Player)
            $name = $name->getName();
        $name = strtolower($name);
        return $this->data["owner"] == strtolower($name) ? true : false;
    }

    public function isAccessDeny() {
        if ($this->isCanBuy())
            return false;
        return $this->data["accessDeny"];
    }

    public function isCanBuy() {
        if (!$this->isHome())
            return false;
        return $this->data["owner"] == "" ? true : false;
    }

    public function isHome() {
        return $this->data["isHome"] == true ? true : false;
    }

    public function setHome($bool = true) {
        $this->data["isHome"] = $bool;
    }

    public function setProtect($bool = true) {
        $this->data["protect"] = $bool;
    }

    public function setPvpAllow($bool = true) {
        $this->data["pvpAllow"] = $bool;
    }

    public function setInvenSave($bool = true) {
        $this->data["invenSave"] = $bool;
    }

    public function setWelcome($string) {
        $this->data["welcome"] = mb_convert_encoding($string, "UTF-8");
    }

    public function setPrice($price) {
        $this->data["areaPrice"] = $price;
    }

    public function setAccessDeny($bool) {
        $this->data["accessDeny"] = $bool;
    }

    public function set($key, $data) {
        $this->data[$key] = $data;
    }

    public function setAll($data) {
        $this->data = $data;
    }

    public function deleteArea() {
        AreaProvider::getInstance()->deleteArea($this->world, $this->data["id"]);
    }

    public function setFence($length = 2, $fenceId = null, $fenceDamange = null) {
        if (isset($this->data["fencePos"])) {
            $this->removePastFence();
        }

        $startX = $this->data["startX"] - 1;
        $startZ = $this->data["startZ"] - 1;
        $endX = $this->data["endX"] + 1;
        $endZ = $this->data["endZ"] + 1;

        if ($fenceId === null and $fenceDamange === null) {
            $defaultFenceData = WhiteWorldProvider::getInstance()->get($this->world)->getDefaultFenceType();
            $fenceId = $defaultFenceData[0];
            $fenceDamange = $defaultFenceData[1];
        }

        $this->setHighestBlockAt($startX, $startZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->setHighestBlockAt($startX + $i, $startZ, $fenceId, $fenceDamange);
            $this->setHighestBlockAt($startX, $startZ + $i, $fenceId, $fenceDamange);
        }

        $this->setHighestBlockAt($startX, $endZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->setHighestBlockAt($startX + $i, $endZ, $fenceId, $fenceDamange);
            $this->setHighestBlockAt($startX, $endZ - $i, $fenceId, $fenceDamange);
        }

        $this->setHighestBlockAt($endX, $startZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->setHighestBlockAt($endX - $i, $startZ, $fenceId, $fenceDamange);
            $this->setHighestBlockAt($endX, $startZ + $i, $fenceId, $fenceDamange);
        }

        $this->setHighestBlockAt($endX, $endZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->setHighestBlockAt($endX - $i, $endZ, $fenceId, $fenceDamange);
            $this->setHighestBlockAt($endX, $endZ - $i, $fenceId, $fenceDamange);
        }
        $this->setSideFence($startX, $startX, $startZ, $endZ, $length, $fenceId, $fenceDamange); // UP
        $this->setSideFence($endX, $endX, $startZ, $endZ, $length, $fenceId, $fenceDamange); // DOWN
        $this->setSideFence($startX, $endX, $startZ, $startZ, $length, $fenceId, $fenceDamange); // WEST
        $this->setSideFence($startX, $endX, $endZ, $endZ, $length, $fenceId, $fenceDamange); // EAST
    }

    public function removePastFence() {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->world);
        foreach ($this->data["fencePos"] as $pos => $fence) {
            $pos = explode(":", $pos);
            $world->setBlock(new Vector3($pos[0], $pos[1], $pos[2]), BlockFactory::get(BlockLegacyIds::AIR));
        }
        unset($this->data["fencePos"]);
    }

    private function setHighestBlockAt($x, $z, $fenceId, $fenceDamange = 0) {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->world);

        $y = $world->getHighestBlockAt($x, $z);
        $blockId = $world->getBlockAt($x, $y, $z)->getId();
        $blockDmg = $world->getBlockAt($x, $y, $z)->getMeta();

        if ($blockId == $fenceId and $blockDmg == $fenceDamange)
            return;

        if ($blockId != 0) {
            $y++;
            if ($blockId == BlockLegacyIds::SIGN_POST)
                return;
            $block = BlockFactory::get($blockId, $blockDmg);
            if ($block->canBeReplaced()) {
                $y--;
            } else if (!$block->isSolid()) {
                $y--;
            }
        }

        if (!isset($this->data["fencePos"]["{$x}:{$y}:{$z}"]))
            $this->data["fencePos"]["{$x}:{$y}:{$z}"] = "{$fenceId}:{$fenceDamange}";

        $world->setBlock(new Vector3($x, $y, $z), BlockFactory::get($fenceId, $fenceDamange));
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
        for ($x = $startX; $x <= $endX; $x++)
            for ($z = $startZ; $z <= $endZ; $z++)
                if ($fenceQueue <= $length) {
                    $fenceQueue++;
                    $this->setHighestBlockAt($x, $z, $fenceId, $fenceDamange);
                } else {
                    if ($emptyQueue < 2) {
                        $emptyQueue++;
                        if ($emptyQueue >= 2) {
                            $fenceQueue = 0;
                            $emptyQueue = 0;
                        }
                        continue;
                    }
                }
    }

    /**
     *
     * @param int $length
     * @param int $fenceId
     * @param int $fenceDamange
     */
    public function removeFence($length = 2, $fenceId = null, $fenceDamange = null) {
        if (isset($this->data["fencePos"])) {
            $this->removePastFence();
            return;
        }

        $startX = $this->data["startX"] - 1;
        $startZ = $this->data["startZ"] - 1;
        $endX = $this->data["endX"] + 1;
        $endZ = $this->data["endZ"] + 1;

        if ($fenceId === null and $fenceDamange === null) {
            $defaultFenceData = WhiteWorldProvider::getInstance()->get($this->world)->getDefaultFenceType();
            $fenceId = $defaultFenceData[0];
            $fenceDamange = $defaultFenceData[1];
        }

        $this->removeHighestWall($startX, $startZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->removeHighestWall($startX + $i, $startZ, $fenceId, $fenceDamange);
            $this->removeHighestWall($startX, $startZ + $i, $fenceId, $fenceDamange);
        }

        $this->removeHighestWall($startX, $endZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->removeHighestWall($startX + $i, $endZ, $fenceId, $fenceDamange);
            $this->removeHighestWall($startX, $endZ - $i, $fenceId, $fenceDamange);
        }

        $this->removeHighestWall($endX, $startZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->removeHighestWall($endX - $i, $startZ, $fenceId, $fenceDamange);
            $this->removeHighestWall($endX, $startZ + $i, $fenceId, $fenceDamange);
        }

        $this->removeHighestWall($endX, $endZ, $fenceId, $fenceDamange);
        for ($i = 1; $i <= $length; $i++) {
            $this->removeHighestWall($endX - $i, $endZ, $fenceId, $fenceDamange);
            $this->removeHighestWall($endX, $endZ - $i, $fenceId, $fenceDamange);
        }

        $this->removeSideFence($startX, $startX, $startZ, $endZ, $fenceId, $fenceDamange); // UP
        $this->removeSideFence($endX, $endX, $startZ, $endZ, $fenceId, $fenceDamange); // DOWN
        $this->removeSideFence($startX, $endX, $startZ, $startZ, $fenceId, $fenceDamange); // WEST
        $this->removeSideFence($startX, $endX, $endZ, $endZ, $fenceId, $fenceDamange); // EAST
    }

    /**
     *
     * @param int $x
     * @param int $z
     * @param int $fenceId
     * @param int $fenceDamange
     */
    private function removeHighestWall($x, $z, $fenceId, $fenceDamange = 0) {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->world);
        $y = $world->getHighestBlockAt($x, $z);

        if ($world->getBlockAt($x, $y, $z)->getId() == $fenceId and $world->getBlock(new Vector3($x, $y, $z))->getMeta() == $fenceDamange)
            $world->setBlock(new Vector3($x, $y, $z), BlockFactory::get(BlockLegacyIds::AIR));
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

        for ($x = $startX; $x <= $endX; $x++)
            for ($z = $startZ; $z <= $endZ; $z++)
                $this->removeHighestWall($x, $z, $fenceId, $fenceDamange);
    }
}