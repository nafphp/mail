<?php

declare(strict_types=1);

namespace Naf\Mail\Core;

use Naf\Mail\Models\Mail;

class Mailer
{
    /**
     * @param TransportInterface $transport
     */
    public function __construct(
        private readonly TransportInterface $transport
    ) {
    }

    /**
     * @return Mail
     */
    public function createMail(): Mail
    {
        return new Mail();
    }

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function send(Mail $mail): bool
    {
        return $this->transport->sendMail($mail);
    }
}
