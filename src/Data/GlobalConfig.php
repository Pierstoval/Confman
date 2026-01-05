<?php

namespace Pierstoval\Confman\Data;

use Pierstoval\Confman\Data\Project;

readonly class GlobalConfig
{
    public function __construct(
        /** @var array<Project> */
        public array $projects = [],
    ) {
    }

    public function withProject(Project $project): self
    {
        foreach ($this->projects as $existing) {
            if ($existing->name === $project->name) {
                throw new \RuntimeException('Project with same name already exists.');
            }
            if (realpath($existing->path) === realpath($project->path)) {
                throw new \RuntimeException('Project with same path already exists.');
            }
        }
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
