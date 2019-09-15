<?php

namespace ifteam\SimpleArea;

use ifteam\SimpleArea\api\APILoader;
use ifteam\SimpleArea\api\AreaTax;
use ifteam\SimpleArea\api\RentPayment;
use ifteam\SimpleArea\database\area\AreaLoader;
use ifteam\SimpleArea\database\area\AreaManager;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\convert\OldSimpleAreaSupport;
use ifteam\SimpleArea\database\minefarm\MineFarmManager;
use ifteam\SimpleArea\database\rent\RentLoader;
use ifteam\SimpleArea\database\rent\RentManager;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\database\user\UserProperties;
use ifteam\SimpleArea\database\world\WhiteWorldLoader;
use ifteam\SimpleArea\database\world\WhiteWorldManager;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\task\AutoSaveTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldInitEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class SimpleArea extends PluginBase implements Listener {

    public $areaProvider;
    public $areaTax;
    public $rentPayment;
    public $eventListener;
    public $otherApi;
    public $messages;
    private $whiteWorldProvider;
    private $userProperties;
    private $areaManager;
    private $whiteWorldManager;
    private $mineFarmManager;
    private $rentManager;
    private $rentProvider;
    private $m_version = 8;

    public function onEnable() {
        new OldSimpleAreaSupport ($this);

        $this->areaProvider = new AreaProvider ();
        $this->rentProvider = new RentProvider ();
        $this->whiteWorldProvider = new WhiteWorldProvider ();

        $this->userProperties = new UserProperties ();
        $this->otherApi = new APILoader ();

        $this->areaManager = new AreaManager ($this);
        $this->rentManager = new RentManager ($this);
        $this->whiteWorldManager = new WhiteWorldManager ($this);
        $this->mineFarmManager = new MineFarmManager ($this);

        $this->areaTax = new AreaTax ($this);
        $this->rentPayment = new RentPayment ($this);
        $this->eventListener = new EventListener ($this);

        $this->initMessage();
        $this->messagesUpdate();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents($this->userProperties, $this);
        $this->getServer()->getPluginManager()->registerEvents($this->eventListener, $this);

        $this->getScheduler()->scheduleRepeatingTask(new AutoSaveTask ($this), 18000);


        $this->registerCommand($this->get("commands-area"), "simplearea.area", $this->get("commands-area-desc"));
        $this->registerCommand($this->get("commands-rent"), "simplearea.rent", $this->get("commands-rent-desc"));
        $this->registerCommand($this->get("commands-whiteworld"), "simplearea.whiteworld", $this->get("commands-whiteworld-desc"));
        $this->registerCommand($this->get("commands-minefarm"), "simplearea.minefarm", $this->get("commands-minefarm-desc"));
        $this->registerCommand($this->get("commands-areatax"), "simplearea.areatax", $this->get("commands-areatax-desc"));

        if (file_exists($this->getServer()->getDataPath() . "worlds/island/world.dat")) {
            if (!$this->getServer()->getWorldManager()->getWorldByName("island") instanceof World) {
                $this->getServer()->getWorldManager()->loadWorld("island");
                WhiteWorldLoader::getInstance()->init("island");
                AreaLoader::getInstance()->init("island");
                RentLoader::getInstance()->init("island");
            }
        }
    }

    public function initMessage() {
        $this->saveResource("messages.yml", false);
        $this->messages = (new Config ($this->getDataFolder() . "messages.yml", Config::YAML))->getAll();
    }

    public function messagesUpdate() {
        if (!isset ($this->messages ["default-language"] ["m_version"])) {
            $this->saveResource("messages.yml", true);
            $this->messages = (new Config ($this->getDataFolder() . "messages.yml", Config::YAML))->getAll();
        } else {
            if ($this->messages ["default-language"] ["m_version"] < $this->m_version) {
                $this->saveResource("messages.yml", true);
                $this->messages = (new Config ($this->getDataFolder() . "messages.yml", Config::YAML))->getAll();
            }
        }
    }

    public function registerCommand($name, $permission, $description = "", $usage = "") {
        $commandMap = $this->getServer()->getCommandMap();
        $command = new PluginCommand ($name, $this);
        $command->setDescription($description);
        $command->setPermission($permission);
        $command->setUsage($usage);
        $commandMap->register($name, $command);
    }

    public function get($var) {
        return $this->messages [$this->messages ["default-language"] . "-" . $var];
    }

    public function onDisable() {
        if ($this->areaProvider instanceof AreaProvider)
            $this->areaProvider->save();
        if ($this->rentProvider instanceof RentProvider)
            $this->rentProvider->save();
        if ($this->whiteWorldProvider instanceof WhiteWorldProvider)
            $this->whiteWorldProvider->save();
    }

    public function autoSave() {
        if ($this->areaProvider instanceof AreaProvider)
            $this->areaProvider->save(true);
        if ($this->rentProvider instanceof RentProvider)
            $this->rentProvider->save(true);
        if ($this->whiteWorldProvider instanceof WhiteWorldProvider)
            $this->whiteWorldProvider->save(true);
    }

    public function onCommand(CommandSender $player, Command $command, string $label, Array $args): bool {
        return $this->eventListener->onCommand($player, $command, $label, $args);
    }

    public function onworldInitEvent(WorldInitEvent $event) {
        if ($event->getWorld() instanceof World) {
            WhiteWorldLoader::getInstance()->init($event->getWorld()->getFolderName());
            AreaLoader::getInstance()->init($event->getWorld()->getFolderName());
            RentLoader::getInstance()->init($event->getWorld()->getFolderName());
            UserProperties::getInstance()->init($event->getWorld()->getFolderName());
        }
    }

    public function onworldLoadEvent(WorldLoadEvent $event) {
        if ($event->getWorld() instanceof World) {
            WhiteWorldLoader::getInstance()->init($event->getWorld()->getFolderName());
            AreaLoader::getInstance()->init($event->getWorld()->getFolderName());
            RentLoader::getInstance()->init($event->getWorld()->getFolderName());
            UserProperties::getInstance()->init($event->getWorld()->getFolderName());
        }
    }

    public function message(CommandSender $player, $text = "", $mark = null) {
        if ($mark === null)
            $mark = $this->get("default-prefix");
        $player->sendMessage(TextFormat::DARK_AQUA . $mark . " " . $text);
    }

    public function alert(CommandSender $player, $text = "", $mark = null) {
        if ($mark === null)
            $mark = $this->get("default-prefix");
        $player->sendMessage(TextFormat::RED . $mark . " " . $text);
    }

    public function tip(Player $player, $text = "", $mark = null) {
        $player->sendTip($text);
    }
}