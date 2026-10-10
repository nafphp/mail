<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Exceptions\MailException;
use Naf\Mail\Models\Mail;
use SensitiveParameter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\CramMd5Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\PlainAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface as SmtpConnection;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

use function Naf\config;

/** Direct SMTP delivery; connections remain lazy until sending or checking. */
final class SmtpTransport implements TransportInterface
{
    private ?SmtpConnection $connection;

    private ?array $configuration;

    public function __construct(#[SensitiveParameter] ?array $configuration = null, ?SmtpConnection $connection = null)
    {
        $this->configuration = $configuration;
        $this->connection    = $connection;
    }

    public function sendMail(Mail $mail): bool
    {
        $message = (new Email())->from($mail->getFrom())->to(...$mail->getRecipients())
            ->replyTo($mail->getReplyTo())->subject($mail->getSubject());
        if ($mail->getCc()) {
            $message->cc(...$mail->getCc());
        }
        if ($mail->getBcc()) {
            $message->bcc(...$mail->getBcc());
        }
        if ($mail->isHtml()) {
            $message->html($mail->getContent());
            if ($mail->getTextContent() !== null) {
                $message->text($mail->getTextContent());
            }
        } else {
            $message->text($mail->getContent());
        }
        foreach ($mail->getAttachments() as $attachment) {
            // Mail captured the bytes when constructed; do not reopen a mutable path.
            $part = new DataPart(base64_decode($attachment['encoded'], true), $attachment['name'], $attachment['mimetype']);
            if ($attachment['inline']) {
                $part->asInline();
            }
            $message->addPart($part);
        }

        try {
            if ($this->connection()->send($message) === null) {
                return false;
            }
        } catch (TransportExceptionInterface $exception) {
            throw new MailException('SMTP delivery failed. Check the server, TLS, credentials and sender permissions.', 0, $exception);
        }

        return true;
    }

    /** Verify TLS/login without sending a message. */
    public function checkConnection(): void
    {
        $connection = $this->connection();
        if (!$connection instanceof EsmtpTransport) {
            throw new MailException('Connection checks require the SMTP transport.');
        }

        try {
            $connection->start();
        } finally {
            $connection->stop();
        }
    }

    private function connection(): SmtpConnection
    {
        return $this->connection ??= $this->connect(new SmtpSettings($this->configuration ?? (array) config('mail', [])));
    }

    private function connect(SmtpSettings $settings): EsmtpTransport
    {
        $connection = new EsmtpTransport($settings->host, $settings->port, $settings->security === 'tls');
        $connection->setAutoTls($settings->security === 'starttls')->setRequireTls($settings->security !== 'none');
        if ($settings->authentication !== 'off') {
            $connection->setUsername($settings->username)->setPassword($settings->password);
            $authenticator = match ($settings->authentication) {
                'login'            => new LoginAuthenticator(),
                'plain'            => new PlainAuthenticator(),
                'cram-md5'         => new CramMd5Authenticator(),
                'xoauth2', 'oauth' => new XOAuth2Authenticator(),
                default            => null,
            };
            if ($authenticator !== null) {
                $connection->setAuthenticators([$authenticator]);
            }
        }
        $options = ['verify_peer' => true, 'verify_peer_name' => true];
        if ($settings->caFile !== null) {
            $options['cafile'] = $settings->caFile;
        }
        $connection->getStream()->setTimeout($settings->timeout)->setStreamOptions(['ssl' => $options]);

        return $connection;
    }
}
