<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

/** Declarative provider fields, independent of a particular form renderer. */
final class TransportConfiguration
{
    public static function fields(string $transport): array
    {
        if (is_subclass_of($transport, ConfigurableTransportInterface::class)) {
            return $transport::configurationFields();
        }
        if ($transport !== __NAMESPACE__ . '\\SmtpTransport') {
            return [];
        }

        return self::smtpFields();
    }

    public static function smtpFields(): array
    {
        $fields = [
            'mail:smtp:host'           => ['label' => 'SMTP-Server', 'type' => 'text', 'default' => ''],
            'mail:smtp:port'           => ['label' => 'SMTP-Port', 'type' => 'integer', 'default' => null, 'options' => ['min' => 1, 'max' => 65535]],
            'mail:smtp:security'       => ['label' => 'SMTP-Verschlüsselung', 'type' => 'select', 'default' => 'auto', 'options' => ['choices' => ['auto' => 'Automatisch', 'none' => 'Keine', 'starttls' => 'STARTTLS', 'tls' => 'TLS']]],
            'mail:smtp:authentication' => ['label' => 'SMTP-Anmeldung', 'type' => 'select', 'default' => 'auto', 'options' => ['choices' => ['auto' => 'Automatisch', 'off' => 'Aus', 'on' => 'An', 'login' => 'LOGIN', 'plain' => 'PLAIN', 'cram-md5' => 'CRAM-MD5', 'xoauth2' => 'XOAUTH2', 'oauth' => 'OAuth']]],
            'mail:smtp:username'       => ['label' => 'SMTP-Benutzername', 'type' => 'text', 'default' => ''],
            'mail:smtp:password'       => ['label' => 'SMTP-Passwort', 'sensitive' => true, 'default' => ''],
            'mail:smtp:timeout'        => ['label' => 'SMTP-Zeitlimit (Sekunden)', 'type' => 'integer', 'default' => 15, 'options' => ['min' => 1, 'max' => 300]],
            'mail:smtp:ca_file'        => ['label' => 'SMTP-Zertifikatsdatei', 'type' => 'text', 'default' => ''],
        ];
        $index = 110;
        foreach ($fields as &$field) {
            $field['index'] = $index;
            $index += 10;
        }
        unset($field);

        return $fields;
    }
}
