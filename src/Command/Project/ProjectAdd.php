<?php

namespace Pierstoval\Confman\Command\Project;

use Pierstoval\Confman\App;
use Pierstoval\Confman\Data\Project;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'projects:add',
    description: 'Add a new project to configuration',
    aliases: ['add'],
)]
readonly class ProjectAdd
{
    public function __construct(
        private App $app,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument()] ?string $name,
        #[Argument()] ?string $path
    ): int
    {
        $app = $this->app;

        $git = $app->findExecutable('git');

        $name = $name ?: $io->ask('Project name?');
        $argPath = $path;
        $argPathValidated = false;
        do {
            $path = $argPath && !$argPathValidated ? $argPath : $io->ask('Path?');
            $argPathValidated = true;

            if (!$path) {
                $io->error('Please specify a directory.');
                continue;
            }

            if (isset($_SERVER['HOME']) && str_contains($path, '~')) {
                $path = str_replace('~', $_SERVER['HOME'], $path);
            }

            if (!is_dir($path)) {
                $io->error('Specified path is not a valid directory.');
                $path = null;
                continue;
            }

            $process = new Process([$git, 'status', '--porcelain'], $path);
            $exitCode = $process->run();
            if ($exitCode !== 0) {
                $io->error(sprintf('Directory %sdoes not seem to be a valid git repository.', $path));
                $path = null;
            }
        } while (!$path);

        $globalConfig = $app->getGlobalConfig();

        $globalConfig = $globalConfig->withProject(new Project($name, $path));

        $app->saveProjects($globalConfig);

        $io->success('Done!');

        return 0;
    }
}
