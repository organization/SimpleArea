<?php

namespace ifteam\SimpleArea\database\minefarm;

use ifteam\SimpleArea\SimpleArea;
use pocketmine\level\generator\Generator;
use pocketmine\level\format\FullChunk;
use pocketmine\level\generator\object\Tree;
use pocketmine\Server;
use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\database\area\AreaLoader;
use ifteam\SimpleArea\database\user\UserProperties;
use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\block\Sapling;
use pocketmine\utils\Random;

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
		$this->areaProvider = AreaProvider::getInstance ();
		$this->whiteWorldProvider = WhiteWorldProvider::getInstance ();
		$this->userProperties = UserProperties::getInstance ();
		$this->server = Server::getInstance ();
	}
	public function createWorld() {
		$generator = Generator::getGenerator ( "flat" );
		$bool = $this->server->generateLevel ( "island", null, $generator, [ 
				"preset" => "2;0;1" 
		] );
		if ($bool) {
			$whiteWorld = $this->whiteWorldProvider->get ( "island" );
			$whiteWorld->setManualCreate ( false );
			$whiteWorld->setAutoCreateAllow ( false );
			$whiteWorld->setProtect ( true );
			$whiteWorld->setInvenSave ( true );
		}
		return $bool;
	}
	public function addMineFarm($owner) {
		if ($owner instanceof Player)
			$owner = $owner->getName ();
		$owner = strtolower ( $owner );
		
		$index = $this->getIndex ();
		$defaultAreaSize = 200;
		$defaultFarmSize = 16;
		$farmX = ($defaultAreaSize + 2) * (int) ($index / 1000);
        $farmZ = ($defaultAreaSize + 2) * ($index % 1000);
		
		$startX = ( int ) round ( $farmX - ($defaultAreaSize / 2) );
		$endX = ( int ) round ( $farmX + ($defaultAreaSize / 2) );
		$startZ = ( int ) round ( $farmZ - ($defaultAreaSize / 2) );
		$endZ = ( int ) round ( $farmZ + ($defaultAreaSize / 2) );
		
		$area = $this->areaProvider->addArea ( "island", $startX, $endX, $startZ, $endZ, $owner, true, false );
		
		$center = $area->getCenter ();
		$level = $this->server->getLevelByName ( "island" );
		
		$startX = ( int ) round ( $center->x - ($defaultFarmSize / 2) );
		$endX = ( int ) round ( $center->x + ($defaultFarmSize / 2) );
		$startZ = ( int ) round ( $center->z - ($defaultFarmSize / 2) );
		$endZ = ( int ) round ( $center->z + ($defaultFarmSize / 2) );
		
		for($x = $startX; $x <= $endX; $x ++) {
			for($z = $startZ; $z <= $endZ; $z ++) {
				$chunk = $level->getChunk ( $x >> 4, $z >> 4, true );
			//	if ($chunk instanceof FullChunk) {
					if (! $chunk->isGenerated ())
						$chunk->setGenerated ( true );
					if (! $chunk->isPopulated ())
						$chunk->setPopulated ( true );
			//	}
				$center->setComponents ( $x, 0, $z );
				$level->setBlock ( $center, Block::get ( Block::BEDROCK ) );
				$center->setComponents ( $x, 1, $z );
				$level->setBlock ( $center, Block::get ( Block::BEDROCK ) );
				$center->setComponents ( $x, 2, $z );
				$level->setBlock ( $center, Block::get ( Block::DIRT ) );
				$center->setComponents ( $x, 3, $z );
				$level->setBlock ( $center, Block::get ( Block::GRASS ) );

			}
		}
		
		$center = $area->getCenter ();
		$center->setComponents ( $center->x + 4, 4, $center->z );
		
		$level->setBlock ( $center, Block::get ( Block::SAPLING, Sapling::OAK ) );
		Tree::growTree ( $level, $center->x, $center->y, $center->z, new Random ( \mt_rand () ), Sapling::OAK & 0x07 );
		
		return $area->getId ();
	}
	public function getMineFarmList($owner) {
		if ($owner instanceof Player)
			$owner = $owner->getName ();
		$owner = strtolower ( $owner );
		return $this->userProperties->getUserProperties ( $owner, "island" );
	}
	public function delMineFarm($id) {
		$this->areaProvider->deleteArea ( $level, $id );
	}
	public function getIndex() {
		return AreaLoader::getInstance ()->get ( "island", "areaIndex" );
	}
}
?>