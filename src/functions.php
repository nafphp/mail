<?php

declare(strict_types=1);

namespace NixPHP\Mail;

use NixPHP\Mail\Core\Mailer;
use NixPHP\Mail\Models\Mail;
use function NixPHP\app;

/**
 * @return Mailer
 */
function mailer(): Mailer
{
    return app()->container()->get(Mailer::class);
}

/**
 * @return Mail
 */
function mail(): Mail
{
    return mailer()->createMail();
}