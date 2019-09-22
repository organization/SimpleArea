<?php

namespace ifteam\SimpleArea\database\rent;

use ifteam\SimpleArea\event\RentAddEvent;
use ifteam\SimpleArea\event\RentDeleteEvent;
use pocketmine\world\World;

class RentProvider {
	private static $instance = null;
	/**
	 *
	 * @var RentLoader
	 */
	private $rentLoader;

	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->rentLoader = new RentLoader ();
	}

	/**
	 *
	 * @return RentProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}

	/**
	 * Add rent data
	 *
	 * @param string $world
	 * @param int $startX
	 * @param int $endX
	 * @param int $startY
	 * @param int $endY
	 * @param int $startZ
	 * @param int $endZ
	 * @param string $owner
	 * @param bool $isHome
	 * @return RentSection|NULL
	 */
	public function addRent($world, $startX, $endX, $startY, $endY, $startZ, $endZ, $areaId, $price) {
		if ($world instanceof World)
			$world = $world->getFolderName();

		if ($startX > $endX) {
			$backup = $startX;
			$startX = $endX;
			$endX = $backup;
		}
		if ($startY > $endY) {
			$backup = $startY;
			$startY = $endY;
			$endY = $backup;
		}
		if ($startZ > $endZ) {
			$backup = $startZ;
			$startZ = $endZ;
			$endZ = $backup;
		}

		$data = [
				"areaId" => $areaId,
				"owner" => "",
				"rentPrice" => $price,
				"startX" => $startX,
				"endX" => $endX,
				"startY" => $startY,
				"endY" => $endY,
				"startZ" => $startZ,
				"endZ" => $endZ
		];

		$rent = $this->rentLoader->addRentSection($world, $data);
		if (!$rent instanceof RentSection)
			return null;

		$event = new RentAddEvent ($rent->getOwner(), $rent->getWorld(), $rent->getRentId());
		$event->call();
		if ($event->isCancelled()) {
			$this->deleteRent($rent->getWorld(), $rent->getRentId());
			return null;
		}

		return $rent;
	}

	/**
	 *
	 * @param string $world
	 * @param string $id
	 */
	public function deleteRent($world, $id) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		$rent = $this->getRentToId($world, $id);
		$event = new RentDeleteEvent ($rent->getOwner(), $rent->getWorld(), $rent->getRentId());
		$event->call();
		if ($event->isCancelled())
			return;
		$this->rentLoader->deleteRentSection($world, $id);
	}

	/**
	 * Get rent data of the world (using Id)
	 *
	 * @param string $world
	 * @param int $id
	 * @return RentSection|NULL
	 */
	public function getRentToId($world, $id) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		return $this->rentLoader->getRentSection($world, $id);
	}

	/**
	 * Get all rent data of the world
	 *
	 * @param string $world
	 * @return array $rents
	 */
	public function getAll($world) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		return $this->rentLoader->getAll($world);
	}

	/**
	 * Get All rents info of the world
	 *
	 * @param string $world
	 * @return NULL|array
	 */
	public function getRentsInfo($world): ?array {
		if ($world instanceof World)
			$world = $world->getFolderName();
		return $this->rentLoader->getRentsInfo($world);
	}

	/**
	 * Get rent data of the world
	 *
	 * @param string $world
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return RentSection|NULL
	 */
	public function getRent($world, $x, $y, $z): ?RentSection {
		if ($world instanceof World)
			$world = $world->getFolderName();
		return $this->rentLoader->getRent($world, $x, $y, $z);
	}

	/**
	 * Get checkOverlap of the world
	 *
	 * @param string $world
	 * @param int $startX
	 * @param int $endX
	 * @param int $startY
	 * @param int $endY
	 * @param int $startZ
	 * @param int $endZ
	 * @return RentSection|NULL
	 */
	public function checkOverlap($world, $startX, $endX, $startY, $endY, $startZ, $endZ) {
		if ($world instanceof World)
			$world = $world->getFolderName();
		return $this->rentLoader->checkOverlap($world, $startX, $endX, $startY, $endY, $startZ, $endZ);
	}

	/**
	 * Save settings
	 */
	public function save() {
		if ($this->rentLoader instanceof RentLoader)
			$this->rentLoader->save();
	}
}

?>