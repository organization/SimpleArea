<?php

namespace ifteam\SimpleArea\api;

use onebone\economyapi\EconomyAPI;
use pocketmine\Server;

class API_EconomyAPI {
	/** @var EconomyAPI|null */
	private $plugin;

	public function __construct() {
		$this->plugin = Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI");
	}

	/**
	 * Get API Plugin
	 *
	 * @return EconomyAPI
	 */
	public function getPlugin(): EconomyAPI {
		return $this->plugin;
	}
}

?>