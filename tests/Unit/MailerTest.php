<?php

declare(strict_types=1);

namespace Tests\Unit;

use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\MailTransport;
use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Exceptions\MailException;
use Naf\Mail\Models\Mail;
use Tests\NafTestCase;

class MailerTest extends NafTestCase
{
    public function testFluentSettersAndGettersWorkAsExpected(): void
    {
        $transport = $this->createStub(TransportInterface::class);

        $mailer = new Mailer($transport);
        $mail   = $mailer->createMail()
            ->addTo('to@example.com')
            ->addCc('cc@example.com')
            ->addBcc('bcc@example.com')
            ->setFrom('from@example.com')
            ->setReplyTo('reply@example.com')
            ->setSubject('Test Subject')
            ->setContent('Hello World', false);

        $this->assertSame(['to@example.com'], $mail->getRecipients());
        $this->assertSame(['cc@example.com'], $mail->getCc());
        $this->assertSame(['bcc@example.com'], $mail->getBcc());
        $this->assertSame('from@example.com', $mail->getFrom());
        $this->assertSame('reply@example.com', $mail->getReplyTo());
        $this->assertSame('Test Subject', $mail->getSubject());
        $this->assertSame('Hello World', $mail->getContent());
        $this->assertFalse($mail->isHtml());
    }

    public function testReplyToFallsBackToFromWhenNotSet(): void
    {
        $mailer = new Mailer(new MailTransport());
        $mail   = $mailer->createMail()->setFrom('from@example.com');

        $this->assertSame('from@example.com', $mail->getReplyTo());
    }

    public function testAddAttachmentThrowsExceptionWhenFileDoesNotExist(): void
    {
        $mailer = new Mailer(new MailTransport());
        $mail   = $mailer->createMail();

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Attachment file not found');

        $mail->addAttachment('missing.txt', '/definitely/not/existing/file.txt');
    }

    public function testAddAttachmentStoresAttachmentMetaData(): void
    {
        $mailer = new Mailer(new MailTransport());
        $mail   = $mailer->createMail();

        $tmpFile = tempnam(sys_get_temp_dir(), 'naf_mail_');
        $this->assertNotFalse($tmpFile, 'Failed to create temp file');

        file_put_contents($tmpFile, 'dummy content');

        $mail->addAttachment('test.txt', $tmpFile, true);

        $attachments = $mail->getAttachments();

        $this->assertCount(1, $attachments);

        $attachment = $attachments[0];

        $this->assertSame('test.txt', $attachment['name']);
        $this->assertSame($tmpFile, $attachment['path']);
        $this->assertSame(
            chunk_split(base64_encode('dummy content')),
            $attachment['encoded'],
        );
        $this->assertSame(true, $attachment['inline']);
        $this->assertNotEmpty($attachment['mimetype']);

        $this->tempFiles[] = $tmpFile;
    }

    public function testSendDelegatesToTransportAndReturnsTrue(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($this->once())
            ->method('sendMail')
            ->with($this->isInstanceOf(Mail::class))
            ->willReturn(true);

        $mailer = new Mailer($transport);
        $mail   = $mailer->createMail()
            ->addTo('to@example.com')
            ->setFrom('from@example.com')
            ->setSubject('Test')
            ->setContent('Body');

        $result = $mailer->send($mail);

        $this->assertTrue($result);
    }

    public function testMailerHelperFunction()
    {
        $this->assertInstanceOf(Mailer::class, \Naf\Mail\mailer());
    }

    public function testMailHelperFunctions()
    {
        $this->assertInstanceOf(Mail::class, \Naf\Mail\mail());
    }
}
