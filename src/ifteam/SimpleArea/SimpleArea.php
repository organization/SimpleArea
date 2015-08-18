<?php

namespace ifteam\SimpleArea;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\database\user\UserProperties;
use pocketmine\utils\Config;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use ifteam\SimpleArea\api\APILoader;
use pocketmine\event\level\LevelLoadEvent;
use ifteam\SimpleArea\database\area\AreaLoader;
use pocketmine\level\Level;
use ifteam\SimpleArea\api\AreaTax;
use ifteam\SimpleArea\database\minefarm\MineFarmManager;
use ifteam\SimpleArea\database\convert\OldSimpleAreaSupport;
use ifteam\SimpleArea\database\area\AreaManager;
use ifteam\SimpleArea\database\world\WhiteWorldManager;
use ifteam\SimpleArea\database\rent\RentManager;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\api\RentPayment;
use ifteam\SimpleArea\task\AutoSaveTask;
use ifteam\SimpleArea\database\world\WhiteWorldLoader;
use ifteam\SimpleArea\database\rent\RentLoader;
use pocketmine\event\level\LevelInitEvent;
use ifteam\SimpleArea\util\HelixCount;

class SimpleArea extends PluginBase implements Listener {
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
	 * @var UserProperties
	 */
	private $userProperties;
	/**
	 *
	 * @var AreaTax
	 */
	public $areaTax;
	/**
	 *
	 * @var RentPayment
	 */
	public $rentPayment;
	/**
	 *
	 * @var EventListener
	 */
	private $eventListener;
	/**
	 *
	 * @var AreaManager
	 */
	private $areaManager;
	/**
	 *
	 * @var WhiteWorldManager
	 */
	private $whiteWorldManager;
	/**
	 *
	 * @var MineFarmManager
	 */
	private $mineFarmManager;
	/**
	 *
	 * @var RentManager
	 */
	private $rentManager;
	/**
	 *
	 * @var RentProvider
	 */
	private $rentProvider;
	/**
	 *
	 * @var APILoader
	 */
	public $otherApi;
	private $m_version = 6;
	public $messages;
	public function onEnable() {
		new OldSimpleAreaSupport ( $this );
		
		$this->areaProvider = new AreaProvider ();
		$this->rentProvider = new RentProvider ();
		$this->whiteWorldProvider = new WhiteWorldProvider ();
		
		$this->userProperties = new UserProperties ();
		$this->otherApi = new APILoader ();
		
		$this->areaManager = new AreaManager ( $this );
		$this->rentManager = new RentManager ( $this );
		$this->whiteWorldManager = new WhiteWorldManager ( $this );
		$this->mineFarmManager = new MineFarmManager ( $this );
		
		$this->areaTax = new AreaTax ( $this );
		$this->rentPayment = new RentPayment ( $this );
		$this->eventListener = new EventListener ( $this );
		
		$this->initMessage ();
		$this->messagesUpdate ();
		
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this->userProperties, $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this->eventListener, $this );
		
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new AutoSaveTask ( $this ), 1800 );
		
		$this->registerCommand ( $this->get ( "commands-area" ), "simplearea.area", $this->get ( "commands-area-desc" ) );
		$this->registerCommand ( $this->get ( "commands-rent" ), "simplearea.rent", $this->get ( "commands-rent-desc" ) );
		$this->registerCommand ( $this->get ( "commands-whiteworld" ), "simplearea.whiteworld", $this->get ( "commands-whiteworld-desc" ) );
		$this->registerCommand ( $this->get ( "commands-minefarm" ), "simplearea.minefarm", $this->get ( "commands-minefarm-desc" ) );
		$this->registerCommand ( $this->get ( "commands-areatax" ), "simplearea.areatax", $this->get ( "commands-areatax-desc" ) );
		
		if (file_exists ( $this->getServer ()->getDataPath () . "worlds/minefarm/level.dat" )) {
			if (! $this->getServer ()->getLevelByName ( "minefarm" ) instanceof Level) {
				$this->getServer ()->loadLevel ( "minefarm" );
				WhiteWorldLoader::getInstance ()->init ( "minefarm" );
				AreaLoader::getInstance ()->init ( "minefarm" );
				RentLoader::getInstance ()->init ( "minefarm" );
			}
		}
	}
	public function onDisable() {
		if ($this->areaProvider instanceof AreaProvider)
			$this->areaProvider->save ();
		if ($this->rentProvider instanceof RentProvider)
			$this->rentProvider->save ();
		if ($this->whiteWorldProvider instanceof WhiteWorldProvider)
			$this->whiteWorldProvider->save ();
	}
	public function autoSave() {
		if ($this->areaProvider instanceof AreaProvider)
			$this->areaProvider->save ( true );
		if ($this->rentProvider instanceof RentProvider)
			$this->rentProvider->save ( true );
		if ($this->whiteWorldProvider instanceof WhiteWorldProvider)
			$this->whiteWorldProvider->save ( true );
	}
	public function onCommand(CommandSender $player, Command $command, $label, Array $args) {
		$this->eventListener->onCommand ( $player, $command, $label, $args );
	}
	public function onLevelInitEvent(LevelInitEvent $event) {
		if ($event->getLevel () instanceof Level) {
			WhiteWorldLoader::getInstance ()->init ( $event->getLevel ()->getFolderName () );
			AreaLoader::getInstance ()->init ( $event->getLevel ()->getFolderName () );
			RentLoader::getInstance ()->init ( $event->getLevel ()->getFolderName () );
			UserProperties::getInstance ()->init ( $event->getLevel ()->getFolderName () );
		}
	}
	public function onLevelLoadEvent(LevelLoadEvent $event) {
		if ($event->getLevel () instanceof Level) {
			WhiteWorldLoader::getInstance ()->init ( $event->getLevel ()->getFolderName () );
			AreaLoader::getInstance ()->init ( $event->getLevel ()->getFolderName () );
			RentLoader::getInstance ()->init ( $event->getLevel ()->getFolderName () );
			UserProperties::getInstance ()->init ( $event->getLevel ()->getFolderName () );
		}
	}
	public function initMessage() {
		$this->saveResource ( "messages.yml", false );
		$this->messages = (new Config ( $this->getDataFolder () . "messages.yml", Config::YAML ))->getAll ();
	}
	public function get($var) {
		return $this->messages [$this->messages ["default-language"] . "-" . $var];
	}
	public function message(CommandSender $player, $text = "", $mark = null) {
		if ($mark === null)
			$mark = $this->get ( "default-prefix" );
		$player->sendMessage ( TextFormat::DARK_AQUA . $mark . " " . $text );
	}
	public function alert(CommandSender $player, $text = "", $mark = null) {
		if ($mark === null)
			$mark = $this->get ( "default-prefix" );
		$player->sendMessage ( TextFormat::RED . $mark . " " . $text );
	}
	public function messagesUpdate() {
		if (! isset ( $this->messages ["default-language"] ["m_version"] )) {
			$this->saveResource ( "messages.yml", true );
			$this->messages = (new Config ( $this->getDataFolder () . "messages.yml", Config::YAML ))->getAll ();
		} else {
			if ($this->messages ["default-language"] ["m_version"] < $this->m_version) {
				$this->saveResource ( "messages.yml", true );
				$this->messages = (new Config ( $this->getDataFolder () . "messages.yml", Config::YAML ))->getAll ();
			}
		}
	}
	public function registerCommand($name, $permission, $description = "", $usage = "") {
		$commandMap = $this->getServer ()->getCommandMap ();
		$command = new PluginCommand ( $name, $this );
		$command->setDescription ( $description );
		$command->setPermission ( $permission );
		$command->setUsage ( $usage );
		$commandMap->register ( $name, $command );
	}
}

?>