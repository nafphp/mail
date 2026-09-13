<?php

declare(strict_types=1);

namespace Naf\Mail\Core;

use Naf\Mail\Models\Mail;

interface TransportInterface
{

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function sendMail(Mail $mail): bool;

}