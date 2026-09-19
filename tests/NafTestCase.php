<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class NafTestCase extends TestCase
{
    protected array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }
}
