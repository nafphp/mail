<?php

declare(strict_types=1);

namespace Tests\Unit;

use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\MailTransport;
use Naf\Mail\Exceptions\MailException;
use Tests\NafTestCase;

require_once __DIR__ . '/../Fixtures/MailFunctionStub.php';

class MailTransportTest extends NafTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['__naf_mail_return__'] = true;
        unset($GLOBALS['__naf_last_mail__']);
    }

    public function testSendMailBuildsHeadersAndBodyAndCallsMail(): void
    {
        $mailer = new Mailer(new MailTransport());
        $mail   = $mailer->createMail()
            ->addTo('to@example.com')
            ->addCc('cc@example.com')
            ->addBcc('bcc@example.com')
            ->setFrom('from@example.com')
            ->setReplyTo('reply@example.com')
            ->setSubject('Äüö ß Subject')
            ->setContent('<p>Hello <strong>World</strong></p>');

        $result = $mailer->send($mail);

        $this->assertTrue($result);
        $this->assertArrayHasKey('__naf_last_mail__', $GLOBALS);

        $call = $GLOBALS['__naf_last_mail__'];

        $this->assertSame('to@example.com', $call['to']);

        $this->assertNotSame('', $call['subject']);

        $headers = $call['headers'];

        $this->assertStringContainsString('From: from@example.com', $headers);
        $this->assertStringContainsString('Reply-To: reply@example.com', $headers);
        $this->assertStringContainsString('Cc: cc@example.com', $headers);
        $this->assertStringContainsString('Bcc: bcc@example.com', $headers);
        $this->assertStringContainsString('MIME-Version: 1.0', $headers);
        $this->assertStringContainsString('Content-Type: multipart/mixed;', $headers);

        $body = $call['message'];

        $this->assertStringContainsString('Content-Type: text/html; charset="utf-8"', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: 8bit', $body);
        $this->assertStringContainsString('<p>Hello <strong>World</strong></p>', $body);
        $this->assertStringContainsString('--', $body);
    }

    public function testSendMailIncludesAttachmentsInBody(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'naf_mail_');
        $this->assertNotFalse($tmpFile, 'Temp file could not be created');

        file_put_contents($tmpFile, 'attachment content');

        $mailer = new Mailer(new MailTransport());
        $mail   = $mailer->createMail()
            ->addTo('to@example.com')
            ->setFrom('from@example.com')
            ->setSubject('With attachment')
            ->setContent('Body text')
            ->addAttachment('file.txt', $tmpFile, false);

        $result = $mailer->send($mail);

        $this->assertTrue($result);

        $call = $GLOBALS['__naf_last_mail__'] ?? null;
        $this->assertNotNull($call);

        $body = $call['message'];

        $this->assertStringContainsString(
            'Content-Disposition: attachment; filename="file.txt"',
            $body
        );
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);

        $this->tempFiles[] = $tmpFile;
    }

    public function testSendMailThrowsExceptionWhenMailReturnsFalse(): void
    {
        $GLOBALS['__naf_mail_return__'] = false;

        $mailer = new Mailer(new MailTransport());
        $mail   = $mailer->createMail()
            ->addTo('to@example.com')
            ->setFrom('from@example.com')
            ->setSubject('Subject')
            ->setContent('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Unable to send mail using PHP mail()');

        try {
            $mailer->send($mail);
        } finally {
            $GLOBALS['__naf_mail_return__'] = true;
        }
    }
}
