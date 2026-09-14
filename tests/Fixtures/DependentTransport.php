<?php

declare(strict_types=1);

namespace Fixtures;

use Naf\Mail\Core\Transport\DummyTransport;
use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Models\Mail;

final class DependentTransport implements TransportInterface
{
    public function __construct(private readonly DummyTransport $capture)
    {
    }

    public function sendMail(Mail $mail): bool
    {
        return $this->capture->sendMail($mail);
    }
}
