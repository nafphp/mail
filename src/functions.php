<?php

declare(strict_types=1);

namespace NixPHP\Mail;

use NixPHP\Mail\Core\Mailer;
use NixPHP\Mail\Models\Mail;
use function NixPHP\app;

function mailer(): Mailer
{
    return app()->container()->get(Mailer::class);
}

function mail(): Mail
{
    return mailer()->createMail();
}