<?php
/**
 *
 */

if (!defined('CONSOLE_LIB')) {
	define('CONSOLE_LIB', realpath(dirname(__FILE__) . "/.."));
}

require_once CONSOLE_LIB . "/Helper/Helper.php";

declare(ticks = 1);

/**
 * The Signal class provides helpers for process signal management
 *
 * @author Jason Belich <jason@belich.com>
 */
class Console_Helper_SignalHelper extends Console_Helper_Helper
{
	private static $signals = array();

	public static $signal_defaults = array(
		SIGPIPE =>  SIG_IGN,
		SIGCHLD =>  SIG_IGN,
		SIGWINCH => SIG_IGN,
		SIGURG =>   SIG_IGN
	);

	protected static $default_handler;

	public function __construct()
	{
		if (!self::$signals) {
			$constants = get_defined_constants(true);
			self::$signals = array_intersect_key($constants['pcntl'], array_flip(preg_grep("/^SIG[^_]/", array_keys($constants['pcntl']))));
		}

		foreach ( array_diff(self::$signals, array_keys(self::$signal_defaults) ) as $signal) {
			$this->install($signal, array($this, 'defaultSignalHandler'));
		}

		foreach (self::$signal_defaults as $signal => $callback) {
			$this->install($signal, $callback);
		}
	}

	/**
	 * Default Signal Handler
	 * 
	 * Can be overridden by setDefaultHandler(). The callback should accept the integer signal as a parameter
	 * 
	 * @param integer $signal
	 */
	protected function defaultSignalHandler($signal)
	{
		if (self::$default_handler) {
			return call_user_func(self::$default_handler, $signal);
		}

		exit;
	}

	/**
	 * Set code for the default error handler
	 *
	 * @param callback $code
	 * @throws InvalidArgumentException
	 * 
	 * @return self
	 */
	public function setDefaultHandler($code)
	{
		if (!is_callable($code)) {
			throw new InvalidArgumentException('code is not a valid callback');
		}
			
		self::$default_handler = $code;

		return $this;
	}
	
	/**
	 * Sets a handler for a specified signal
	 * 
	 * @param integer $signal
	 * @param callback $callback
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function install($signal, $callback)
	{
		if (!is_callable($code) && !in_array($callback, array(SIG_IGN, SIG_DFL))) {
			throw new InvalidArgumentException('code is not a valid callback or signal handler constant');
		}
		 
		if (!pcntl_signal($signal, $callback)) {
			throw new RuntimeException('signal handler installation failed!');
		}
	}

	/**
	 * Emits a signal, either to the current process, or to a specified pid
	 * 
	 * Usage:
	 * 		$obj->signal(SIGKILL); // sending signal to self process
	 * 		$obj->signal(123, SIGKILL); // sending signal to another process
	 * 
	 * @param integer $pid or $signal
	 * @param integer $signal or void
	 *  
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function signal()
	{
		$argc = func_num_args();
		if ($argc < 1 || $argc > 2) {
			throw new InvalidArgumentException(__METHOD__ . ' must be called with one or two arguments, with the signal being sent the solitary (if signalling the self pid) or last parameter, respectively');
		}
		$args = func_get_args();

		$signal = array_pop($args);

		$pid = $args ? array_pop($args) : posix_getpid();

		if (!posix_kill($pid, $signal)) {
			throw new RuntimeException('Signal Failed! Error Code: `' . posix_get_last_error() . '`, Error Msg: `' . posix_strerror() . '`');
		}
	}

	/**
	 * Sends a SIGALARM to the self process after a specified number of seconds
	 * 
	 * @param integer $sec
	 */
	public function alarm($sec)
	{
		pcntl_alarm($sec);
	}
	
	/**
	 * Returns the helper's canonical name
	 * 
	 * @return string
	 */
	public function getName()
	{
		return 'signal';
	}

}
