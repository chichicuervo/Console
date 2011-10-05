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

// namespace Symfony\Component\Console\Formatter;

/**
 * Formatter style class for defining styles.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @api
 */
class Console_Formatter_OutputFormatterStyle implements Console_Formatter_OutputFormatterStyleInterface
{
    static private $availableForegroundColors = array(
        'black'     => 30,
        'red'       => 31,
        'green'     => 32,
        'yellow'    => 33,
        'blue'      => 34,
        'magenta'   => 35,
        'cyan'      => 36,
        'white'     => 37
    );
    static private $availableBackgroundColors = array(
        'black'     => 40,
        'red'       => 41,
        'green'     => 42,
        'yellow'    => 43,
        'blue'      => 44,
        'magenta'   => 45,
        'cyan'      => 46,
        'white'     => 47
    );
    static private $availableOptions = array(
        'bold'          => 1,
        'underscore'    => 4,
        'blink'         => 5,
        'reverse'       => 7,
        'conceal'       => 8
    );

    private $foreground;
    private $background;
    private $options = array();

    /**
     * Initializes output formatter style.
     *
     * @param   string  $foreground     style foreground color name
     * @param   string  $background     style background color name
     * @param   array   $options        style options
     *
     * @api
     */
    public function __construct($foreground = null, $background = null, array $options = array())
    {
        if (null !== $foreground) {
            $this->setForeground($foreground);
        }
        if (null !== $background) {
            $this->setBackground($background);
        }
        if (count($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets style foreground color.
     *
     * @param   string  $color  color name
     *
     * @api
     */
    public function setForeground($color = null)
    {
        if (null === $color) {
            $this->foreground = null;

            return;
        }

        if (!isset(self::$availableForegroundColors[$color])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid foreground color specified: "%s". Expected one of (%s)',
                $color,
                implode(', ', array_keys(self::$availableForegroundColors))
            ));
        }

        $this->foreground = self::$availableForegroundColors[$color];
    }

    /**
     * Sets style background color.
     *
     * @param   string  $color  color name
     *
     * @api
     */
    public function setBackground($color = null)
    {
        if (null === $color) {
            $this->background = null;

            return;
        }

        if (!isset(self::$availableBackgroundColors[$color])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid background color specified: "%s". Expected one of (%s)',
                $color,
                implode(', ', array_keys(self::$availableBackgroundColors))
            ));
        }

        $this->background = self::$availableBackgroundColors[$color];
    }

    /**
     * Sets some specific style option.
     *
     * @param   string  $option     option name
     *
     * @api
     */
    public function setOption($option)
    {
        if (!isset(self::$availableOptions[$option])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(self::$availableOptions))
            ));
        }

        if (false === array_search(self::$availableOptions[$option], $this->options)) {
            $this->options[] = self::$availableOptions[$option];
        }
    }

    /**
     * Unsets some specific style option.
     *
     * @param   string  $option     option name
     */
    public function unsetOption($option)
    {
        if (!isset(self::$availableOptions[$option])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(self::$availableOptions))
            ));
        }

        $pos = array_search(self::$availableOptions[$option], $this->options);
        if (false !== $pos) {
            unset($this->options[$pos]);
        }
    }

    /**
     * Set multiple style options at once.
     *
     * @param   array   $options
     */
    public function setOptions(array $options)
    {
        $this->options = array();

        foreach ($options as $option) {
            $this->setOption($option);
        }
    }

    /**
     * Applies the style to a given text.
     *
     * @param string $text The text to style
     *
     * @return string
     */
    public function apply($text)
    {
        $codes = array();

        if (null !== $this->foreground) {
            $codes[] = $this->foreground;
        }
        if (null !== $this->background) {
            $codes[] = $this->background;
        }
        if (count($this->options)) {
            $codes = array_merge($codes, $this->options);
        }

        return sprintf("\033[%sm%s\033[0m", implode(';', $codes), $text);
    }
}
