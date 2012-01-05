<?php

if (!defined('CONSOLE_LIB')) {
	define('CONSOLE_LIB', realpath(dirname(__FILE__) . "/.."));
}

require_once CONSOLE_LIB . "/Command/Command.php";
require_once CONSOLE_LIB . "/Application.php";
require_once CONSOLE_LIB . "/Helper/HelperSet.php";
require_once CONSOLE_LIB . "/Helper/MemoryHelper.php";
require_once CONSOLE_LIB . "/Helper/SignalHelper.php";
require_once CONSOLE_LIB . "/Input/InputInterface.php";
require_once CONSOLE_LIB . "/Output/OutputInterface.php";

class Console_Daemon_Daemon extends Console_Command_Command
{

	private $code;

	private $catchExceptions = true;


	/**
	 * Sets whether to catch exceptions or not during daemon loop.
	 *
	 * @param Boolean $boolean Whether to catch exceptions or not during daemon loop
	 *
	 * @api
	 */
	public function setCatchExceptions($boolean)
	{
		$this->catchExceptions = (Boolean) $boolean;
	}

	/**
	 * Sets the application instance for this command.
	 *
	 * @param Application $application An Application instance
	 *
	 * @api
	 */
	public function setApplication(Console_Application $application = null)
	{
		if ($application) {
			parent::setApplication($application);
		}

		$helperSet = $this->getHelperSet();
		if (!$helperSet instanceof Console_Helper_HelperSet) {
			$this->setHelperSet(new Console_Helper_HelperSet(array(
				new Console_Helper_MemoryHelper()
			)));

			$helperSet = $this->getHelperSet();
		}
			
		if (!$helperSet->has('memory')) {
			$helperSet->set(new Console_Helper_MemoryHelper());
		}
	}


	/**
	 * Sets the code to execute when running this daemon.
	 *
	 * If this method is used, it overrides the code defined
	 * in the main() method.
	 *
	 * @param \Closure $code A \Closure
	 *
	 * @return Command The current instance
	 *
	 * @see main()
	 *
	 * @api
	 */
	public function setCode($code)
	{
		if (!is_callable($code)) {
			throw new InvalidArgumentException('code is not a valid callback');
		}
			
		$this->code = $code;

		return $this;
	}

	/**
	 * Repeatedly executes the current command.
	 *
	 * This method is not abstract because you can use this class
	 * as a concrete class. In this case, instead of defining the
	 * main() method, you set the code to execute by passing
	 * a Closure to the setCode() method.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract method is not implemented
	 * @see    setCode()
	 */
	public function execute(Console_Input_InputInterface $input, Console_Output_OutputInterface $output)
	{
		do {
			try {
					
				if ($this->code) {
					$statusCode = call_user_func($this->code, $input, $output);
				} else {
					$statusCode = $this->main($input, $output);
				}

			} catch (Exception $e) {
				if (!$this->catchExceptions) {
					throw $e;
				}

				if (null !== $this->getApplication()) {
					$this->getApplication()->renderException($e, $output);
				}

				$statusCode = $e->getCode();
				$statusCode = is_numeric($statusCode) && $statusCode ? $statusCode : 1;
			}
				
		} while ($this->doProceed($statusCode, $input, $output));

		return $statusCode;
	}

	/**
	 * Process whether or not to proceed
	 *
	 * @param integer $statusCode
	 * @param Console_Input_InputInterface $input
	 * @param Console_Output_OutputInterface $output
	 *
	 * @throws Exception
	 *
	 * @return boolean
	 */
	protected function doProceed($statusCode, Console_Input_InputInterface $input, Console_Output_OutputInterface $output)
	{
		try {
			if ($this->getHelper('memory')->limit()) {
				throw new Exception("Memory Limit Reached!");
			}
				
			return (bool) $this->proceed($statusCode, $input, $output);
				
		} catch (Exception $e) {
			if (!$this->catchExceptions) {
				throw $e;
			}
				
			if (null !== $this->getApplication()) {
				$this->getApplication()->renderException($e, $output);
			}
				
			return FALSE;
		}
	}

	/**
	 * Executes the current command.
	 *
	 * This method is not abstract because you can use this class
	 * as a concrete class. In this case, instead of defining the
	 * execute() method, you set the code to execute by passing
	 * a Closure to the setCode() method.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract method is not implemented
	 * @see    setCode()
	 */
	public function main(Console_Input_InputInterface $input, Console_Output_OutputInterface $output)
	{
		throw new LogicException('You must override the main() method in the concrete daemon class.');
	}


	/**
	 * Determines whether to reloop
	 *
	 * @param integer $statusCode
	 * @param Console_Input_InputInterface $input
	 * @param Console_Output_OutputInterface $output
	 *
	 * @return boolean
	 */
	public function proceed($statusCode, Console_Input_InputInterface $input, Console_Output_OutputInterface $output)
	{
		return (bool) $statusCode === 0;
	}
}
