<?php

declare(strict_types=1);

namespace NixPHP\Mail\Core\Transport;

$GLOBALS['__nixphp_mail_return__'] = true;

function mail(string $to, string $subject, string $message, string $headers): bool
{
    $GLOBALS['__nixphp_last_mail__'] = [
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'headers' => $headers,
    ];

    return $GLOBALS['__nixphp_mail_return__'] ?? true;
}
