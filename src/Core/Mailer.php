<?php

declare(strict_types=1);

namespace Naf\Mail\Core;

use Naf\Mail\Exceptions\MailException;
use Naf\Mail\Models\Mail;

use function Naf\app;
use function Naf\config;

class Mailer
{
    private readonly TransportInterface $transport;

    /**
     * An explicit transport bypasses configuration and container resolution.
     */
    public function __construct(
        ?TransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? $this->resolveTransport();
    }

    /**
     * @return Mail
     */
    public function createMail(): Mail
    {
        return new Mail();
    }

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function send(Mail $mail): bool
    {
        return $this->transport->sendMail($mail);
    }

    private function resolveTransport(): TransportInterface
    {
        $class = config('mail:transport');

        if (!is_string($class) || !class_exists($class) || !is_subclass_of($class, TransportInterface::class)) {
            throw new MailException('mail:transport must name a class implementing ' . TransportInterface::class);
        }

        $container = app()->container();
        $transport = $container->has($class) ? $container->get($class) : $container->make($class);

        if (!$transport instanceof TransportInterface) {
            throw new MailException('The container binding for ' . $class . ' must implement ' . TransportInterface::class);
        }

        return $transport;
    }
}
