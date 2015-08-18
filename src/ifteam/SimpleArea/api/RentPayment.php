<?php

namespace ifteam\SimpleArea\api;

use pocketmine\Server;
use ifteam\SimpleArea\SimpleArea;
use ifteam\SimpleArea\database\rent\RentProvider;
use ifteam\SimpleArea\task\HourRentPaymentTask;
use pocketmine\Player;

class RentPayment {
	private $plugin;
	/**
	 *
	 * @var \onebone\economyapi\EconomyAPI
	 */
	private $economy;
	/**
	 *
	 * @var Server
	 */
	private $server;
	/**
	 *
	 * @var RentProvider
	 */
	private $rentProvider;
	public function __construct(SimpleArea $plugin) {
		$this->plugin = $plugin;
		$this->server = Server::getInstance ();
		$this->economy = $this->plugin->otherApi->economyAPI->getPlugin ();
		$this->rentProvider = RentProvider::getInstance ();
		
		$this->server->getScheduler ()->scheduleRepeatingTask ( new HourRentPaymentTask ( $this ), 3600 );
	}
	public function payment() {
		if ($this->economy === null)
			return;
		foreach ( $this->server->getLevels () as $level ) {
			$rents = $this->rentProvider->getAll ( $level );
			if ($rents === null)
				continue;
			foreach ( $rents as $rent ) {
				if (! isset ( $rent ["rentId"] ) or ! isset ( $rent ["rentPrice"] ))
					continue;
				
				if (! isset ( $rent ["owner"] ) or $rent ["owner"] === "")
					continue;
				
				$money = $this->economy->myMoney ( $rent ["owner"] );
				if ($money < $rent ["rentPrice"]) {
					$rentInstance = $this->rentProvider->getRentToId ( $level, $rent ["rentId"] );
					
					$player = $this->server->getPlayer ( $rentInstance->getOwner () );
					if ($player instanceof Player)
						$this->plugin->message ( $player, $area ["rentId"] . $this->plugin->get ( "rent-permissions-lost" ) );
					
					$rentInstance->setOwner ( "" );
				} else {
					$this->economy->reduceMoney ( $rent ["owner"], $rent ["rentPrice"] );
					
					$player = $this->server->getPlayer ( $rent ["owner"] );
					if ($player instanceof Player)
						$this->plugin->message ( $player, $rent ["rentId"] . $this->plugin->get ( "rent-tax-paid" ) . $rent ["rentPrice"] );
				}
			}
		}
	}
}
?>