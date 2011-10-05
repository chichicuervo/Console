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
	define('CONSOLE_LIB', realpath(dirname(__FILE__) . "/.."));
}

require_once CONSOLE_LIB . "/Output/StreamOutput.php";
require_once CONSOLE_LIB . "/Formatter/OutputFormatter.php";

/**
 * ConsoleOutput is the default class for all CLI output. It uses STDOUT.
 *
 * This class is a convenient wrapper around `StreamOutput`.
 *
 *     $output = new ConsoleOutput();
 *
 * This is equivalent to:
 *
 *     $output = new StreamOutput(fopen('php://stdout', 'w'));
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class Console_Output_ConsoleOutput extends Console_Output_StreamOutput
{
    /**
     * Constructor.
     *
     * @param integer         $verbosity The verbosity level (self::VERBOSITY_QUIET, self::VERBOSITY_NORMAL,
     *                                   self::VERBOSITY_VERBOSE)
     * @param Boolean         $decorated Whether to decorate messages or not (null for auto-guessing)
     * @param OutputFormatter $formatter Output formatter instance
     *
     * @api
     */
    public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null, Console_Formatter_OutputFormatter $formatter = null)
    {
        parent::__construct(fopen('php://stdout', 'w'), $verbosity, $decorated, $formatter);
    }
}
