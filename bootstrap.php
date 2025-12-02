<?php

declare(strict_types=1);

use NixPHP\Mail\Core\Mailer;
use NixPHP\Mail\Core\Transport\MailTransport;
use function NixPHP\app;

app()->container()->set(Mailer::class, fn() => new Mailer(new MailTransport()));
