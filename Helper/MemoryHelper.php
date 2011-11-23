<?php
/**
 *
 */

if (!defined('CONSOLE_LIB')) {
	define('CONSOLE_LIB', realpath(dirname(__FILE__) . "/.."));
}

require_once CONSOLE_LIB . "/Helper/Helper.php";

/**
 * The Memory class provides helpers for memory control
 *
 * @author Jason Belich <jason@belich.com>
 */
class Console_Helper_MemoryHelper extends Console_Helper_Helper
{
	const ABSOLUTE_MAXIMUM = 4294967296; // 4 Gigabytes. If you need more, refactor your code.  Seriously.
	
	const MAX_MEMORY_DISCOUNT_FACTOR = 1048576; // 1 Megabyte

	const MEGABYTE = 1048576; // 1 Megabyte

	private static $hard_limit = self::ABSOLUTE_MAXIMUM;

	private $limit;
	
	public function __construct()
	{
		if (ini_get('memory_limit')) {
			self::$hard_limit = ini_get('memory_limit') - self::MAX_MEMORY_DISCOUNT_FACTOR;
		}
		
		$this->limit = self::$hard_limit;
	}

	/**
	 * Have we hit the memory limit?
	 *
	 * @param integer $megs
	 * @param boolean $current
	 *
	 * @return boolean
	 */
	public function limit($megs = NULL, $current = FALSE)
	{
		if ($megs && $megs * self::MEGABYTE < self::$hard_limit) {
			$megs = $megs * self::MEGABYTE;
		} elseif ($megs && $megs * self::MEGABYTE >= self::$hard_limit) {
			$megs = self::$hard_limit;
		} else {
			$megs = $this->limit;
		}

		return (bool) $this->using($current) >= $megs; 
	}

	/**
	 * Return the script's memory usage
	 * 
	 * @param boolean $current
	 * 
	 * @return integer
	 */
	public function using($current = FALSE)
	{
		return $current ? memory_get_usage(TRUE) : memory_get_peak_usage(TRUE);
	}
	
	/**
	 * 
	 * @param integer $megs
	 * 
	 * @return self
	 */
	public function setLimit($megs = NULL) 
	{
		$this->limit = ($megs && ($megs * self::MEGABYTE) < self::$hard_limit) ? ( $megs * self::MEGABYTE ) : self::$hard_limit;		

		return $this;
	}
	
	/**
	 * @return integer
	 */
	public function getLimit()
	{
		return $this->limit;
	}
	
	/**
	 * Returns the helper's canonical name
	 * 
	 * @return string
	 */
	public function getName()
	{
		return 'memory';
	}

}
