<?php

declare(strict_types=1);

namespace Fixtures;

use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Models\Mail;

final class ScalarTransport implements TransportInterface
{
    public function __construct(public readonly string $endpoint)
    {
    }

    public function sendMail(Mail $mail): bool
    {
        return true;
    }
}
