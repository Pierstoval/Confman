<?php

namespace Pierstoval\Confman;

use Pierstoval\Confman\Data\GlobalConfig;
use Pierstoval\Confman\Data\Project;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
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

class App extends Application
{
    private readonly Serializer $serializer;
    private SymfonyStyle $io;

    /** @var string|null The path that is set by the "--config/-c" option at runtime. */
    private ?string $inputConfigPath = null;
    private ?string $currentConfigPath;

    private ?GlobalConfig $currentConfig;


    public function __construct()
    {
        parent::__construct('Confman', APP_VERSION);
        $this->serializer = new Serializer([new ObjectNormalizer(classMetadataFactory: $metadataFactory = new ClassMetadataFactory(new AttributeLoader()), nameConverter: new MetadataAwareNameConverter($metadataFactory), propertyTypeExtractor: new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()])), new ArrayDenormalizer()], [new JsonEncoder(defaultContext: [JsonEncode::OPTIONS => JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES])]);
    }

    public function getInputConfigPath(): ?string
    {
        return $this->inputConfigPath;
    }

    public function getHomePath(): ?string
    {
        if (!empty($_SERVER["HOME"])) {
            return $_SERVER["HOME"];
        }

        if (!empty($_SERVER["USERPROFILE"])) {
            return $_SERVER["USERPROFILE"];
        }

        if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH']) && $windowsPath = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH']) {
            return $windowsPath;
        }

        return null;
    }

    public function getGlobalConfig(?string $path = null): GlobalConfig
    {
        if (isset($this->currentConfig)) {
            return $this->currentConfig;
        }

        $path = $path ?: $this->getProjectsFile();

        $content = file_get_contents($path);
        if (!trim($content)) {
            $content = '{}'; // Makes sure empty files work too.
        }

        return $this->currentConfig = $this->serializer->deserialize(
            data: $content,
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

    public function saveProjects(GlobalConfig $projects): void
    {
        $json = $this->serializer->serialize(
            data: $projects,
            format: 'json',
        );

        file_put_contents($this->currentConfigPath, $json);
    }

    public function getProjectsFile(): string
    {
        if (isset($this->currentConfigPath)) {
            return $this->currentConfigPath;
        }

        $inputPath = $this->inputConfigPath;

        $allowedPaths = $this->getAllowedConfigPaths();

        foreach ($allowedPaths as $p) {
            if (is_file($p)) {
                $this->currentConfigPath = $p;
                $this->io->section(sprintf("Detected config file at <info>%s</>", $p));
                return $p;
            }
        }

        $error = trim(sprintf(
            <<<ERR
            Config manager file was not found.
            Searched in these paths:
            %s
            ERR
            ,
            implode("\n", array_map(static fn (string $path) => sprintf($inputPath ? ' - %s' : ' - %s', $path), $allowedPaths))
        ));

        if (!$inputPath) {
            $error .= "\n\nCreate any of these files to start configuring your setup 🚀";
        }

        throw new \RuntimeException($error);
    }

    public function getAllowedConfigPaths(?string $inputPath = null): array
    {
        $inputPath = $inputPath ?: $this->inputConfigPath;

        $allowedPaths = [];

        if ($inputPath) {
            if (file_exists($inputPath)) {
                $allowedPaths[] = $inputPath;
            }
            if (!str_ends_with($inputPath, '.json')) {
                $allowedPaths[] = getcwd().'/'.rtrim($inputPath, '\\/').'.json';
            }
        }
        if ($inputPath && is_dir($inputPath)) {
            $allowedPaths[] = $inputPath . '/confman.json';
        }

        $homePath = $this->getHomePath();

        if ($inputPath && $homePath && !is_file($inputPath) && !str_contains($inputPath, '/\\')) {
            $testPath = rtrim($homePath, '\\/').'/.config/Confman/'.$inputPath.'.json';
            $allowedPaths[] = $testPath;
        }

        if (!$inputPath) {
            $allowedPaths[] = __DIR__ . '/confman.json';
        }
        if (!$inputPath && ($cwd = getcwd()) !== false && $cwd !== __DIR__) {
            $allowedPaths[] = rtrim($cwd, '\\/') . '/confman.json';
        }
        if (!$inputPath && $homePath) {
            $allowedPaths[] = rtrim($homePath, '\\/').'/.config/Confman/confman.json';
        }
        foreach ($allowedPaths as $k => $path) {
            if (str_starts_with($path, 'phar://')) {
                unset($allowedPaths[$k]);
            }
        }

        return $allowedPaths;
    }

    public function executeCommandOnProject(Project $project, array $command): string
    {
        $process = new Process($command, $project->path);
        $process->run();

        return trim($process->getOutput(), " \n\r\t\v\0'\"");
    }

    public function executeCommandOnAllProjects(array $command, Cursor $cursor, bool $showOutput = false): void {
        $globalConfig = $this->getGlobalConfig();

        $rows = [];
        $processes = [];
        $errors = [];
        $outputs = [];

        $this->io->section(sprintf('Running <info>%s</> on all projects…', implode(' ', $command)));

        $renderRows = fn ($rows) => $this->io->table(['Project', 'Path', 'Status'], $rows);

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
            $process = new Process($command, $project->path);
            $process->start(static function ($type, $data) use (&$errors, &$outputs, $project) {
                if ($type === 'err') {
                    if (!isset($errors[$project->name])) {
                        $errors[$project->name] = '';
                    }

                    $errors[$project->name] .= $data;
                } else {
                    if (!isset($outputs[$project->name])) {
                        $outputs[$project->name] = '';
                    }

                    $outputs[$project->name] .= $data;
                }
            });
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
                } elseif ($process->isTerminated() && $process->getExitCode() !== 0) {
                    $rows[$projectName] = [$projectName, $project->path, sprintf("❌ Error %s %s", $process->getExitCode(), $process->getExitCodeText())];
                    $refresh = true;
                    unset($processes[$projectName]);
                }
                if ($refresh) {
                    $render($rows);
                }
            }
        }

        if (!count($errors)) {
            if ($showOutput) {
                foreach ($outputs as $projectName => $output) {
                    if (!trim($output)) {
                        break;
                    }
                    $this->io->section(sprintf(" ➡️ Output for project %s:", $projectName));
                    $this->io->writeln($output);
                }
            }

            $this->io->success('Done!');
        } else {
            foreach ($errors as $projectName => $output) {
                $messages = [
                    'Error in '.$projectName,
                ];
                if (trim($output)) {
                    $messages[] = ' > '.$output;
                }
                $this->io->error(implode("\n", $messages));
            }
        }
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $def = parent::getDefaultInputDefinition();

        $def->addOption(new InputOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to look for the "confman.json" file. Can be a file or a directory.'));

        return $def;
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if (
            $command->getName() !== 'projects:create'
            && $input->hasOption('config')
            && $input->getOption('config')
        ) {
            $this->inputConfigPath = $input->getOption('config');
            $this->currentConfig = $this->getGlobalConfig();
        }

        return parent::doRunCommand($command, $input, $output);
    }
}
