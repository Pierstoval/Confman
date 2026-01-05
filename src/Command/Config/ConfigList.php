<?php

namespace Pierstoval\Confman\Command\Config;

use Pierstoval\Confman\App;
use Pierstoval\Confman\Data\Project;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'config:list',
    description: 'Lists all configuration files',
    aliases: ['configs'],
)]
readonly class ConfigList
{
    public function __construct(
        private App $app,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $baseConfigFiles = $this->app->getAllowedConfigPaths();

        $io->table(
            ['Name', 'Path', 'Current branch', 'Last commit date', 'Current remote URL'],
            array_map(static function (Project $project) use ($app) {
                $currentBranch = $app->executeCommandOnProject($project, ['git', 'branch', '--show-current']);
                $lastCommitDate = $app->executeCommandOnProject($project, ['git', 'log', '-1', '--oneline', '--format="%ci"']);
                $currentRemote = $app->executeCommandOnProject($project, ['git', 'remote', 'show']);
                $remoteUrl = $app->executeCommandOnProject($project, ['git', 'config', '--get', sprintf('remote.%s.url', $currentRemote)]);

                return [$project->name, $project->path, $currentBranch, $lastCommitDate, $remoteUrl];
            }, $projects->projects),
        );

        return 0;
    }

}
