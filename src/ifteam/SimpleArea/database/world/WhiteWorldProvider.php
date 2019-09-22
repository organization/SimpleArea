<?php

namespace ifteam\SimpleArea\database\world;

class WhiteWorldProvider {
	private static $instance = null;
	/**
	 *
	 * @var WhiteWorldLoader
	 */
	private $whiteWorldLoader;

	public function __construct() {
		if (self::$instance == null)
			self::$instance = $this;
		$this->whiteWorldLoader = new WhiteWorldLoader ();
	}

	/**
	 *
	 * @return WhiteWorldProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}

	/**
	 * Get white world data
	 *
	 * @param string $world
	 * @return WhiteWorldData $data | null
	 */
	public function get($world) {
		return $this->whiteWorldLoader->getWhiteWorldData($world);
	}

	/**
	 * Save settings
	 */
	public function save() {
		if ($this->whiteWorldLoader instanceof WhiteWorldLoader)
			$this->whiteWorldLoader->save();
	}
}

?>