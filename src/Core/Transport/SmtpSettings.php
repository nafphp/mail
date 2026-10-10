<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

use Naf\Mail\Exceptions\MailException;
use SensitiveParameter;

final readonly class SmtpSettings
{
    public string $host;
    public int $port;
    public string $security;
    public string $authentication;
    public string $username;
    public string $password;
    public float $timeout;
    public ?string $caFile;

    public function __construct(#[SensitiveParameter] array $mail)
    {
        $smtp       = (array) ($mail['smtp'] ?? []);
        $this->host = trim((string) ($smtp['host'] ?? ''));
        $security   = (string) ($smtp['security'] ?? 'auto');
        $port       = $smtp['port'] ?? '';
        if ($port !== '' && $port !== null && (!is_int($port) && !(is_string($port) && preg_match('/^[0-9]+$/D', $port) === 1))) {
            throw new MailException('mail:smtp needs a valid host and port.');
        }
        $local = in_array($this->host, ['localhost', '127.0.0.1', '::1', 'mailpit'], true);
        if ($security === 'auto') {
            $security = (int) $port === 465 ? 'tls' : ($local ? 'none' : 'starttls');
        }
        $this->security = $security;
        $this->port     = $port === '' || $port === null ? match ($this->security) {
            'tls'      => 465,
            'starttls' => 587,
            default    => $this->host === 'mailpit' ? 1025 : 25,
        } : (int) $port;
        $login                = (string) ($smtp['username'] ?? '');
        $login                = $login !== '' ? $login : (string) ($mail['username'] ?? '');
        $this->username       = $login !== '' ? $login : (string) ($mail['from'] ?? '');
        $password             = (string) ($smtp['password'] ?? '');
        $this->password       = $password !== '' ? $password : (string) ($mail['password'] ?? '');
        $authentication       = (string) ($smtp['authentication'] ?? 'auto');
        $this->authentication = $authentication === 'auto' ? ($login !== '' || $this->password !== '' ? 'on' : 'off') : $authentication;
        $this->timeout        = (float) ($smtp['timeout'] ?? 15);
        $this->caFile         = ($smtp['ca_file'] ?? '') === '' ? null : (string) $smtp['ca_file'];

        if ($this->host === '' || preg_match('/[\s\x00-\x1f\x7f]/', $this->host) || $this->port < 1 || $this->port > 65535) {
            throw new MailException('mail:smtp needs a valid host and port.');
        }
        if (!in_array($this->security, ['none', 'starttls', 'tls'], true)) {
            throw new MailException('mail:smtp:security must be auto, starttls, tls or none.');
        }
        if (!in_array($this->authentication, ['off', 'on', 'login', 'plain', 'cram-md5', 'xoauth2', 'oauth'], true)) {
            throw new MailException('mail:smtp:authentication must be auto, on, off, login, plain, cram-md5 or xoauth2.');
        }
        if ($this->authentication !== 'off' && ($this->security === 'none' || $this->username === '' || $this->password === '')) {
            throw new MailException('SMTP authentication requires TLS, a username and a password/token.');
        }
        if (!is_finite($this->timeout) || $this->timeout <= 0 || $this->timeout > 300 || ($this->caFile !== null && !is_readable($this->caFile))) {
            throw new MailException('SMTP needs a timeout in (0,300] and a readable CA file when configured.');
        }
    }
}
