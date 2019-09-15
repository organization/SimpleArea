<?php
namespace ifteam\SimpleArea\api;

use ifteam\SimpleArea\database\area\AreaProvider;
use ifteam\SimpleArea\database\world\WhiteWorldData;
use ifteam\SimpleArea\database\world\WhiteWorldProvider;
use ifteam\SimpleArea\SimpleArea;
use ifteam\SimpleArea\task\HourTaxCheckTask;
use pocketmine\player\Player;
use pocketmine\Server;

class AreaTax {

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
     * @var \onebone\economyapi\EconomyAPI
     */
    private $economy;

    /**
     *
     * @var Server
     */
    private $server;

    public function __construct(SimpleArea $plugin) {
        $this->plugin = $plugin;
        $this->areaProvider = AreaProvider::getInstance();
        $this->whiteWorldProvider = WhiteWorldProvider::getInstance();
        $this->server = Server::getInstance();
        $this->economy = $this->plugin->otherApi->economyAPI->getPlugin();

        $this->plugin->getScheduler()->scheduleRepeatingTask(new HourTaxCheckTask($this), 3600);
    }

    public function payment() {
        if ($this->economy === null)
            return;
        foreach ($this->server->getWorldManager()->getWorlds() as $world) {
            $whiteWorld = $this->whiteWorldProvider->get($world);
            if (!$whiteWorld instanceof WhiteWorldData)
                continue;
            $areaTax = $whiteWorld->getAreaTax();

            if ($areaTax == 0)
                continue;

            $areas = $this->areaProvider->getAll($world);
            foreach ($areas as $area) {

                if (!isset($area["id"]))
                    continue;

                if (!isset($area["owner"]) or $area["owner"] === "")
                    continue;

                $money = $this->economy->myMoney($area["owner"]);

                if ($money < $areaTax) {
                    $areaInstance = $this->areaProvider->getAreaToId($world, $area["id"]);

                    $player = $this->server->getPlayer($areaInstance->getOwner());
                    if ($player instanceof Player)
                        $this->plugin->message($player, $area["id"] . $this->plugin->get("area-permissions-lost"));

                    $areaInstance->setOwner("");

                    foreach (array_keys($areaInstance->getResident()) as $resident) {
                        $player = $this->server->getPlayer($resident);
                        if ($player instanceof Player)
                            $this->plugin->message($player, $area["id"] . $this->plugin->get("area-permissions-lost-owner-problem"));

                        $areaInstance->setResident(false, $resident);
                    }
                } else {
                    $this->economy->reduceMoney($area["owner"], $areaTax);

                    $player = $this->server->getPlayer($area["owner"]);
                    if ($player instanceof Player)
                        $this->plugin->message($player, $area["id"] . $this->plugin->get("area-tax-paid") . $areaTax);
                }
            }
        }
    }
}

?>
