<?php

declare(strict_types=1);

namespace Tests\Unit;

use Naf\Mail\Core\Transport\ConfigurableTransportInterface;
use Naf\Mail\Core\Transport\DummyTransport;
use Naf\Mail\Core\Transport\TransportConfiguration;
use Naf\Mail\Models\Mail;
use PHPUnit\Framework\TestCase;

final class TransportConfigurationTest extends TestCase
{
    public function testSmtpDescribesPortTlsAndWriteOnlyCredentialsWithoutConnecting(): void
    {
        $fields = TransportConfiguration::fields('Naf\\Mail\\Core\\Transport\\SmtpTransport');
        self::assertSame(65535, $fields['mail:smtp:port']['options']['max']);
        self::assertArrayHasKey('starttls', $fields['mail:smtp:security']['options']['choices']);
        self::assertTrue($fields['mail:smtp:password']['sensitive']);
        self::assertSame('', $fields['mail:smtp:password']['default']);
    }

    public function testCustomTransportMetadataWinsWithoutChangingTheTransportContract(): void
    {
        self::assertSame(
            ['mail:example:token' => ['sensitive' => true]],
            TransportConfiguration::fields(DescribedTransport::class),
        );
        self::assertSame([], TransportConfiguration::fields(DummyTransport::class));
    }
}

final class DescribedTransport implements ConfigurableTransportInterface
{
    public function sendMail(Mail $mail): bool
    {
        return true;
    }

    public static function configurationFields(): array
    {
        return ['mail:example:token' => ['sensitive' => true]];
    }
}
