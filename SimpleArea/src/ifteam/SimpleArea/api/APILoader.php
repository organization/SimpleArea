<?php

namespace ifteam\SimpleArea\api;

class APILoader {
	public $economyAPI;
	public function __construct() {
		$this->economyAPI = new API_EconomyAPI ();
	}
}

?>