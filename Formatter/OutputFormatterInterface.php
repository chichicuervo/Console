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

require_once CONSOLE_LIB . "/Output/OutputFormatterStyleInterface.php";

/**
 * Formatter interface for console output.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @api
 */
interface Console_Formatter_OutputFormatterInterface
{
    /**
     * Sets the decorated flag.
     *
     * @param Boolean $decorated Whether to decorated the messages or not
     *
     * @api
     */
    function setDecorated($decorated);

    /**
     * Gets the decorated flag.
     *
     * @return Boolean true if the output will decorate messages, false otherwise
     *
     * @api
     */
    function isDecorated();

    /**
     * Sets a new style.
     *
     * @param string                        $name  The style name
     * @param OutputFormatterStyleInterface $style The style instance
     *
     * @api
     */
    function setStyle($name, Console_Formatter_OutputFormatterStyleInterface $style);

    /**
     * Checks if output formatter has style with specified name.
     *
     * @param   string  $name
     *
     * @return  Boolean
     *
     * @api
     */
    function hasStyle($name);

    /**
     * Gets style options from style with specified name.
     *
     * @param   string  $name
     *
     * @return  OutputFormatterStyleInterface
     *
     * @api
     */
    function getStyle($name);

    /**
     * Formats a message according to the given styles.
     *
     * @param  string $message The message to style
     *
     * @return string The styled message
     *
     * @api
     */
    function format($message);
}
