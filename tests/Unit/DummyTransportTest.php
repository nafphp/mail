<?php

declare(strict_types=1);

namespace Tests\Unit;

use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\DummyTransport;
use Tests\NafTestCase;
use function Naf\app;
use function Naf\Mail\{mail, mailer};

require_once __DIR__ . '/../Fixtures/MailFunctionStub.php';

class DummyTransportTest extends NafTestCase
{
    public function testCapturesMessageWithoutCallingPhpMail(): void
    {
        unset($GLOBALS['__naf_last_mail__']);

        $transport = new DummyTransport();
        $mailer = new Mailer($transport);
        $file = tempnam(sys_get_temp_dir(), 'naf_mail_');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;
        file_put_contents($file, 'attachment content');

        $mail = $mailer->createMail()
            ->setFrom('from@example.com')
            ->setReplyTo('reply@example.com')
            ->addTo('to@example.com')
            ->addCc('cc@example.com')
            ->addBcc('bcc@example.com')
            ->setSubject('Captured message')
            ->setContent('<b>Hello</b>')
            ->addAttachment('example.txt', $file);

        $this->assertTrue($mailer->send($mail));
        $this->assertArrayNotHasKey('__naf_last_mail__', $GLOBALS);
        $this->assertCount(1, $transport->getMessages());
        $captured = $transport->getMessages()[0];
        $this->assertNotSame($mail, $captured);
        $this->assertEquals($mail, $captured);

        $mail->setSubject('Changed')->setContent('Changed', false)->addTo('later@example.com');
        file_put_contents($file, 'changed attachment');

        $this->assertSame('Captured message', $captured->getSubject());
        $this->assertSame('<b>Hello</b>', $captured->getContent());
        $this->assertTrue($captured->isHtml());
        $this->assertSame(['to@example.com'], $captured->getRecipients());
        $this->assertSame(
            chunk_split(base64_encode('attachment content')),
            $captured->getAttachments()[0]['encoded'],
        );
    }

    public function testRepeatedSendsCaptureEachVersionAndClearAllowsReuse(): void
    {
        $transport = new DummyTransport();
        $otherTransport = new DummyTransport();
        $mailer = new Mailer($transport);
        $mail = $mailer->createMail()->setSubject('First');

        $this->assertSame([], $transport->getMessages());
        $mailer->send($mail);
        $mailer->send($mail->setSubject('Second'));

        $this->assertCount(2, $transport->getMessages());
        $this->assertSame('First', $transport->getMessages()[0]->getSubject());
        $this->assertSame('Second', $transport->getMessages()[1]->getSubject());
        $this->assertSame([], $otherTransport->getMessages());

        $transport->clear();
        $this->assertSame([], $transport->getMessages());
        $mailer->send($mail->setSubject('Next job'));
        $this->assertCount(1, $transport->getMessages());
        $this->assertSame('Next job', $transport->getMessages()[0]->getSubject());
    }

    public function testApplicationBindingConnectsMailHelpersToDummyTransport(): void
    {
        $container = app()->container();
        $previousMailer = mailer();
        $transport = new DummyTransport();

        try {
            $container->set(Mailer::class, static fn() => new Mailer($transport));

            $message = mail()
                ->setFrom('hello@example.com')
                ->addTo('reader@example.com')
                ->setSubject('Through the application')
                ->setContent('Hello', false);

            $this->assertTrue(mailer()->send($message));
            $this->assertCount(1, $transport->getMessages());
            $this->assertEquals($message, $transport->getMessages()[0]);
        } finally {
            $container->set(Mailer::class, $previousMailer);
        }
    }
}
