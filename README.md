<div style="text-align: center;" align="center">

![NAF](assets/naf-logo-small-square.png)

[![NAF Mailer Plugin](https://github.com/nafphp/mailer/actions/workflows/php.yml/badge.svg)](https://github.com/nafphp/mailer/actions/workflows/php.yml)

</div>

[← Back to NAF](https://github.com/nafphp/framework)

---

# naf/mail

> **A lightweight, extensible mailer system for NAF – with full transport abstraction and attachment support.**

This plugin provides a clean interface for sending emails in your NAF application. It includes a default `MailTransport` that uses PHP’s built-in `mail()` function, but can easily be swapped for SMTP, API-based services, or other custom transports.

> 🧩 Part of the official NAF plugin collection. Install it if you need flexible, framework-integrated email handling.

## Documentation

**[Sending mail →](https://nafphp.github.io/docs/mail/)**

Everything about this package — what it does, how it is configured and what it needs — lives
in the [NAF documentation](https://nafphp.github.io/docs/). Not sure which packages you need?
[Start here](https://nafphp.github.io/docs/choosing-packages/).

## Install

```bash
composer require naf/mail
```

## License

MIT. Part of [NAF](https://github.com/nafphp/framework).
