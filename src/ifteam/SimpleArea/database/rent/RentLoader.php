<?php

namespace ifteam\SimpleArea\database\rent;

use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;

class RentLoader {
	private static $instance = null;
	private $rents;
	private $jsons;

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
			if (!$world instanceof World)
				return;
			$filePath = Server::getInstance()->getDataPath() . "worlds/" . $world->getFolderName() . "/rents.json";
			if (isset ($this->jsons [$world->getFolderName()]))
				return;
			$this->jsons [$world->getFolderName()] = (new Config ($filePath, Config::JSON, [
					"rentIndex" => 0
			]))->getAll();
			return;
		}
		foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
			if (!$world instanceof World)
				continue;
			$filePath = Server::getInstance()->getDataPath() . "worlds/" . $world->getFolderName() . "/rents.json";
			if (isset ($this->jsons [$world->getFolderName()]))
				continue;
			$this->jsons [$world->getFolderName()] = (new Config ($filePath, Config::JSON, [
					"rentIndex" => 0
			]))->getAll();
		}
	}

	/**
	 *
	 * @return RentLoader
	 */
	public static function getInstance() {
		return static::$instance;
	}

	/**
	 * Get rent data of the world (using x, y, z)
	 *
	 * @param string $world
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return NULL|RentSection
	 */
	public function getRent($world, $x, $y, $z) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (!isset ($this->jsons [$world]))
			return null;
		foreach ($this->jsons [$world] as $id => $rent)
			if (isset ($rent ["startX"]))
				if ($rent ["startX"] <= $x and $rent ["endX"] >= $x and $rent ["startY"] <= $y and $rent ["endY"] >= $y and $rent ["startZ"] <= $z and $rent ["endZ"] >= $z)
					return $this->getRentSection($world, $rent ["rentId"]);
		return null;
	}

	/**
	 *
	 * @param int $world
	 * @param int $id
	 * @return RentSection|NULL
	 */
	public function getRentSection($world, $id) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (isset ($this->rents [$world] [$id])) {
			return $this->rents [$world] [$id];
		} else {

			if (!isset ($this->jsons [$world] [$id]))
				return null;

			$rentSection = new RentSection ($this->jsons [$world] [$id], $world);

			if ($rentSection == null) {
				unset ($this->jsons [$world] [$id]);
				return null;
			} else {
				$this->rents [$world] [$id] = $rentSection;
				return $rentSection;
			}
		}
	}

	/**
	 * Get All rent data of the world
	 *
	 * @param string $world
	 * @return NULL|Array
	 */
	public function getAll($world) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (isset ($this->jsons [$world])) {
			return $this->jsons [$world];
		} else {
			return null;
		}
	}

	/**
	 * Get All rents info of the world
	 *
	 * @param string $world
	 * @return NULL|Array
	 */
	public function getRentsInfo($world) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (!isset ($this->jsons [$world]))
			return null;
		$info = [
				"userRent" => 0,
				"buyableRent" => 0
		];
		foreach ($this->jsons [$world] as $id => $rent) {
			if (!isset ($rent ["owner"]))
				continue;
			if ($rent ["owner"] == "") {
				++$info ["buyableRent"];
			} else {
				++$info ["userRent"];
			}
		}
		return $info;
	}

	/**
	 * Get rent data of the world using key
	 *
	 * @param string $world
	 * @return NULL|Array
	 */
	public function get($world, $key) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (isset ($this->jsons [$world] [$key])) {
			return $this->jsons [$world] [$key];
		} else {
			return null;
		}
	}

	/**
	 * addRentSection (addRent)
	 *
	 * @param string $world
	 * @param array $data
	 * @return NULL|RentSection
	 */
	public function addRentSection($world, array $data) {
		if ($world instanceof World)
			$world = $world->getFolderName();

		$isOverlap = $this->checkOverlap($world, $data ["startX"], $data ["endX"], $data ["startY"], $data ["endY"], $data ["startZ"], $data ["endZ"]);
		if ($isOverlap !== null)
			return null;

		$data ["rentId"] = $this->jsons [$world] ["rentIndex"]++;
		$this->jsons [$world] [$data ["rentId"]] = $data;

		$rentSection = new RentSection ($this->jsons [$world] [$data ["rentId"]], $world);
		if ($rentSection == null) {
			unset ($this->jsons [$world] [$data ["rentId"]]);
			return null;
		} else {
			$this->rents [$world] [$data ["rentId"]] = $rentSection;
			return $rentSection;
		}
	}

	/**
	 *
	 * @param string $world
	 * @param int $startX
	 * @param int $endX
	 * @param int $startY
	 * @param int $endY
	 * @param int $startZ
	 * @param int $endZ
	 * @return NULL|RentSection
	 */
	public function checkOverlap($world, $startX, $endX, $startY, $endY, $startZ, $endZ) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		foreach ($this->jsons [$world] as $id => $rent)
			if (isset ($rent ["startX"]))
				if ((($rent ["startX"] <= $startX and $rent ["endX"] >= $startX) or ($rent ["startX"] <= $endX and $rent ["endX"] >= $endX)) and (($rent ["startY"] <= $startY and $rent ["endY"] >= $startY) or ($rent ["startY"] <= $endY and $rent ["endY"] >= $endY)) and (($rent ["startZ"] <= $startZ and $rent ["endZ"] >= $startZ) or ($rent ["endZ"] <= $endZ and $rent ["endZ"] >= $endZ)))
					return $this->getRentSection($world, $rent ["rentId"]);
		return null;
	}

	public function deleteRentSection($world, $id) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (isset ($this->rents [$world] [$id]))
			unset ($this->rents [$world] [$id]);
		if (isset ($this->jsons [$world] [$id]))
			unset ($this->jsons [$world] [$id]);
	}

	/**
	 * Get world data
	 *
	 * @param string $world
	 */
	public function getWorldData($world) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		if (isset ($this->jsons [$world]))
			return $this->jsons [$world];
		return null;
	}

	/**
	 * Save settings (bool is async)
	 *
	 * @param string $bool
	 */
	public function save($bool = false) {
		foreach ($this->jsons as $worldName => $json) {
			$filePath = Server::getInstance()->getDataPath() . "worlds/" . $worldName . "/rents.json";
			$config = new Config ($filePath, Config::JSON);
			$config->setAll($json);
			$config->save($bool);
		}
	}
}