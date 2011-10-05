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

// namespace Symfony\Component\Console;

// use Symfony\Component\Console\Input\InputInterface;
// use Symfony\Component\Console\Input\ArgvInput;
// use Symfony\Component\Console\Input\ArrayInput;
// use Symfony\Component\Console\Input\InputDefinition;
// use Symfony\Component\Console\Input\InputOption;
// use Symfony\Component\Console\Input\InputArgument;
// use Symfony\Component\Console\Output\OutputInterface;
// use Symfony\Component\Console\Output\Output;
// use Symfony\Component\Console\Output\ConsoleOutput;
// use Symfony\Component\Console\Command\Command;
// use Symfony\Component\Console\Command\HelpCommand;
// use Symfony\Component\Console\Command\ListCommand;
// use Symfony\Component\Console\Helper\HelperSet;
// use Symfony\Component\Console\Helper\FormatterHelper;
// use Symfony\Component\Console\Helper\DialogHelper;

/**
 * An Application is the container for a collection of commands.
 *
 * It is the main entry point of a Console application.
 *
 * This class is optimized for a standard CLI environment.
 *
 * Usage:
 *
 *     $app = new Application('myapp', '1.0 (stable)');
 *     $app->add(new SimpleCommand());
 *     $app->run();
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class Console_Application
{
    private $commands;
    private $wantHelps = false;
    private $runningCommand;
    private $name;
    private $version;
    private $catchExceptions;
    private $autoExit;
    private $definition;
    private $helperSet;

    /**
     * Constructor.
     *
     * @param string  $name    The name of the application
     * @param string  $version The version of the application
     *
     * @api
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        $this->name = $name;
        $this->version = $version;
        $this->catchExceptions = true;
        $this->autoExit = true;
        $this->commands = array();
        $this->helperSet = new Console_Helper_HelperSet(array(
            new Console_Helper_FormatterHelper(),
            new Console_Helper_DialogHelper(),
        ));

        $this->add(new Console_Command_HelpCommand());
        $this->add(new Console_Command_ListCommand());

        $this->definition = new Console_Input_InputDefinition(array(
            new Console_Input_InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new Console_Input_InputOption('--help',           '-h', Console_Input_InputOption::VALUE_NONE, 'Display this help message.'),
            new Console_Input_InputOption('--quiet',          '-q', Console_Input_InputOption::VALUE_NONE, 'Do not output any message.'),
            new Console_Input_InputOption('--verbose',        '-v', Console_Input_InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
            new Console_Input_InputOption('--version',        '-V', Console_Input_InputOption::VALUE_NONE, 'Display this program version.'),
            new Console_Input_InputOption('--ansi',           '',   Console_Input_InputOption::VALUE_NONE, 'Force ANSI output.'),
            new Console_Input_InputOption('--no-ansi',        '',   Console_Input_InputOption::VALUE_NONE, 'Disable ANSI output.'),
            new Console_Input_InputOption('--no-interaction', '-n', Console_Input_InputOption::VALUE_NONE, 'Do not ask any interactive question.'),
        ));
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \Exception When doRun returns Exception
     *
     * @api
     */
    public function run(Console_Input_InputInterface $input = null, Console_Output_OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new Console_Input_ArgvInput();
        }

        if (null === $output) {
            $output = new Console_Output_ConsoleOutput();
        }

        try {
            $statusCode = $this->doRun($input, $output);
        } catch (Exception $e) {
            if (!$this->catchExceptions) {
                throw $e;
            }

            $this->renderException($e, $output);
            $statusCode = $e->getCode();

            $statusCode = is_numeric($statusCode) && $statusCode ? $statusCode : 1;
        }

        if ($this->autoExit) {
            if ($statusCode > 255) {
                $statusCode = 255;
            }
            // @codeCoverageIgnoreStart
            exit($statusCode);
            // @codeCoverageIgnoreEnd
        }

        return $statusCode;
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(Console_Input_InputInterface $input, Console_Output_OutputInterface $output)
    {
        $name = $this->getCommandName($input);

        if (true === $input->hasParameterOption(array('--ansi'))) {
            $output->setDecorated(true);
        } elseif (true === $input->hasParameterOption(array('--no-ansi'))) {
            $output->setDecorated(false);
        }

        if (true === $input->hasParameterOption(array('--help', '-h'))) {
            if (!$name) {
                $name = 'help';
                $input = new Console_Input_ArrayInput(array('command' => 'help'));
            } else {
                $this->wantHelps = true;
            }
        }

        if (true === $input->hasParameterOption(array('--no-interaction', '-n'))) {
            $input->setInteractive(false);
        }

        if (true === $input->hasParameterOption(array('--quiet', '-q'))) {
            $output->setVerbosity(Console_Output_OutputInterface::VERBOSITY_QUIET);
        } elseif (true === $input->hasParameterOption(array('--verbose', '-v'))) {
            $output->setVerbosity(Console_Output_OutputInterface::VERBOSITY_VERBOSE);
        }

        if (true === $input->hasParameterOption(array('--version', '-V'))) {
            $output->writeln($this->getLongVersion());

            return 0;
        }

        if (!$name) {
            $name = 'list';
            $input = new Console_Input_ArrayInput(array('command' => 'list'));
        }

        // the command name MUST be the first element of the input
        $command = $this->find($name);

        $this->runningCommand = $command;
        $statusCode = $command->run($input, $output);
        $this->runningCommand = null;

        return is_numeric($statusCode) ? $statusCode : 0;
    }

    /**
     * Set a helper set to be used with the command.
     *
     * @param HelperSet $helperSet The helper set
     *
     * @api
     */
    public function setHelperSet(Console_Helper_HelperSet $helperSet)
    {
        $this->helperSet = $helperSet;
    }

    /**
     * Get the helper set associated with the command.
     *
     * @return HelperSet The HelperSet instance associated with this command
     *
     * @api
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * Gets the InputDefinition related to this Application.
     *
     * @return InputDefinition The InputDefinition instance
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Gets the help message.
     *
     * @return string A help message.
     */
    public function getHelp()
    {
        $messages = array(
            $this->getLongVersion(),
            '',
            '<comment>Usage:</comment>',
            sprintf("  [options] command [arguments]\n"),
            '<comment>Options:</comment>',
        );

        foreach ($this->getDefinition()->getOptions() as $option) {
            $messages[] = sprintf('  %-29s %s %s',
                '<info>--'.$option->getName().'</info>',
                $option->getShortcut() ? '<info>-'.$option->getShortcut().'</info>' : '  ',
                $option->getDescription()
            );
        }

        return implode("\n", $messages);
    }

    /**
     * Sets whether to catch exceptions or not during commands execution.
     *
     * @param Boolean $boolean Whether to catch exceptions or not during commands execution
     *
     * @api
     */
    public function setCatchExceptions($boolean)
    {
        $this->catchExceptions = (Boolean) $boolean;
    }

    /**
     * Sets whether to automatically exit after a command execution or not.
     *
     * @param Boolean $boolean Whether to automatically exit after a command execution or not
     *
     * @api
     */
    public function setAutoExit($boolean)
    {
        $this->autoExit = (Boolean) $boolean;
    }

    /**
     * Gets the name of the application.
     *
     * @return string The application name
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the application name.
     *
     * @param string $name The application name
     *
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the application version.
     *
     * @return string The application version
     *
     * @api
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets the application version.
     *
     * @param string $version The application version
     *
     * @api
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Returns the long version of the application.
     *
     * @return string The long application version
     *
     * @api
     */
    public function getLongVersion()
    {
        if ('UNKNOWN' !== $this->getName() && 'UNKNOWN' !== $this->getVersion()) {
            return sprintf('<info>%s</info> version <comment>%s</comment>', $this->getName(), $this->getVersion());
        }

        return '<info>Console Tool</info>';
    }

    /**
     * Registers a new command.
     *
     * @param string $name The command name
     *
     * @return Command The newly created command
     *
     * @api
     */
    public function register($name)
    {
        return $this->add(new Console_Command_Command($name));
    }

    /**
     * Adds an array of command objects.
     *
     * @param Command[] $commands An array of commands
     *
     * @api
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     *
     * @param Command $command A Command object
     *
     * @return Command The registered command
     *
     * @api
     */
    public function add(Console_Command_Command $command)
    {
        $command->setApplication($this);

        $this->commands[$command->getName()] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->commands[$alias] = $command;
        }

        return $command;
    }

    /**
     * Returns a registered command by name or alias.
     *
     * @param string $name The command name or alias
     *
     * @return Command A Command object
     *
     * @throws \InvalidArgumentException When command name given does not exist
     *
     * @api
     */
    public function get($name)
    {
        if (!isset($this->commands[$name])) {
            throw new InvalidArgumentException(sprintf('The command "%s" does not exist.', $name));
        }

        $command = $this->commands[$name];

        if ($this->wantHelps) {
            $this->wantHelps = false;

            $helpCommand = $this->get('help');
            $helpCommand->setCommand($command);

            return $helpCommand;
        }

        return $command;
    }

    /**
     * Returns true if the command exists, false otherwise.
     *
     * @param string $name The command name or alias
     *
     * @return Boolean true if the command exists, false otherwise
     *
     * @api
     */
    public function has($name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * Returns an array of all unique namespaces used by currently registered commands.
     *
     * It does not returns the global namespace which always exists.
     *
     * @return array An array of namespaces
     */
    public function getNamespaces()
    {
        $namespaces = array();
        foreach ($this->commands as $command) {
            $namespaces[] = $this->extractNamespace($command->getName());

            foreach ($command->getAliases() as $alias) {
                $namespaces[] = $this->extractNamespace($alias);
            }
        }

        return array_values(array_unique(array_filter($namespaces)));
    }

    /**
     * Finds a registered namespace by a name or an abbreviation.
     *
     * @param string $namespace A namespace or abbreviation to search for
     *
     * @return string A registered namespace
     *
     * @throws \InvalidArgumentException When namespace is incorrect or ambiguous
     */
    public function findNamespace($namespace)
    {
        $allNamespaces = array();
        foreach ($this->getNamespaces() as $n) {
            $allNamespaces[$n] = explode(':', $n);
        }

        $found = array();
        foreach (explode(':', $namespace) as $this->_i => $part) {
            
//         	$abbrevs = self::getAbbreviations(array_unique(array_values(array_filter(array_map(function ($p) use ($i) { return isset($p[$i]) ? $p[$i] : ''; }, $allNamespaces)))));
        	$abbrevs = self::getAbbreviations(array_unique(array_values(array_filter(array_map(array($this, 'fnsCb'), $allNamespaces)))));
        	 
            if (!isset($abbrevs[$part])) {
                throw new InvalidArgumentException(sprintf('There are no commands defined in the "%s" namespace.', $namespace));
            }

            if (count($abbrevs[$part]) > 1) {
                throw new InvalidArgumentException(sprintf('The namespace "%s" is ambiguous (%s).', $namespace, $this->getAbbreviationSuggestions($abbrevs[$namespace])));
            }

            $found[] = $abbrevs[$part][0];
        }

        return implode(':', $found);
    }

    private $_i = 0;
    
    private function fnsCb(array $p)
    {
    	return isset($p[$this->_i]) ? $p[$this->_i] : '';
    }
    
    
    /**
     * Finds a command by name or alias.
     *
     * Contrary to get, this command tries to find the best
     * match if you give it an abbreviation of a name or alias.
     *
     * @param  string $name A command name or a command alias
     *
     * @return Command A Command instance
     *
     * @throws \InvalidArgumentException When command name is incorrect or ambiguous
     *
     * @api
     */
    public function find($name)
    {
        // namespace
        $namespace = '';
        $searchName = $name;
        if (false !== $pos = strrpos($name, ':')) {
            $namespace = $this->findNamespace(substr($name, 0, $pos));
            $searchName = $namespace.substr($name, $pos);
        }

        // name
        $commands = array();
        foreach ($this->commands as $command) {
            if ($this->extractNamespace($command->getName()) == $namespace) {
                $commands[] = $command->getName();
            }
        }

        $abbrevs = self::getAbbreviations(array_unique($commands));
        if (isset($abbrevs[$searchName]) && 1 == count($abbrevs[$searchName])) {
            return $this->get($abbrevs[$searchName][0]);
        }

        if (isset($abbrevs[$searchName]) && count($abbrevs[$searchName]) > 1) {
            $suggestions = $this->getAbbreviationSuggestions($abbrevs[$searchName]);

            throw new InvalidArgumentException(sprintf('Command "%s" is ambiguous (%s).', $name, $suggestions));
        }

        // aliases
        $aliases = array();
        foreach ($this->commands as $command) {
            foreach ($command->getAliases() as $alias) {
                if ($this->extractNamespace($alias) == $namespace) {
                    $aliases[] = $alias;
                }
            }
        }

        $abbrevs = self::getAbbreviations(array_unique($aliases));
        if (!isset($abbrevs[$searchName])) {
            throw new InvalidArgumentException(sprintf('Command "%s" is not defined.', $name));
        }

        if (count($abbrevs[$searchName]) > 1) {
            throw new InvalidArgumentException(sprintf('Command "%s" is ambiguous (%s).', $name, $this->getAbbreviationSuggestions($abbrevs[$searchName])));
        }

        return $this->get($abbrevs[$searchName][0]);
    }

    /**
     * Gets the commands (registered in the given namespace if provided).
     *
     * The array keys are the full names and the values the command instances.
     *
     * @param  string  $namespace A namespace name
     *
     * @return array An array of Command instances
     *
     * @api
     */
    public function all($namespace = null)
    {
        if (null === $namespace) {
            return $this->commands;
        }

        $commands = array();
        foreach ($this->commands as $name => $command) {
            if ($namespace === $this->extractNamespace($name, substr_count($namespace, ':') + 1)) {
                $commands[$name] = $command;
            }
        }

        return $commands;
    }

    /**
     * Returns an array of possible abbreviations given a set of names.
     *
     * @param array $names An array of names
     *
     * @return array An array of abbreviations
     */
    static public function getAbbreviations($names)
    {
        $abbrevs = array();
        foreach ($names as $name) {
            for ($len = strlen($name) - 1; $len > 0; --$len) {
                $abbrev = substr($name, 0, $len);
                if (!isset($abbrevs[$abbrev])) {
                    $abbrevs[$abbrev] = array($name);
                } else {
                    $abbrevs[$abbrev][] = $name;
                }
            }
        }

        // Non-abbreviations always get entered, even if they aren't unique
        foreach ($names as $name) {
            $abbrevs[$name] = array($name);
        }

        return $abbrevs;
    }

    /**
     * Returns a text representation of the Application.
     *
     * @param string $namespace An optional namespace name
     *
     * @return string A string representing the Application
     */
    public function asText($namespace = null)
    {
        $commands = $namespace ? $this->all($this->findNamespace($namespace)) : $this->commands;

        $messages = array($this->getHelp(), '');
        if ($namespace) {
            $messages[] = sprintf("<comment>Available commands for the \"%s\" namespace:</comment>", $namespace);
        } else {
            $messages[] = '<comment>Available commands:</comment>';
        }

        $width = 0;
        foreach ($commands as $command) {
            $width = strlen($command->getName()) > $width ? strlen($command->getName()) : $width;
        }
        $width += 2;

        // add commands by namespace
        foreach ($this->sortCommands($commands) as $space => $commands) {
            if (!$namespace && '_global' !== $space) {
                $messages[] = '<comment>'.$space.'</comment>';
            }

            foreach ($commands as $name => $command) {
                $messages[] = sprintf("  <info>%-${width}s</info> %s", $name, $command->getDescription());
            }
        }

        return implode("\n", $messages);
    }

    /**
     * Returns an XML representation of the Application.
     *
     * @param string  $namespace An optional namespace name
     * @param Boolean $asDom     Whether to return a DOM or an XML string
     *
     * @return string|DOMDocument An XML string representing the Application
     */
    public function asXml($namespace = null, $asDom = false)
    {
        $commands = $namespace ? $this->all($this->findNamespace($namespace)) : $this->commands;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->appendChild($xml = $dom->createElement('symfony'));

        $xml->appendChild($commandsXML = $dom->createElement('commands'));

        if ($namespace) {
            $commandsXML->setAttribute('namespace', $namespace);
        } else {
            $namespacesXML = $dom->createElement('namespaces');
            $xml->appendChild($namespacesXML);
        }

        // add commands by namespace
        foreach ($this->sortCommands($commands) as $space => $commands) {
            if (!$namespace) {
                $namespaceArrayXML = $dom->createElement('namespace');
                $namespacesXML->appendChild($namespaceArrayXML);
                $namespaceArrayXML->setAttribute('id', $space);
            }

            foreach ($commands as $name => $command) {
                if ($name !== $command->getName()) {
                    continue;
                }

                if (!$namespace) {
                    $commandXML = $dom->createElement('command');
                    $namespaceArrayXML->appendChild($commandXML);
                    $commandXML->appendChild($dom->createTextNode($name));
                }

                $node = $command->asXml(true)->getElementsByTagName('command')->item(0);
                $node = $dom->importNode($node, true);

                $commandsXML->appendChild($node);
            }
        }

        return $asDom ? $dom : $dom->saveXml();
    }

    /**
     * Renders a catched exception.
     *
     * @param Exception       $e      An exception instance
     * @param OutputInterface $output An OutputInterface instance
     */
    public function renderException($e, $output)
    {
    	
        do {
            $title = sprintf('  [%s]  ', get_class($e));
            $len = $this->strlen($title);
            $lines = array();
            foreach (explode("\n", $e->getMessage()) as $line) {
                $lines[] = sprintf('  %s  ', $line);
                $len = max($this->strlen($line) + 4, $len);
            }

            $messages = array(str_repeat(' ', $len), $title.str_repeat(' ', $len - $this->strlen($title)));

            foreach ($lines as $line) {
                $messages[] = $line.str_repeat(' ', $len - $this->strlen($line));
            }

            $messages[] = str_repeat(' ', $len);

            $output->writeln("\n");
            foreach ($messages as $message) {
                $output->writeln('<error>'.$message.'</error>');
            }
            $output->writeln("\n");

            if (Console_Output_OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
                $output->writeln('<comment>Exception trace:</comment>');

                // exception related properties
                $trace = $e->getTrace();
                array_unshift($trace, array(
                    'function' => '',
                    'file'     => $e->getFile() != null ? $e->getFile() : 'n/a',
                    'line'     => $e->getLine() != null ? $e->getLine() : 'n/a',
                    'args'     => array(),
                ));

                for ($i = 0, $count = count($trace); $i < $count; $i++) {
                    $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
                    $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
                    $function = $trace[$i]['function'];
                    $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                    $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

                    $output->writeln(sprintf(' %s%s%s() at <info>%s:%s</info>', $class, $type, $function, $file, $line));
                }

                $output->writeln("\n");
            }
        } while ($e = $e->getPrevious());

        if (null !== $this->runningCommand) {
            $output->writeln(sprintf('<info>%s</info>', sprintf($this->runningCommand->getSynopsis(), $this->getName())));
            $output->writeln("\n");
        }
    }

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(Console_Input_InputInterface $input)
    {
        return $input->getFirstArgument('command');
    }

    /**
     * Sorts commands in alphabetical order.
     *
     * @param array $commands An associative array of commands to sort
     *
     * @return array A sorted array of commands
     */
    private function sortCommands($commands)
    {
        $namespacedCommands = array();
        foreach ($commands as $name => $command) {
            $key = $this->extractNamespace($name, 1);
            if (!$key) {
                $key = '_global';
            }

            $namespacedCommands[$key][$name] = $command;
        }
        ksort($namespacedCommands);

        foreach ($namespacedCommands as &$commands) {
            ksort($commands);
        }

        return $namespacedCommands;
    }

    /**
     * Returns abbreviated suggestions in string format.
     *
     * @param array $abbrevs Abbreviated suggestions to convert
     *
     * @return string A formatted string of abbreviated suggestions
     */
    private function getAbbreviationSuggestions($abbrevs)
    {
        return sprintf('%s, %s%s', $abbrevs[0], $abbrevs[1], count($abbrevs) > 2 ? sprintf(' and %d more', count($abbrevs) - 2) : '');
    }

    private function extractNamespace($name, $limit = null)
    {
        $parts = explode(':', $name);
        array_pop($parts);

        return implode(':', null === $limit ? $parts : array_slice($parts, 0, $limit));
    }
    
    private function strlen($string)
    {
    	if (!function_exists('mb_strlen')) {
    		return strlen($string);
    	}
    	
    	if (false === $encoding = mb_detect_encoding($string)) {
    		return strlen($string);
    	}
    	
    	return mb_strlen($string, $encoding);
    }
}
