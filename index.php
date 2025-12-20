<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
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
        $this->serializer = new Serializer(
            [
                new ObjectNormalizer(
                    classMetadataFactory: $metadataFactory = new ClassMetadataFactory(new AttributeLoader()),
                    nameConverter: new MetadataAwareNameConverter($metadataFactory),
                    propertyTypeExtractor: new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()])
                ),
                new ArrayDenormalizer()
            ],
            [new JsonEncoder()],
        );
    }
}
$app = new App();
$app->addCommand(new Command('list')->setCode(
    function (
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        Cursor $cursor,
        Application $app,
    ): int {
        /** @var App $app */
        $io->title($app->getName());

        if (!\is_file($file = __DIR__ . '/projects.json')) {
            $io->error('No "projects.json" file found.');
            return 1;
        }

        $projects = $app->serializer->deserialize(
            data: \file_get_contents($file),
            type: Projects::class,
            format: 'json',
        );

        dd($projects->projects);

        return 0;
    }
));

$app->run();

readonly class Projects
{
    public function __construct(
        /** @var array<Project> */
        public array $projects,
    ) {
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
