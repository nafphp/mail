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

For local tests, replace the mailer binding in host bootstrap before consumers resolve it:

```php
<?php
use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\DummyTransport;
use function Naf\app;

$transport = new DummyTransport();
app()->container()->set(Mailer::class, static fn() => new Mailer($transport));
```

Application code can keep using `mail()->setFrom(...)->addTo(...)->setSubject(...)` and
`mailer()->send($message)`. `setContent($body)` means HTML; pass `false` for plain text.
`DummyTransport` is shipped since 0.2.2. Inspect captures with `$transport->getMessages()`;
`clear()` discards them between tests or worker jobs. Messages are cloned when sent and kept
only in memory. For previews across browser requests, see the documented file-outbox recipe.

## Change it here

Start with [Mail](src/Models/Mail.php), [Mailer](src/Core/Mailer.php),
[TransportInterface](src/Core/TransportInterface.php), [transports](src/Core/Transport/) and
[bootstrap](bootstrap.php). A binding for `TransportInterface` alone does not alter the default
mailer, whose factory directly constructs `MailTransport`; rebind `Mailer::class` as well.
Preserve recipient/header validation and attachment behavior. Keep external delivery out of
routine tests. The shipped transport throws `MailException` on failure; custom transports
may report `false`, so callers must follow the selected transport's contract.

## Verify

Run `composer test` and `composer validate --strict`. Use [tests](tests/) with capture transports
and controlled attachment fixtures. Verify messages/headers and failure behavior without
sending real mail. No `analyse` script is declared.

User docs: [Mail and local testing](https://nafphp.github.io/docs/mail/).
