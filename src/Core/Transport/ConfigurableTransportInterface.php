<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

use Naf\Mail\Core\TransportInterface;

/** Optional metadata for configuration UIs; existing transports remain compatible. */
interface ConfigurableTransportInterface extends TransportInterface
{
    /** @return array<string, array{label?: string, type?: string, default?: mixed, index?: int, sensitive?: bool, options?: array}> */
    public static function configurationFields(): array;
}
