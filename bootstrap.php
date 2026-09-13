<?php

declare(strict_types=1);

use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\MailTransport;
use function Naf\app;

app()->container()->set(Mailer::class, fn() => new Mailer(new MailTransport()));
