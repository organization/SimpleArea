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
	 * Get white world data
	 *
	 * @param string $level        	
	 * @return WhiteWorldData $data | null
	 */
	public function get($level) {
		return $this->whiteWorldLoader->getWhiteWorldData ( $level );
	}
	/**
	 * Save settings (bool is async)
	 *
	 * @param string $bool        	
	 */
	public function save($bool = false) {
		if ($this->whiteWorldLoader instanceof WhiteWorldLoader)
			$this->whiteWorldLoader->save ( $bool );
	}
	/**
	 *
	 * @return WhiteWorldProvider
	 */
	public static function getInstance() {
		return static::$instance;
	}
}

?>