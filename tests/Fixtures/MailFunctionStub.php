<?php

declare(strict_types=1);

namespace NixPHP\Mail\Core\Transport; {

    /**
     * Steuerbare Rückgabe für den mail()-Stub.
     * Standard: true (Mail erfolgreich).
     */
    $GLOBALS['__nixphp_mail_return__'] = true;

    /**
     * Stub für die globale mail()-Funktion im Namespace des Transports.
     * Dadurch wird im Test NICHT wirklich gemailt.
     */
    function mail(string $to, string $subject, string $message, string $headers): bool
    {
        // Zuletzt aufgerufene Parameter für Assertions speichern
        $GLOBALS['__nixphp_last_mail__'] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];

        return $GLOBALS['__nixphp_mail_return__'] ?? true;
    }

}
