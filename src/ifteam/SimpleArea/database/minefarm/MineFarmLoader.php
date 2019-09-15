<?php
namespace ifteam\SimpleArea\database\minefarm;

use ifteam\SimpleArea\database\area\AreaLoader;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\user\UserProperties;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\SimpleArea;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Random;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\generator\object\Tree;

class MineFarmLoader {

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
	 * @var UserProperties
	 */
	private $userProperties;

	/**
	 *
	 * @var Server
	 */
	private $server;

	public function __construct(SimpleArea $plugin) {
		$this->plugin = $plugin;
		$this->areaProvider = AreaProvider::getInstance();
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance();
		$this->userProperties = UserProperties::getInstance();
		$this->server = Server::getInstance();
	}

	public function createWorld() {
		$generator = GeneratorManager::getGenerator("flat");
		$bool = $this->server->getWorldManager()->generateWorld("island", null, $generator, [
				"preset" => "2;0;1"
		]);
		if ($bool) {
			$whiteWorld = $this->whiteWorldProvider->get("island");
			$whiteWorld->setManualCreate(false);
			$whiteWorld->setAutoCreateAllow(false);
			$whiteWorld->setProtect(true);
			$whiteWorld->setInvenSave(true);
		}
		return $bool;
	}

	public function addMineFarm($owner) {
		if ($owner instanceof Player)
			$owner = $owner->getName();
		$owner = strtolower($owner);

		$index = $this->getIndex();
		$defaultAreaSize = 200;
		$defaultFarmSize = 16;
		$farmX = ($defaultAreaSize + 2) * (int) ($index / 1000);
		$farmZ = ($defaultAreaSize + 2) * ($index % 1000);

		$startX = (int) round($farmX - ($defaultAreaSize / 2));
		$endX = (int) round($farmX + ($defaultAreaSize / 2));
		$startZ = (int) round($farmZ - ($defaultAreaSize / 2));
		$endZ = (int) round($farmZ + ($defaultAreaSize / 2));

		$area = $this->areaProvider->addArea("island", $startX, $endX, $startZ, $endZ, $owner, true, false);

		$center = $area->getCenter();
		$world = $this->server->getWorldManager()->getWorldByName("island");

		$startX = (int) round($center->x - ($defaultFarmSize / 2));
		$endX = (int) round($center->x + ($defaultFarmSize / 2));
		$startZ = (int) round($center->z - ($defaultFarmSize / 2));
		$endZ = (int) round($center->z + ($defaultFarmSize / 2));

		for ($x = $startX; $x <= $endX; $x++) {
			for ($z = $startZ; $z <= $endZ; $z++) {
				$chunk = $world->getChunk($x >> 4, $z >> 4, true);
				// if ($chunk instanceof FullChunk) {
				if (!$chunk->isGenerated())
					$chunk->setGenerated(true);
				if (!$chunk->isPopulated())
					$chunk->setPopulated(true);
				// }
				$center->setComponents($x, 0, $z);
				$world->setBlock($center, BlockFactory::get(BlockLegacyIds::BEDROCK));
				$center->setComponents($x, 1, $z);
				$world->setBlock($center, BlockFactory::get(BlockLegacyIds::BEDROCK));
				$center->setComponents($x, 2, $z);
				$world->setBlock($center, BlockFactory::get(BlockLegacyIds::DIRT));
				$center->setComponents($x, 3, $z);
				$world->setBlock($center, BlockFactory::get(BlockLegacyIds::GRASS));
			}
		}

		$center = $area->getCenter();
		$center->setComponents($center->x + 4, 4, $center->z);

		$world->setBlock($center, BlockFactory::get(BlockLegacyIds::SAPLING, 0));
		Tree::growTree($world, $center->x, $center->y, $center->z, new Random());

		return $area->getId();
	}

	public function getIndex() {
		return AreaLoader::getInstance()->get("island", "areaIndex");
	}

	public function getMineFarmList($owner) {
		if ($owner instanceof Player)
			$owner = $owner->getName();
		$owner = strtolower($owner);
		return $this->userProperties->getUserProperties($owner, "island");
	}

	public function delMineFarm($id) {
		$this->areaProvider->deleteArea("island", $id);
	}
}