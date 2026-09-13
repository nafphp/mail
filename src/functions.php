<?php

declare(strict_types=1);

namespace Naf\Mail;

use Naf\Mail\Core\Mailer;
use Naf\Mail\Models\Mail;
use function Naf\app;

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