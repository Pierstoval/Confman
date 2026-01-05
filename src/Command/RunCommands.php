<?php

namespace Pierstoval\Confman\Command;

use Pierstoval\Confman\App;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Cursor;

#[AsCommand(
    name: 'projects:command:all',
    description: 'Run a command on all projects',
    aliases: ['command', 'commands'],
    help: <<<HELP
    You should add "<info>--</>" before your command to make sure options do not create conflict.

    For example, this command might return an error:

    > <info>confman projects:command:all git fetch --all --prune</>

    The reason is that the "--all" and "--prune" are options, and they will automatically be interpreted
    as options for the <comment>main command</>, not the one you delegate to projects.

    To fix this, you must run the command this way:

    > <info>confman projects:command:all -- git fetch --all --prune</>

    You can use the "<comment>--show-output</>" option to display the output of all commands after the execution:

    > <info>confman projects:command:all --show-status -- git status</>
    
    Or with its "<comment>-s</>" short version:

    > <info>confman projects:command:all -s -- git status</>

    HELP
)]
readonly class RunCommands
{
    public function __construct(
        private App $app,
    ) {
    }

    public function __invoke(
        Cursor $cursor,
        #[Argument()] array $arguments,
        #[Option(
            description: 'Display command output for each execution after running all commands',
            name: 'show-output',
            shortcut: 's',
        )]
        bool $showOutput = false,
    ): int
    {
        $command = $arguments;

        if (!is_file($command[0])) {
            $command[0] = $this->app->findExecutable($command[0]);
        }

        $this->app->executeCommandOnAllProjects($command, $cursor, $showOutput);

        return 0;
    }
}
