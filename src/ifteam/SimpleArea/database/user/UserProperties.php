<?php
namespace ifteam\SimpleArea\database\user;

use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\area\AreaSection;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\database\rent\RentSection;
use ifteam\SimpleArea\database\world\WhiteWorldData;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\event\AreaAddEvent;
use ifteam\SimpleArea\event\AreaDeleteEvent;
use ifteam\SimpleArea\event\AreaResidentEvent;
use ifteam\SimpleArea\event\RentAddEvent;
use ifteam\SimpleArea\event\RentBuyEvent;
use ifteam\SimpleArea\event\RentDeleteEvent;
use ifteam\SimpleArea\event\RentOutEvent;
use ifteam\SimpleArea\SimpleArea;
use ifteam\SimpleArea\task\CheckAreaEventTask;
use ifteam\SimpleArea\task\CheckRentEventTask;
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

class UserProperties implements Listener {

    private static $instance = null;

    /**
     *
     * @var Server
     */
    private $server;

    /**
     *
     * @var SimpleArea
     */
    private $plugin;

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

    private $properties = [];

    private $rentProperties = [];

    private $saleList = [];

    private $rentSaleList = [];

    public function __construct() {
        if (self::$instance == null)
            self::$instance = $this;
        $this->server = Server::getInstance();
        $this->plugin = $this->server->getPluginManager()->getPlugin("SimpleArea");
        $this->areaProvider = AreaProvider::getInstance();
        $this->rentProvider = RentProvider::getInstance();
        $this->whiteWorldProvider = WhiteWorldProvider::getInstance();
        $this->init();
    }

    /**
     * Load list the user area holdings
     */
    public function init($worldName = null) {
        if ($worldName !== null) {
            $world = $this->server->getWorldManager()->getWorldByName($worldName);
            if (!$world instanceof World)
                return;
            $areas = $this->areaProvider->getAll($world->getFolderName());
            foreach ($areas as $area) {
                if (isset($area["resident"]) and count($area["resident"]) == 0) {
                    $this->addSaleList($world->getFolderName(), $area["id"]);
                    continue;
                }
                if (!isset($area["resident"]) or !isset($area["id"]) or !is_array($area["resident"]))
                    continue;
                foreach (array_keys($area["resident"]) as $resident)
                    $this->addUserProperties($resident, $world->getFolderName(), $area["id"]);
            }
            $rents = $this->rentProvider->getAll($world->getFolderName());
            foreach ($rents as $rent) {
                if (!isset($rent["owner"]))
                    continue;
                if ($rent["owner"] == "") {
                    $this->addRentSaleList($world->getFolderName(), $rent["rentId"]);
                    continue;
                }
                $this->addUserRentProperties($rent["owner"], $world->getFolderName(), $rent["rentId"]);
            }
        }
        foreach ($this->server->getWorldManager()->getWorlds() as $world)
            if ($world instanceof World) {
                $areas = $this->areaProvider->getAll($world->getFolderName());
                $whiteWorld = $this->whiteWorldProvider->get($world);
                foreach ($areas as $area) {
                    if (isset($area["resident"]) and count($area["resident"]) == 0) {
                        $this->addSaleList($world->getFolderName(), $area["id"]);
                        continue;
                    }
                    if (!isset($area["resident"]) or !isset($area["id"]) or !is_array($area["resident"]))
                        continue;
                    if ($whiteWorld->isCountShareArea()) {
                        foreach (array_keys($area["resident"]) as $resident)
                            $this->addUserProperties($resident, $world->getFolderName(), $area["id"]);
                    } else {
                        if ($area["owner"] != "")
                            $this->addUserProperties($area["owner"], $world->getFolderName(), $area["id"]);
                    }
                }
                $rents = $this->rentProvider->getAll($world->getFolderName());
                foreach ($rents as $rent) {
                    if (!isset($rent["owner"]))
                        continue;
                    if ($rent["owner"] == "") {
                        $this->addRentSaleList($world->getFolderName(), $rent["rentId"]);
                        continue;
                    }
                    $this->addUserRentProperties($rent["owner"], $world->getFolderName(), $rent["rentId"]);
                }
            }
    }

    /**
     * Add can buy area list
     *
     * @param string $world
     * @param int $areaId
     */
    public function addSaleList($world, $areaId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        $this->saleList[$world][$areaId] = true;
    }

    /**
     * Add user area holdings
     *
     * @param string $username
     * @param string $world
     * @param int $areaId
     */
    public function addUserProperties($username, $world, $areaId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if ($username instanceof Player)
            $username = $username->getName();
        $username = strtolower($username);
        $this->properties[$username][$world][$areaId] = true;
    }

    /**
     * Add can buy rent list
     *
     * @param string $world
     * @param int $areaId
     */
    public function addRentSaleList($world, $areaId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        $this->rentSaleList[$world][$areaId] = true;
    }

    /**
     * Add user rent holdings
     *
     * @param string $username
     * @param string $world
     * @param int $rentId
     */
    public function addUserRentProperties($username, $world, $rentId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if ($username instanceof Player)
            $username = $username->getName();
        $username = strtolower($username);
        $this->rentProperties[$username][$world][$rentId] = true;
    }

    /**
     *
     * @return UserProperties
     */
    public static function getInstance() {
        return static::$instance;
    }

    /**
     * Get user area holdings
     *
     * @param string $username
     * @param string $world
     * @return array
     */
    public function getUserProperties($username, $world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if ($username instanceof Player)
            $username = $username->getName();
        $username = strtolower($username);
        if (isset($this->properties[$username][$world]))
            return $this->properties[$username][$world];
        return [];
    }

    /**
     * Get user rent holdings
     *
     * @param string $username
     * @param string $world
     * @return array
     */
    public function getUserRentProperties($username, $world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if ($username instanceof Player)
            $username = $username->getName();
        $username = strtolower($username);
        if (isset($this->rentProperties[$username][$world]))
            return $this->rentProperties[$username][$world];
        return [];
    }

    /**
     * Get can buy area list
     *
     * @param string $world
     * @return array
     */
    public function getSaleList($world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if (isset($this->saleList[$world]))
            return $this->saleList[$world];
        return [];
    }

    /**
     * Get can buy rent list
     *
     * @param string $world
     * @return array
     */
    public function getRentSaleList($world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if (isset($this->rentSaleList[$world]))
            return $this->rentSaleList[$world];
        return [];
    }

    /**
     * Apply area event
     *
     * @param Event $event
     */
    public function applyAreaEvent(Event $event) {
        switch (true) {
            case $event instanceof AreaAddEvent:
                $area = $event->getAreaData();
                $whiteWorld = $event->getWhtieWorldData();

                if (!$area instanceof AreaSection)
                    return;
                if (!$whiteWorld instanceof WhiteWorldData)
                    return;

                $residents = $area->getResident();
                if (count($residents) == 0) {
                    $this->addSaleList($area->getWorld(), $area->getId());
                    return;
                }
                if ($whiteWorld->isCountShareArea()) {
                    foreach (array_keys($residents) as $resident)
                        $this->addUserProperties($resident, $area->getWorld(), $area->getId());
                } else {
                    if ($area->getOwner() != "")
                        $this->addUserProperties($area->getOwner(), $area->getWorld(), $area->getId());
                }
                break;
            case $event instanceof AreaDeleteEvent:
                $whiteWorld = $event->getWhiteWorldData();
                $area = $event->getAreaData();

                if (!$area instanceof AreaSection)
                    return;
                if (!$whiteWorld instanceof WhiteWorldData)
                    return;

                $residents = $event->getResident();
                $this->deleteSaleList($event->getWorld(), $event->getAreaId());

                if ($whiteWorld->isCountShareArea()) {
                    foreach (array_keys($residents) as $resident)
                        $this->addUserProperties($resident, $event->getWorld(), $event->getAreaId());
                } else {
                    if ($area->getOwner() != "")
                        $this->addUserProperties($area->getOwner(), $event->getWorld(), $event->getAreaId());
                }
                break;
            case $event instanceof AreaResidentEvent:
                $area = $event->getAreaData();
                if (!$area instanceof AreaSection)
                    return;
                if (count($event->getAdded()) == 0) {
                    $residents = $area->getResident();
                    foreach ($event->getDeleted() as $player)
                        if (isset($residents[$player]))
                            unset($residents[$player]);
                    if (count($residents) == 0) {
                        $this->addSaleList($area->getWorld(), $area->getId());
                    } else {
                        $this->deleteSaleList($area->getWorld(), $area->getId());
                    }
                } else {
                    $this->deleteSaleList($area->getWorld(), $area->getId());
                }
                foreach ($event->getAdded() as $player)
                    $this->addUserProperties($player, $area->getWorld(), $area->getId());
                foreach ($event->getDeleted() as $player)
                    $this->deleteUserProperties($player, $area->getWorld(), $area->getId());
                break;
        }
    }

    /**
     * Delete can buy area list
     *
     * @param string $world
     * @param int $areaId
     */
    public function deleteSaleList($world, $areaId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if (isset($this->saleList[$world][$areaId]))
            unset($this->saleList[$world][$areaId]);
    }

    /**
     * Delete user area holdings
     *
     * @param string $username
     * @param string $world
     * @param int $areaId
     */
    public function deleteUserProperties($username, $world, $areaId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if ($username instanceof Player)
            $username = $username->getName();
        $username = strtolower($username);
        if (isset($this->properties[$username][$world][$areaId]))
            unset($this->properties[$username][$world][$areaId]);
    }

    public function onAreaAddEvent(AreaAddEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckAreaEventTask($this, $event), 1);
    }

    public function onAreaDeleteEvent(AreaDeleteEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckAreaEventTask($this, $event), 1);
    }

    public function onAreaResidentEvent(AreaResidentEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckAreaEventTask($this, $event), 1);
    }

    /**
     * Apply rent event
     *
     * @param Event $event
     */
    public function applyRentEvent(Event $event) {
        switch (true) {
            case $event instanceof RentAddEvent:
                $rent = $event->getRentData();
                if (!$rent instanceof RentSection)
                    return;

                $owner = $rent->getOwner();
                if ($owner == "") {
                    $this->addRentSaleList($rent->getWorld(), $rent->getRentId());
                } else {
                    $this->addUserRentProperties($owner, $rent->getWorld(), $rent->getRentId());
                }
                break;
            case $event instanceof RentDeleteEvent:
                $rent = $event->getRentData();
                if (!$rent instanceof RentSection)
                    return;

                $this->deleteRentSaleList($rent->getWorld(), $rent->getRentId());
                $this->deleteUserRentProperties($rent->getOwner(), $rent->getWorld(), $rent->getRentId());
                break;
            case $event instanceof RentBuyEvent:
                $rent = $event->getRentData();
                if (!$rent instanceof RentSection)
                    return;

                $this->deleteRentSaleList($rent->getWorld(), $rent->getRentId());
                $this->addUserRentProperties($rent->getOwner(), $rent->getWorld(), $rent->getRentId());
                break;
            case $event instanceof RentOutEvent:
                $rent = $event->getRentData();
                if (!$rent instanceof RentSection)
                    return;

                $this->addRentSaleList($rent->getWorld(), $rent->getRentId());
                $this->deleteUserRentProperties($rent->getOwner(), $rent->getWorld(), $rent->getRentId());
                break;
        }
    }

    /**
     * Delete can buy rent list
     *
     * @param string $world
     * @param int $rentId
     */
    public function deleteRentSaleList($world, $rentId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if (isset($this->rentSaleList[$world][$rentId]))
            unset($this->rentSaleList[$world][$rentId]);
    }

    /**
     * Delete user rent holdings
     *
     * @param string $username
     * @param string $world
     * @param int $rentId
     */
    public function deleteUserRentProperties($username, $world, $rentId) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if ($username instanceof Player)
            $username = $username->getName();
        $username = strtolower($username);
        if (isset($this->rentProperties[$username][$world][$rentId]))
            unset($this->rentProperties[$username][$world][$rentId]);
    }

    public function onRentAddEvent(RentAddEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckRentEventTask($this, $event), 1);
    }

    public function onRentDeleteEvent(RentDeleteEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckRentEventTask($this, $event), 1);
    }

    public function onRentBuyEvent(RentBuyEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckRentEventTask($this, $event), 1);
    }

    public function onRentOutEvent(RentOutEvent $event) {
        $this->plugin->getScheduler()->scheduleDelayedTask(new CheckRentEventTask($this, $event), 1);
    }
}
