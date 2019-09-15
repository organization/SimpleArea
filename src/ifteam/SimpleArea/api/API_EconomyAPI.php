<?php

namespace ifteam\SimpleArea\api;

use onebone\economyapi\EconomyAPI;
use pocketmine\Server;

class API_EconomyAPI {
    private $plugin;

    public function __construct() {
        $this->plugin = Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI");
    }

    /**
     * Get API Plugin
     *
     * @return EconomyAPI
     */
    public function getPlugin() {
        return $this->plugin;
    }
}

?>