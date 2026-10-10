<?php

declare(strict_types=1);

namespace Tests\Unit;

use Naf\Core\Config;
use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\DummyTransport;
use Naf\Mail\Core\Transport\SmtpSettings;
use Naf\Mail\Core\Transport\SmtpTransport;
use Naf\Mail\Exceptions\MailException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Tests\NafTestCase;

use function Naf\app;

final class SmtpTransportTest extends NafTestCase
{
    public function testSharedCredentialsAndSecureDefaultsHaveExplicitProtocolOverrides(): void
    {
        $mail     = ['from' => 'support@example.test', 'password' => 'shared', 'smtp' => ['host' => 'smtp.example.test']];
        $settings = new SmtpSettings($mail);
        self::assertSame('support@example.test', $settings->username);
        self::assertSame('shared', $settings->password);
        self::assertSame('starttls', $settings->security);
        self::assertSame(587, $settings->port);
        self::assertSame('on', $settings->authentication);
        $mail['smtp'] += ['username' => 'relay-login', 'password' => 'separate', 'port' => 465];
        $override = new SmtpSettings($mail);
        self::assertSame('relay-login', $override->username);
        self::assertSame('separate', $override->password);
        self::assertSame('tls', $override->security);
        self::assertSame(465, $override->port);
        $local = new SmtpSettings(['from' => 'local@example.test', 'smtp' => ['host' => 'mailpit']]);
        self::assertSame(1025, $local->port);
        self::assertSame('none', $local->security);
        self::assertSame('off', $local->authentication);
    }

    public function testExplicitZeroCredentialsAndNumericStringPortArePreserved(): void
    {
        $settings = new SmtpSettings(['username' => 'shared-login', 'password' => 'shared-secret',
            'smtp'                               => ['host' => 'smtp.example.test', 'username' => '0', 'password' => '0', 'port' => '587']]);
        self::assertSame('0', $settings->username);
        self::assertSame('0', $settings->password);
        self::assertSame(587, $settings->port);
        self::assertSame('on', $settings->authentication);
    }

    public static function invalidConnections(): array
    {
        return [
            [['host' => 'smtp.example.test', 'security' => 'none', 'username' => 'user', 'password' => 'secret']],
            [['host' => 'smtp.example.test', 'authentication' => 'login', 'username' => 'user']],
            [['host' => 'smtp.example.test', 'security' => 'invalid']],
            [['host' => 'smtp.example.test', 'port' => 70000]],
            [['host' => 'smtp.example.test', 'port' => '587invalid']],
            [['host' => 'smtp.example.test', 'port' => 587.5]],
            [['host' => 'smtp.example.test', 'timeout' => NAN]],
            [['host' => 'smtp.example.test', 'timeout' => INF]],
            [['host' => "smtp.example.test\r\nAUTH PLAIN secret"]],
            [['host' => 'smtp.example.test', 'authentication' => 'invalid']],
        ];
    }

    #[DataProvider('invalidConnections')]
    public function testUnsafeOrInvalidConnectionsAreRejectedBeforeNetworkIo(array $smtp): void
    {
        $this->expectException(MailException::class);
        new SmtpSettings(['smtp' => $smtp]);
    }

    public function testMissingSmtpConfigurationDoesNotBreakDependencyResolution(): void
    {
        $transport = new SmtpTransport(['smtp' => ['host' => '']]);
        $mailer    = new Mailer($transport);
        self::assertInstanceOf(Mailer::class, $mailer);
        $this->expectException(MailException::class);
        $transport->checkConnection();
    }

    public function testSenderDefaultsApplyToHelpersWithoutOverridingExplicitMessageValues(): void
    {
        $original = app()->container()->get(Config::class);
        app()->container()->set(Config::class, new Config(['mail' => ['from' => 'support@example.test', 'reply_to' => 'support@example.test']]));

        try {
            $mailer  = new Mailer(new DummyTransport());
            $default = $mailer->createMail();
            self::assertSame('support@example.test', $default->getFrom());
            self::assertSame('support@example.test', $default->getReplyTo());
            $default->setFrom('other@example.test')->setReplyTo('other@example.test');
            self::assertSame('other@example.test', $default->getFrom());
            self::assertSame('other@example.test', $default->getReplyTo());
        } finally {
            app()->container()->set(Config::class, $original);
        }
    }

    public function testSmtpPreservesMimeRecipientsAndCapturedAttachmentBytes(): void
    {
        $connection = new class extends AbstractTransport {
            public ?SentMessage $sent = null;

            protected function doSend(SentMessage $message): void
            {
                $this->sent = $message;
            }

            public function __toString(): string
            {
                return 'capture://';
            }
        };
        $transport         = new SmtpTransport(['smtp' => ['host' => 'smtp.example.test']], $connection);
        $mailer            = new Mailer($transport);
        $path              = tempnam(sys_get_temp_dir(), 'smtp-attachment');
        $this->tempFiles[] = $path;
        file_put_contents($path, 'original bytes');
        $mail = $mailer->createMail()->setFrom('support@example.test')->setReplyTo('reply@example.test')
            ->addTo('to@example.test')->addCc('cc@example.test')->addBcc('hidden@example.test')
            ->setSubject('Grüße aus Nafinity')->setContent('<p>Hallo &amp; danke!</p>')->setTextContent('Hallo & danke!')
            ->addAttachment('note.txt', $path);
        file_put_contents($path, 'changed later');
        self::assertTrue($mailer->send($mail));
        $sent = $connection->sent;
        self::assertNotNull($sent);
        $message = $sent->getOriginalMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertSame('Grüße aus Nafinity', $message->getSubject());
        self::assertSame('reply@example.test', $message->getReplyTo()[0]->getAddress());
        self::assertSame('Hallo & danke!', $message->getTextBody());
        self::assertSame('<p>Hallo &amp; danke!</p>', $message->getHtmlBody());
        self::assertCount(3, $sent->getEnvelope()->getRecipients());
        self::assertSame('original bytes', $message->getAttachments()[0]->getBody());
        self::assertStringNotContainsString('Bcc:', $sent->toString());
    }

    public function testDeliveryErrorsRemainFailures(): void
    {
        $connection = new class extends AbstractTransport {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Simulated SMTP rejection');
            }

            public function __toString(): string
            {
                return 'reject://';
            }
        };
        $mailer = new Mailer(new SmtpTransport(['smtp' => ['host' => 'smtp.example.test']], $connection));
        $this->expectException(MailException::class);
        $mailer->send($mailer->createMail()->setFrom('support@example.test')->addTo('to@example.test')->setContent('Body', false));
    }
}
