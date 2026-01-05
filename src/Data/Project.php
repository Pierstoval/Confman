<?php

namespace Pierstoval\Confman\Data;

readonly class Project
{
    public function __construct(
        public string $name,
        public string $path,
    ) {
    }
}
