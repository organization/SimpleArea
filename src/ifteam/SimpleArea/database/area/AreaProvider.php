<?php
namespace ifteam\SimpleArea\database\area;

use ifteam\SimpleArea\event\AreaAddEvent;
use ifteam\SimpleArea\event\AreaDeleteEvent;
use pocketmine\world\World;

class AreaProvider {

    private static $instance = null;

    /**
     *
     * @var AreaLoader
     */
    private $areaLoader;

    public function __construct() {
        if (self::$instance == null)
            self::$instance = $this;
        $this->areaLoader = new AreaLoader();
    }

    public static function getInstance() {
        return static::$instance;
    }

    public function addArea($world, $startX, $endX, $startZ, $endZ, $owner, $isHome, $fenceSet = true) {
        if ($world instanceof World)
            $world = $world->getFolderName();

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

        $data = [
                "owner" => "",
                "resident" => [],
                "isHome" => $isHome,
                "startX" => $startX,
                "endX" => $endX,
                "startZ" => $startZ,
                "endZ" => $endZ
        ];

        $area = $this->areaLoader->addAreaSection($world, $data);
        if (!$area instanceof AreaSection)
            return null;

        if ($isHome and $fenceSet)
            $area->setFence();

        if ($owner != "")
            $area->setOwner($owner);

        $event = new AreaAddEvent($area->getOwner(), $area->getWorld(), $area->getId());
        $event->call();
        // Server::getInstance ()->getPluginManager ()->callEvent ( $event );
        /*
         * if ($event->isCancelled()) {
         * $this->deleteArea($area->getWorld(), $area->getId());
         * return null;
         * }
         */

        return $area;
    }

    public function deleteArea($world, $id) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        $area = $this->getAreaToId($world, $id);

        if ($area->isHome())
            $area->removeFence();

        if ($area instanceof AreaSection) {
            $event = new AreaDeleteEvent($area->getOwner(), $area->getWorld(), $area->getId(), $area->getResident());
            $event->call();
            // Server::getInstance ()->getPluginManager ()->callEvent ( $event );
            if ($event->isCancelled())
                return;
        }
        $this->areaLoader->deleteAreaSection($world, $id);
    }

    public function getAreaToId($world, $id) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        return $this->areaLoader->getAreaSection($world, $id);
    }

    public function getAll($world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        return $this->areaLoader->getAll($world);
    }

    public function getAreasInfo($world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        return $this->areaLoader->getAreasInfo($world);
    }

    public function getArea($world, $x, $z, $player = null) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        return $this->areaLoader->getArea($world, $x, $z, $player);
    }

    public function resizeArea($world, $id, $startX = 0, $endX = 0, $startZ = 0, $endZ = 0, $resetFence = true) {
        $area = $this->getAreaToId($world, $id);
        if (!$area instanceof AreaSection)
            return false;

        $rstartX = $area->get("startX") - $startX;
        $rendX = $area->get("endX") + $endX;
        $rstartZ = $area->get("startZ") - $startZ;
        $rendZ = $area->get("endZ") + $endZ;

        $getOverlap = $this->checkOverlap($world, $rstartX, $rendX, $rstartZ, $rendZ, $id);
        if ($getOverlap instanceof AreaSection)
            return false;

        if ($resetFence)
            $area->removeFence();

        $area->set("startX", $rstartX);
        $area->set("startZ", $rstartZ);
        $area->set("endX", $rendX);
        $area->set("endZ", $rendZ);

        if ($resetFence)
            $area->setFence();
        return true;
    }

    public function checkOverlap($world, $startX, $endX, $startZ, $endZ, $pass = null) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        return $this->areaLoader->checkOverlap($world, $startX, $endX, $startZ, $endZ, $pass);
    }

    public function save($bool = false) {
        if ($this->areaLoader instanceof AreaLoader)
            $this->areaLoader->save($bool);
    }
}

?>