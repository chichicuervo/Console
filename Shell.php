<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Backported for php5.2 by Jason Belich <jason@belich.com>
 * 
 */

if (!defined('CONSOLE_LIB')) {
	define('CONSOLE_LIB', dirname(__FILE__));
}

require_once CONSOLE_LIB . "/Application.php";
require_once CONSOLE_LIB . "/Input/StringInput.php";
require_once CONSOLE_LIB . "/Output/ConsoleOutput.php";

/**
 * A Shell wraps an Application to add shell capabilities to it.
 *
 * This class only works with a PHP compiled with readline support
 * (either --with-readline or --with-libedit)
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Console_Shell
{
    private $application;
    private $history;
    private $output;

    /**
     * Constructor.
     *
     * If there is no readline support for the current PHP executable
     * a \RuntimeException exception is thrown.
     *
     * @param Application $application An application instance
     *
     * @throws \RuntimeException When Readline extension is not enabled
     */
    public function __construct(Console_Application $application)
    {
        if (!function_exists('readline')) {
            throw new RuntimeException('Unable to start the shell as the Readline extension is not enabled.');
        }

        $this->application = $application;
        $this->history = getenv('HOME').'/.history_'.$application->getName();
        $this->output = new Console_Output_ConsoleOutput();
    }

    /**
     * Runs the shell.
     */
    public function run()
    {
        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions(true);

        readline_read_history($this->history);
        readline_completion_function(array($this, 'autocompleter'));

        $this->output->writeln($this->getHeader());
        while (true) {
            $command = readline($this->application->getName().' > ');

            if (false === $command) {
                $this->output->writeln("\n");

                break;
            }

            readline_add_history($command);
            readline_write_history($this->history);

            if (0 !== $ret = $this->application->run(new Console_Input_StringInput($command), $this->output)) {
                $this->output->writeln(sprintf('<error>The command terminated with an error status (%s)</error>', $ret));
            }
        }
    }

    /**
     * Tries to return autocompletion for the current entered text.
     *
     * @param string  $text     The last segment of the entered text
     * @param integer $position The current position
     */
    private function autocompleter($text, $position)
    {
        $info = readline_info();
        $text = substr($info['line_buffer'], 0, $info['end']);

        if ($info['point'] !== $info['end']) {
            return true;
        }

        // task name?
        if (false === strpos($text, ' ') || !$text) {
            return array_keys($this->application->all());
        }

        // options and arguments?
        try {
            $command = $this->application->find(substr($text, 0, strpos($text, ' ')));
        } catch (Exception $e) {
            return true;
        }

        $list = array('--help');
        foreach ($command->getDefinition()->getOptions() as $option) {
            $list[] = '--'.$option->getName();
        }

        return $list;
    }

    /**
     * Returns the shell header.
     *
     * @return string The header string
     */
    protected function getHeader()
    {
        return <<<EOF

Welcome to the <info>{$this->application->getName()}</info> shell (<comment>{$this->application->getVersion()}</comment>).

At the prompt, type <comment>help</comment> for some help,
or <comment>list</comment> to get a list available commands.

To exit the shell, type <comment>^D</comment>.

EOF;
    }
}
