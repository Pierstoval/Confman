<?php

namespace Pierstoval\Confman\Command\Project;

use Pierstoval\Confman\App;
use Pierstoval\Confman\Data\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'projects:list',
    description: 'Lists all currently configured projects',
    aliases: ['projects'],
)]
readonly class ProjectsList
{
    public function __construct(
        private App $app,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $app = $this->app;

        $projects = $app->getGlobalConfig();

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
