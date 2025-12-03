<?php

declare(strict_types=1);

namespace NixPHP\Mail\Core;

use NixPHP\Mail\Models\Mail;

interface TransportInterface
{

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function sendMail(Mail $mail): bool;

}