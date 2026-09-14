<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fixtures\DependentTransport;
use Fixtures\ScalarTransport;
use Naf\Core\Config;
use Naf\Exceptions\ContainerException;
use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\{DummyTransport, MailTransport};
use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Exceptions\MailException;
use Naf\Mail\Models\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\NafTestCase;
use function Naf\{app, config};
use function Naf\Mail\{mail, mailer};

require_once __DIR__ . '/../Fixtures/MailFunctionStub.php';

class MailerConfigurationTest extends NafTestCase
{
    private array $previousServices = [];

    protected function setUp(): void
    {
        parent::setUp();
        $container = app()->container();

        foreach ([Config::class, Mailer::class, MailTransport::class, DummyTransport::class,
            DependentTransport::class, ScalarTransport::class, TransportInterface::class] as $id) {
            $this->previousServices[$id] = $container->has($id) ? $container->get($id) : null;
        }

        // Reinstall the package's lazy factory for each configuration scenario.
        require __DIR__ . '/../../bootstrap.php';
        unset($GLOBALS['__naf_last_mail__']);
        $GLOBALS['__naf_mail_return__'] = true;
    }

    protected function tearDown(): void
    {
        foreach ($this->previousServices as $id => $service) {
            if ($service === null) {
                app()->container()->reset($id);
            } else {
                app()->container()->set($id, $service);
            }
        }

        parent::tearDown();
    }

    private function configure(mixed $transport): void
    {
        app()->container()->set(Config::class, new Config(['mail' => ['transport' => $transport]]));
    }

    public function testPluginDefaultUsesPhpMailWithoutRegisteringItsTransport(): void
    {
        $this->assertSame(MailTransport::class, config('mail:transport'));
        $this->assertFalse(app()->container()->has(MailTransport::class));

        $message = mail()->setFrom('from@example.com')->addTo('to@example.com')->setContent('Default');
        $this->assertTrue(mailer()->send($message));
        $this->assertStringContainsString('Default', $GLOBALS['__naf_last_mail__']['message']);
    }

    public function testConfiguredClassIsCreatedWithoutRegistration(): void
    {
        $this->configure(DummyTransport::class);
        $this->assertFalse(app()->container()->has(DummyTransport::class));
        $this->assertTrue(mailer()->send(new Mail()));
        $this->assertArrayNotHasKey('__naf_last_mail__', $GLOBALS);
    }

    public function testConfiguredClassAutowiresItsDependencies(): void
    {
        $this->configure(DependentTransport::class);
        $capture = new DummyTransport();
        app()->container()->set(DummyTransport::class, $capture);

        $this->assertFalse(app()->container()->has(DependentTransport::class));
        $message = (new Mail())->setSubject('Autowired transport');
        $this->assertTrue(mailer()->send($message));
        $this->assertEquals([$message], $capture->getMessages());
    }

    public function testSelectedClassFactoryIsLazyAndSharedWithInjectedMailer(): void
    {
        $this->configure(DependentTransport::class);
        $capture = new DummyTransport();
        $calls = 0;
        app()->container()->set(DependentTransport::class, static function () use ($capture, &$calls) {
            $calls++;
            return new DependentTransport($capture);
        });

        $this->assertSame(0, $calls);
        $service = app()->container()->make(MailerConsumer::class);
        $this->assertSame(mailer(), $service->mailer);
        $this->assertSame(1, $calls);
        $service->mailer->send((new Mail())->setSubject('First'));
        (new Mailer())->send((new Mail())->setSubject('Second'));
        $this->assertSame(1, $calls);
        $this->assertCount(2, $capture->getMessages());
    }

    public function testExplicitTransportBypassesConfigAndSharedMailerFactory(): void
    {
        $this->configure(false);
        app()->container()->set(Mailer::class, static function () {
            throw new \RuntimeException('Shared mailer must not be resolved');
        });
        $transport = new DummyTransport();
        $message = (new Mail())->setSubject('Explicit');

        $this->assertTrue(mailer($transport)->send($message));
        $this->assertEquals([$message], $transport->getMessages());
        $this->assertFalse(config('mail:transport'));
    }

    public function testExplicitTransportDoesNotReplaceResolvedApplicationMailer(): void
    {
        $this->configure(DummyTransport::class);
        $configured = new DummyTransport();
        app()->container()->set(DummyTransport::class, $configured);
        $shared = mailer();
        $explicit = new DummyTransport();

        mailer($explicit)->send((new Mail())->setSubject('Explicit'));
        mailer()->send((new Mail())->setSubject('Configured'));

        $this->assertSame($shared, mailer(null));
        $this->assertSame('Explicit', $explicit->getMessages()[0]->getSubject());
        $this->assertSame('Configured', $configured->getMessages()[0]->getSubject());
        $this->assertCount(1, $configured->getMessages());
    }

    public function testInterfaceBindingDoesNotOverrideConfiguredClass(): void
    {
        $this->configure(MailTransport::class);
        $unused = new DummyTransport();
        app()->container()->set(TransportInterface::class, $unused);

        mailer()->send((new Mail())->setFrom('from@example.com')->addTo('to@example.com'));
        $this->assertArrayHasKey('__naf_last_mail__', $GLOBALS);
        $this->assertSame([], $unused->getMessages());
    }

    #[DataProvider('invalidConfiguration')]
    public function testInvalidConfigFailsWithoutFallback(mixed $value): void
    {
        $this->configure($value);
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('mail:transport must name a class');
        new Mailer();
    }

    public static function invalidConfiguration(): iterable
    {
        yield 'null' => [null];
        yield 'false' => [false];
        yield 'empty string' => [''];
        yield 'unknown class' => ['MissingTransport'];
        yield 'unrelated class' => [\stdClass::class];
        yield 'interface' => [TransportInterface::class];
        yield 'instance' => [new DummyTransport()];
        yield 'array' => [[DummyTransport::class]];
    }

    public function testMissingConfigFailsWithoutFallback(): void
    {
        app()->container()->set(Config::class, new Config());
        $this->expectException(MailException::class);
        new Mailer();
    }

    public function testWrongContainerResultIsRejected(): void
    {
        $this->configure(DummyTransport::class);
        app()->container()->set(DummyTransport::class, new \stdClass());
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('The container binding for ' . DummyTransport::class);
        new Mailer();
    }

    public function testUnresolvableDependencyFailsWithoutFallback(): void
    {
        $this->configure(ScalarTransport::class);
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('$endpoint');
        new Mailer();
    }

    public function testFactoryCanSupplyScalarConfiguration(): void
    {
        $this->configure(ScalarTransport::class);
        $transport = new ScalarTransport('test-endpoint');
        app()->container()->set(ScalarTransport::class, static fn() => $transport);

        $this->assertTrue(mailer()->send(new Mail()));
        $this->assertSame($transport, app()->container()->get(ScalarTransport::class));
    }

    public function testFailingSelectedFactoryPropagates(): void
    {
        $this->configure(DummyTransport::class);
        app()->container()->set(DummyTransport::class, static function () {
            throw new \RuntimeException('Transport setup failed');
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Transport setup failed');
        mailer();
    }
}

final class MailerConsumer
{
    public function __construct(public readonly Mailer $mailer)
    {
    }
}
