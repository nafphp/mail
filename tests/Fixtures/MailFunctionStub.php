<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

$GLOBALS['__naf_mail_return__'] = true;

function mail(string $to, string $subject, string $message, string $headers): bool
{
    $GLOBALS['__naf_last_mail__'] = [
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'headers' => $headers,
    ];

    return $GLOBALS['__naf_mail_return__'] ?? true;
}
