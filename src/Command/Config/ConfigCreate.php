<?php

namespace Pierstoval\Confman\Command\Config;

use Pierstoval\Confman\App;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'config:create',
    description: 'Create a new configuration file',
    aliases: ['create'],
)]
readonly class ConfigCreate
{
    public function __construct(
        private App $app,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $files = $this->app->getAllowedConfigPaths();

        $filename = $io->choice('Where do you want to create the config file?', $files);

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('File "%s" already exists.', $filename));
        }

        $dir = dirname($filename);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('An error occurred when creating "%s" directory.', $dir));
        }

        $defaultContent = <<<JSON
            {
            }
            JSON;

        if (false === file_put_contents($filename, $defaultContent)) {
            throw new \RuntimeException('An error occurred when creating config file.');
        }

        return 0;
    }
}
