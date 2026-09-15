<?php

declare(strict_types=1);

use Naf\Mail\Core\Mailer;

use function Naf\app;

app()->container()->set(Mailer::class, static fn() => new Mailer());
