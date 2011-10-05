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

require_once CONSOLE_LIB . "/Formatter/OutputFormatterInterface.php";
require_once CONSOLE_LIB . "/Formatter/OutputFormatterStyleInterface.php";
require_once CONSOLE_LIB . "/Formatter/OutputFormatterStyle.php";

/**
 * Formatter class for console output.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @api
 */
class Console_Formatter_OutputFormatter implements Console_Formatter_OutputFormatterInterface
{
    /**
     * The pattern to phrase the format.
     */
    const FORMAT_PATTERN = '#<([a-z][a-z0-9_=;-]+)>(.*?)</\\1?>#is';

    private $decorated;
    private $styles = array();

    /**
     * Initializes console output formatter.
     *
     * @param   Boolean $decorated  Whether this formatter should actually decorate strings
     * @param   array   $styles     Array of "name => FormatterStyle" instance
     *
     * @api
     */
    public function __construct($decorated = null, array $styles = array())
    {
        $this->decorated = (Boolean) $decorated;

        $this->setStyle('error',    new Console_Formatter_OutputFormatterStyle('white', 'red'));
        $this->setStyle('info',     new Console_Formatter_OutputFormatterStyle('green'));
        $this->setStyle('comment',  new Console_Formatter_OutputFormatterStyle('yellow'));
        $this->setStyle('question', new Console_Formatter_OutputFormatterStyle('black', 'cyan'));

        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }
    }

    /**
     * Sets the decorated flag.
     *
     * @param Boolean $decorated Whether to decorated the messages or not
     *
     * @api
     */
    public function setDecorated($decorated)
    {
        $this->decorated = (Boolean) $decorated;
    }

    /**
     * Gets the decorated flag.
     *
     * @return Boolean true if the output will decorate messages, false otherwise
     *
     * @api
     */
    public function isDecorated()
    {
        return $this->decorated;
    }

    /**
     * Sets a new style.
     *
     * @param string                        $name  The style name
     * @param OutputFormatterStyleInterface $style The style instance
     *
     * @api
     */
    public function setStyle($name, Console_Formatter_OutputFormatterStyleInterface $style)
    {
        $this->styles[strtolower($name)] = $style;
    }

    /**
     * Checks if output formatter has style with specified name.
     *
     * @param   string  $name
     *
     * @return  Boolean
     *
     * @api
     */
    public function hasStyle($name)
    {
        return isset($this->styles[strtolower($name)]);
    }

    /**
     * Gets style options from style with specified name.
     *
     * @param   string  $name
     *
     * @return  OutputFormatterStyleInterface
     *
     * @api
     */
    public function getStyle($name)
    {
        if (!$this->hasStyle($name)) {
            throw new InvalidArgumentException('Undefined style: '.$name);
        }

        return $this->styles[strtolower($name)];
    }

    /**
     * Formats a message according to the given styles.
     *
     * @param  string $message The message to style
     *
     * @return string The styled message
     *
     * @api
     */
    public function format($message)
    {
        return preg_replace_callback(self::FORMAT_PATTERN, array($this, 'replaceStyle'), $message);
    }

    /**
     * Replaces style of the output.
     *
     * @param array $match
     *
     * @return string The replaced style
     */
    private function replaceStyle($match)
    {
        if (!$this->isDecorated()) {
            return $match[2];
        }

        if (isset($this->styles[strtolower($match[1])])) {
            $style = $this->styles[strtolower($match[1])];
        } else {
            $style = $this->createStyleFromString($match[1]);

            if (false === $style) {
                return $match[0];
            }
        }

        return $style->apply($this->format($match[2]));
    }

    /**
     * Tries to create new style instance from string.
     *
     * @param   string  $string
     *
     * @return  Symfony\Component\Console\Format\FormatterStyle|Boolean false if string is not format string
     */
    private function createStyleFromString($string)
    {
        if (!preg_match_all('/([^=]+)=([^;]+)(;|$)/', strtolower($string), $matches, PREG_SET_ORDER)) {
            return false;
        }

        $style = new Console_Formatter_OutputFormatterStyle();
        foreach ($matches as $match) {
            array_shift($match);

            if ('fg' == $match[0]) {
                $style->setForeground($match[1]);
            } elseif ('bg' == $match[0]) {
                $style->setBackground($match[1]);
            } else {
                $style->setOption($match[1]);
            }
        }

        return $style;
    }
}
