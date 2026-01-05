<?php

namespace Pierstoval\Confman\Command\Git;

use Pierstoval\Confman\App;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Cursor;

#[AsCommand('git:fetch', description: 'Runs the "<info>git fetch --all --prune</>" command on all projects.')]
readonly class GitFetch
{
    public function __construct(
        private App $app,
    ) {
    }

    public function __invoke(Cursor $cursor): int
    {
        $this->app->executeCommandOnAllProjects([
            $this->app->findExecutable('git'),
            'fetch',
            '--all',
            '--prune',
        ], $cursor);

        return 0;
    }

}
