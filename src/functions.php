<?php

declare(strict_types=1);

namespace Naf\Mail;

use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Models\Mail;

use function Naf\app;

/**
 * Use a separate mailer for an explicit transport, or retrieve the shared mailer.
 */
function mailer(?TransportInterface $transport = null): Mailer
{
    if ($transport !== null) {
        return new Mailer($transport);
    }

    return app()->container()->get(Mailer::class);
}

/**
 * @return Mail
 */
function mail(): Mail
{
    return mailer()->createMail();
}
