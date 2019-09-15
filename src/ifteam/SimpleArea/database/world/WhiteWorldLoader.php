<?php
namespace ifteam\SimpleArea\database\world;

use ifteam\SimpleArea\database\area\AreaLoader;
use ifteam\SimpleArea\database\area\AreaSection;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;

class WhiteWorldLoader {

    private static $instance = null;
    private $jsons;
    private $whiteWorlds;

    public function __construct() {
        if (self::$instance == null)
            self::$instance = $this;
        $this->init();
    }

    /**
     * Create a default setting
     */
    public function init($worldName = null) {
        if ($worldName !== null) {
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
            $filePath = Server::getInstance()->getDataPath() . "worlds/" . $world->getFolderName() . "/options.json";
            if (isset($this->jsons[$world->getFolderName()]))
                return;
            $this->jsons[$world->getFolderName()] = (new Config($filePath, Config::JSON, [
                    "protect" => false,
                    "defaultAreaPrice" => 5000,
                    "welcome" => "",
                    "pvpAllow" => true,
                    "invenSave" => true,
                    "autoCreateAllow" => true,
                    "manualCreate" => true,
                    "areaHoldLimit" => 4,
                    "defaultAreaSize" => [
                            32,
                            22
                    ],
                    "defaultFenceType" => [
                            139,
                            1
                    ],
                    "manualCreateMinSize" => 10,
                    "manualCreateMaxSize" => 500
            ]))->getAll();
            $this->whiteWorlds[$world->getFolderName()] = new WhiteWorldData($this->jsons[$world->getFolderName()], $world->getFolderName());
            return;
        }
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            if (!$world instanceof World)
                continue;
            $filePath = Server::getInstance()->getDataPath() . "worlds/" . $world->getFolderName() . "/options.json";
            if (isset($this->jsons[$world->getFolderName()]))
                continue;
            $this->jsons[$world->getFolderName()] = (new Config($filePath, Config::JSON, [
                    "protect" => false,
                    "defaultAreaPrice" => 5000,
                    "welcome" => "",
                    "pvpAllow" => true,
                    "invenSave" => true,
                    "autoCreateAllow" => true,
                    "manualCreate" => true,
                    "areaHoldLimit" => 4,
                    "defaultAreaSize" => [
                            32,
                            22
                    ],
                    "defaultFenceType" => [
                            139,
                            0
                    ],
                    "manualCreateMinSize" => 10,
                    "manualCreateMaxSize" => 500
            ]))->getAll();
            $this->whiteWorlds[$world->getFolderName()] = new WhiteWorldData($this->jsons[$world->getFolderName()], $world->getFolderName());
        }
    }

    /**
     *
     * @return AreaLoader
     */
    public static function getInstance() {
        return static::$instance;
    }

    /**
     * Get All area data of the world
     *
     * @param string $world
     * @return NULL|AreaSection
     */
    public function getAll($world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if (isset($this->jsons[$world])) {
            return $this->jsons[$world];
        } else {
            return null;
        }
    }

    /**
     * getWhiteWorldData
     *
     * @param string $world
     * @return WhiteWorldData $data | null
     */
    public function getWhiteWorldData($world) {
        if ($world instanceof World)
            $world = $world->getFolderName();
        if (isset($this->whiteWorlds[$world])) {
            return $this->whiteWorlds[$world];
        } else {
            return null;
        }
    }

    /**
     * Save settings (bool is async)
     *
     * @param string $bool
     */
    public function save($bool = false) {
        foreach ($this->jsons as $worldName => $json) {
            $filePath = Server::getInstance()->getDataPath() . "worlds/" . $worldName . "/options.json";
            $config = new Config($filePath, Config::JSON);
            $config->setAll($json);
            $config->save($bool);
        }
    }
}