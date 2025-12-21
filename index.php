<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

require __DIR__.'/vendor/autoload.php';

class App extends Application {
    public readonly Serializer $serializer;

    public function __construct()
    {
        parent::__construct('Global config', '0.1.0');
        $this->serializer = new Serializer([new ObjectNormalizer(classMetadataFactory: $metadataFactory = new ClassMetadataFactory(new AttributeLoader()), nameConverter: new MetadataAwareNameConverter($metadataFactory), propertyTypeExtractor: new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()])), new ArrayDenormalizer()], [new JsonEncoder(defaultContext: [JsonEncode::OPTIONS => JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES])]);
    }

    public function getGlobalConfig(?string $path = null): GlobalConfig
    {
        $path = $this->getProjectsFile($path);

        return $this->serializer->deserialize(
            data: file_get_contents($path),
            type: GlobalConfig::class,
            format: 'json',
        );
    }

    public function findExecutable(string $command): string
    {
        static $finder;
        if (!$finder) {
            $finder = new ExecutableFinder();
        }
        $executable = $finder->find($command);
        if (!$executable) {
            throw new \RuntimeException(sprintf('Could not find "%s" command. Did you install it in your system?', $command));
        }
        return $executable;
    }

    public function saveProjects(GlobalConfig $projects, ?string $path = null): void
    {
        $json = $this->serializer->serialize(
            data: $projects,
            format: 'json',
        );

        $path = $this->getProjectsFile($path, false);

        file_put_contents($path, $json);
    }

    public function executeCommandOnAllProjects(array $command, SymfonyStyle $io, Cursor $cursor): void {
        $globalConfig = $this->getGlobalConfig();

        $rows = [];
        $processes = [];

        $io->section(sprintf('Running <info>%s</> on all projects…', implode(' ', $command)));

        $renderRows = static function ($rows) use ($io) {
            $io->table(['Project', 'Path', 'Status'], $rows);
        };

        $render = static function($rows) use ($cursor, $renderRows) {
            foreach ($rows as $row) {
                $cursor->moveUp();
            }
            $cursor->moveUp(5); // Last line, headers, and table separators
            $cursor->moveToColumn(0);
            $renderRows($rows);
        };

        foreach ($globalConfig->projects as $project) {
            $rows[$project->name] = [$project->name, $project->path, '-'];
        }
        $renderRows($rows);

        foreach ($globalConfig->projects as $project) {
            $rows[$project->name] = [$project->name, $project->path, 'Command started…'];
            $process = new Symfony\Component\Process\Process($command, $project->path);
            $process->start();
            $processes[$project->name] = $process;
        }
        $render($rows);

        while (\count($processes) > 0) {
            foreach ($processes as $projectName => $process) {
                $project = $globalConfig->getProject($projectName);
                $refresh = false;
                if ($process->isSuccessful()) {
                    $rows[$projectName] = [$projectName, $project->path, str_pad('✅', 17, ' ', STR_PAD_BOTH)];
                    unset($processes[$projectName]);
                    $refresh = true;
                } elseif ($process->isTerminated()) {
                    $rows[$projectName] = [$projectName, $project->path, sprintf("❌ Error %s %s", $process->getExitCode(), $process->getExitCodeText())];
                    $refresh = true;
                }
                if ($refresh) {
                    $render($rows);
                }
            }
        }

        $io->success('Done!');
    }

    private function getProjectsFile(?string $path, bool $checkExists = true): string
    {
        if  (!$path) {
            $path = __DIR__ . '/projects.json';
        }
        if ($checkExists && !is_file($path)) {
            throw new \RuntimeException('No "projects.json" file found.');
        }

        return $path;
    }
}
$app = new App();
$app->addCommand(new Command('projects:list')->setCode(function (SymfonyStyle $io, Application $app): int {
    /** @var App $app */

    $projects = $app->getGlobalConfig();

    $io->table(
        ['Name', 'Path'],
        array_map(static fn (Project $project) => [$project->name, $project->path], $projects->projects),
    );

    return Command::SUCCESS;
}));
$app->addCommand(new Command('projects:add')->setCode(function (SymfonyStyle $io, Application $app): int {
    /** @var App $app */

    $git = $app->findExecutable('git');

    $name = $io->ask('Project name?');
    do {
        $path = $io->ask('Path?');
        if (!$path) {
            $io->warning('Please specify a directory.');
        } elseif (!is_dir($path)) {
            $io->warning('Specified path is not a valid directory.');
            $path = null;
        } else {
            $process = new Symfony\Component\Process\Process([$git, 'status', '--porcelain'], $path);
            $exitCode = $process->run();
            if ($exitCode !== 0) {
                $io->warning(sprintf('Directory %sdoes not seem to be a valid git repository.', $path));
                $path = null;
            }
        }
    } while (!$path);

    $globalConfig = $app->getGlobalConfig();

    $globalConfig = $globalConfig->withProject(new Project($name, $path));

    $app->saveProjects($globalConfig);

    return Command::SUCCESS;
}));
$app->addCommand(new Command('projects:git:fetch')->setCode(function (SymfonyStyle $io, Cursor $cursor, Application $app): int {
    /** @var App $app */

    $app->executeCommandOnAllProjects([
        $app->findExecutable('git'),
        'fetch',
        '--all',
        '--prune',
    ], $io, $cursor);

    return Command::SUCCESS;
}));
$app->addCommand(new Command('projects:command:all')
    ->addArgument('arguments', InputArgument::IS_ARRAY)
    ->setDescription('Run a command on all projects. You should add "--" before your command to make sure options do not create conflict')
    ->setCode(function (InputInterface $input, SymfonyStyle $io, Cursor $cursor, Application $app): int {
    /** @var App $app */

    $command = $input->getArgument('arguments');

    if (!is_file($command[0])) {
        $command[0] = $app->findExecutable($command[0]);
    }

    $app->executeCommandOnAllProjects($command, $io, $cursor);

    return Command::SUCCESS;
}));

$app->run();

readonly class GlobalConfig
{
    public function __construct(
        /** @var array<Project> */
        public array $projects,
    ) {
    }

    public function withProject(Project $project): self
    {
        $projects = $this->projects;
        $projects[] = $project;

        return new self($projects);
    }

    public function getProject(int|string $projectName): Project
    {
        foreach ($this->projects as $project) {
            if ($projectName === $project->name) {
                return $project;
            }
        }
        throw new \RuntimeException(sprintf('Could not find project "%s".', $projectName));
    }
}

readonly class Project
{
    public function __construct(
        public string $name,
        public string $path,
    ) {
    }
}
