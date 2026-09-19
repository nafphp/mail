# Working on naf/mail

NAF is a small PHP framework with optional Composer plugins. Its core owns boot,
configuration, the service container, routing, events and PSR-7 responses. Prefer existing
NAF helpers, services and extension interfaces; keep application business rules in the host.
This package declares `type: naf-plugin` and is discovered after installation in a NAF host.
The plugin repository itself is not the application's web root.

Before changing code, read the [shared contribution workflow](https://github.com/nafphp/docs/blob/main/AGENT_WORKFLOW.md)
and [release procedure](https://github.com/nafphp/docs/blob/main/RELEASING.md).
In the multi-repository workspace, the same documents are in the sibling `docs/` checkout;
use the linked copies when working from a standalone clone. Preserve other contributors' work.
Review and update user documentation with every behavior change. Source fixes use an RC branch;
verified documentation-only changes can be merged and published by the agent.

## What this plugin does

`naf/mail` separates message construction from delivery. Install with `composer require naf/mail`;
check its `ext-fileinfo` and `ext-mbstring` requirements. Helpers are `Naf\Mail\mail` and `mailer`.
The default transport calls PHP's `mail()` and needs an actual delivery setup.

## Use it

Since 0.2.2, choose a transport class in `app/config.php`:

```php
<?php
use Naf\Mail\Core\Transport\MailTransport;

return ['mail' => ['transport' => MailTransport::class]];
```

The plugin supplies this default; it calls PHP's `mail()`. The selected class must implement
`TransportInterface`. `Mailer` uses its container binding when registered, otherwise `make()`
autowires it. Register scalar configuration or interface dependencies before consumers resolve
the mailer. Invalid configuration or failed construction raises an exception; there is no
fallback transport. Configuration contains a class name, never a transport instance.

For a local test, pass the shipped dummy directly:

```php
<?php
use Naf\Mail\Core\Transport\DummyTransport;
use function Naf\Mail\mailer;

$transport = new DummyTransport();
$mailer = mailer($transport);
$message = $mailer->createMail()
    ->setFrom('hello@example.com')
    ->addTo('reader@example.com')
    ->setContent('Hello', false);
$mailer->send($message);
$messages = $transport->getMessages();
$transport->clear();
```

An explicit transport bypasses configuration and the shared mailer without changing them.
`mailer()` without an argument retrieves the lazy shared `Mailer`, also used by constructor
injection. Existing explicit `Mailer::class` overrides still work. `new Mailer()` resolves the
configured transport; `new Mailer($transport)` uses that instance directly. `mail()` creates a
message through the shared mailer. `setContent($body)` means HTML; pass `false` for plain text.
Dummy captures are cloned at send time and kept only in memory. Use `clear()` between tests or
worker jobs. For previews across browser requests, see the documented file-outbox recipe.

## Change it here

Start with [Mail](src/Models/Mail.php), [Mailer](src/Core/Mailer.php),
[TransportInterface](src/Core/TransportInterface.php), [transports](src/Core/Transport/),
[configuration](src/config.php) and [bootstrap](bootstrap.php). A generic `TransportInterface`
binding does not override the configured class. Keep transport resolution lazy so application
bootstrap can register factories before they are used. Preserve recipient/header validation
and attachment behavior. Keep external delivery out of routine tests. `MailTransport` throws
`MailException` on failure; custom transports may report `false`.

## Verify

Run `composer test` and `composer validate --strict`. Use [tests](tests/) with capture transports
and controlled attachment fixtures. Verify messages/headers and failure behavior without
sending real mail. No `analyse` script is declared.

User docs: [Mail and local testing](https://nafphp.github.io/docs/mail/).

Follow the shared [PHP code style](https://github.com/nafphp/docs/blob/main/CODE_STYLE.md)
and `.php-cs-fixer.dist.php`. Run `composer style:check`; `composer style:fix` applies the rules.
Keep logical steps and local names readable, preserving public signatures and template output.
