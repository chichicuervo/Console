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

require_once CONSOLE_LIB . "/Input/InputArgument.php";
require_once CONSOLE_LIB . "/Input/InputOption.php";
require_once CONSOLE_LIB . "/Input/InputInterface.php";
require_once CONSOLE_LIB . "/Output/OutputInterface.php";
require_once CONSOLE_LIB . "/Command/Command.php";

/**
 * HelpCommand displays the help for a given command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Console_Command_HelpCommand extends Console_Command_Command
{
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors = true;

        $this
            ->setDefinition(array(
                new Console_Input_InputArgument('command_name', Console_Input_InputArgument::OPTIONAL, 'The command name', 'help'),
                new Console_Input_InputOption('xml', null, Console_Input_InputOption::VALUE_NONE, 'To output help as XML'),
            ))
            ->setName('help')
            ->setDescription('Displays help for a command')
            ->setHelp(<<<EOF
The <info>help</info> command displays help for a given command:

  <info>php app/console help list</info>

You can also output the help as XML by using the <comment>--xml</comment> option:

  <info>php app/console help --xml list</info>
EOF
            );
    }

    /**
     * Sets the command
     *
     * @param Command $command The command to set
     */
    public function setCommand(Console_Command_Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console_Input_InputInterface $input, Console_Output_OutputInterface $output)
    {
        if (null === $this->command) {
            $this->command = $this->getApplication()->get($input->getArgument('command_name'));
        }

        if ($input->getOption('xml')) {
            $output->writeln($this->command->asXml(), Console_Output_OutputInterface::OUTPUT_RAW);
        } else {
            $output->writeln($this->command->asText());
        }
    }
}
