<?php

namespace ifteam\SimpleArea\task;

use pocketmine\scheduler\Task;
use ifteam\SimpleArea\api\RentPayment;

class HourRentPaymentTask extends Task {
	/**
	 *
	 * @var RentPayment
	 */
	private $owner;
	public function __construct(RentPayment $owner) {
		$this->owner = $owner;
	}
	public function onRun($currentTick) {
		$this->owner->payment ();
	}
}

?>