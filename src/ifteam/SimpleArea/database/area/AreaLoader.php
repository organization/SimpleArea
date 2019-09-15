<?php
namespace ifteam\SimpleArea\database\area;

use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;

class AreaLoader {

    private static $instance = null;

    private $areas;

    private $jsons;

    private $server;

    /* solo */
    private $playerlist = [];

    public function __construct() {
        if (self::$instance == null)
            self::$instance = $this;
        $this->server = Server::getInstance();

        $this->init();
    }

    public function init($worldName = null) {
        if ($worldName !== null) {
            $world = $this->server->getWorldManager()->getWorldByName($worldName);
            if (!$world instanceof World)
                return;
            $filePath = $this->server->getDataPath() . "worlds/" . $world->getFolderName() . "/protects.json";
            if (isset($this->jsons[$world->getFolderName()]))
                return;
            $this->jsons[$world->getFolderName()] = (new Config($filePath, Config::JSON, [
                    "areaIndex" => 0
            ]))->getAll();
            return;
        }
        foreach ($this->server->getWorldManager()->getWorlds() as $world) {
            if (!$world instanceof World)
                continue;
            $filePath = $this->server->getDataPath() . "worlds/" . $world->getFolderName() . "/protects.json";
            if (isset($this->jsons[$world->getFolderName()]))
                continue;
            $this->jsons[$world->getFolderName()] = (new Config($filePath, Config::JSON, [
                    "areaIndex" => 0
            ]))->getAll();
        }
    }

    public static function getInstance() {
        return static::$instance;
    }

    public function getArea($World, $x, $z, $player = null) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if ($player !== null) {
            if (isset($this->playerlist[$player])) {
                $id = $this->playerlist[$player];
                if (isset($this->areas[$World][$id])) {
                    $area = $this->areas[$World][$id];
                    if ($area->get('startX') <= $x && $area->get('endX') >= $x && $area->get('startZ') <= $z && $area->get('endZ') >= $z) {
                        return $area;
                    }
                }
            }
        }
        if (isset($this->areas[$World])) {
            foreach ($this->areas[$World] as $id => $area)
                if ($area->get('startX') <= $x && $area->get('endX') >= $x && $area->get('startZ') <= $z && $area->get('endZ') >= $z) {
                    if ($player !== null) {
                        $this->playerlist[$player] = $id;
                    }
                    return $area;
                }
        }
        if (!isset($this->jsons[$World]))
            return null;
        foreach ($this->jsons[$World] as $id => $area) {
            if (isset($area["startX"]))
                if ($area["startX"] <= $x && $area["endX"] >= $x && $area["startZ"] <= $z && $area["endZ"] >= $z)
                    return $this->getAreaSection($World, $area["id"]);
        }
        return null;
    }

    public function getAreaSection($World, $id) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (isset($this->areas[$World][$id])) {
            return $this->areas[$World][$id];
        } else {

            if (!isset($this->jsons[$World][$id]))
                return null;

            $areaSection = new AreaSection($this->jsons[$World][$id], $World);

            if ($areaSection == null) {
                unset($this->jsons[$World][$id]);
                return null;
            } else {
                $this->areas[$World][$id] = $areaSection;
                return $areaSection;
            }
        }
    }

    public function getAll($World) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (isset($this->jsons[$World])) {
            return $this->jsons[$World];
        } else {
            return null;
        }
    }

    public function getAreasInfo($World) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (!isset($this->jsons[$World]))
            return null;
        $info = [
                "userArea" => 0,
                "buyableArea" => 0,
                "adminArea" => 0
        ];
        foreach ($this->jsons[$World] as $id => $area) {
            if (!isset($area["isHome"]))
                continue;
            if ($area["isHome"]) {
                if ($area["owner"] == "") {
                    ++$info["buyableArea"];
                } else {
                    ++$info["userArea"];
                }
            } else {
                ++$info["adminArea"];
            }
        }
        return $info;
    }

    public function get($World, $key) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (isset($this->jsons[$World][$key])) {
            return $this->jsons[$World][$key];
        } else {
            return null;
        }
    }

    public function addAreaSection($World, array $data) {
        if ($World instanceof World)
            $World = $World->getFolderName();

        $isOverlap = $this->checkOverlap($World, $data["startX"], $data["endX"], $data["startZ"], $data["endZ"]);

        $data["id"] = $this->jsons[$World]["areaIndex"]++;
        $this->jsons[$World][$data["id"]] = $data;

        $areaSection = new AreaSection($this->jsons[$World][$data["id"]], $World);
        if ($areaSection == null) {
            unset($this->jsons[$World][$data["id"]]);
            return null;
        } else {
            $this->areas[$World][$data["id"]] = $areaSection;
            return $areaSection;
        }
    }

    public function checkOverlap($World, $startX, $endX, $startZ, $endZ, $pass = null) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (!isset($this->jsons[$World]))
            return null;
        foreach ($this->jsons[$World] as $id => $area) {
            if (isset($area["startX"])) {
                if ($pass !== null && $pass == $area["id"])
                    continue;
                if ((($area["startX"] <= $startX && $area["endX"] >= $startX) || ($area["startX"] <= $endX and $area["endX"] >= $endX)) && (($area["startZ"] <= $startZ && $area["endZ"] >= $startZ) || ($area["endZ"] <= $endZ && $area["endZ"] >= $endZ)))
                    return $this->getAreaSection($World, $area["id"]);
            }
        }
        return null;
    }

    public function deleteAreaSection($World, $id) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (isset($this->areas[$World][$id]))
            unset($this->areas[$World][$id]);
        if (isset($this->jsons[$World][$id]))
            unset($this->jsons[$World][$id]);
    }

    public function getWorldData($World) {
        if ($World instanceof World)
            $World = $World->getFolderName();
        if (isset($this->jsons[$World]))
            return $this->jsons[$World];
        return null;
    }

    public function save($bool = false) {
        foreach ($this->jsons as $WorldName => $json) {
            $filePath = $this->server->getDataPath() . "worlds/" . $WorldName . "/protects.json";
            $config = new Config($filePath, Config::JSON);
            $config->setAll($json);
            $config->save($bool);
        }
        $this->playerlist = [];
    }
}