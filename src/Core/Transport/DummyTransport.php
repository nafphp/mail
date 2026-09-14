<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Models\Mail;

/**
 * Captures messages in memory without delivering them.
 */
final class DummyTransport implements TransportInterface
{
    /** @var list<Mail> */
    private array $messages = [];

    public function sendMail(Mail $mail): bool
    {
        $this->messages[] = clone $mail;

        return true;
    }

    /** @return list<Mail> Messages captured in send order. */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Discard captures before another test or worker job.
     */
    public function clear(): void
    {
        $this->messages = [];
    }
}
