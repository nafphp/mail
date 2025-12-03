<div style="text-align: center;" align="center">

![Logo](https://nixphp.github.io/docs/assets/nixphp-logo-small-square.png)

[![NixPHP Mailer Plugin](https://github.com/nixphp/mailer/actions/workflows/php.yml/badge.svg)](https://github.com/nixphp/mailer/actions/workflows/php.yml)

</div>

[← Back to NixPHP](https://github.com/nixphp/framework)

---

# nixphp/mail

> **A lightweight, extensible mailer system for NixPHP – with full transport abstraction and attachment support.**

This plugin provides a clean interface for sending emails in your NixPHP application. It includes a default `MailTransport` that uses PHP’s built-in `mail()` function, but can easily be swapped for SMTP, API-based services, or other custom transports.

> 🧩 Part of the official NixPHP plugin collection. Install it if you need flexible, framework-integrated email handling.

---

## 📦 Features

- ✅ Compose and send emails with fluent API
- ✅ Supports `To`, `Cc`, `Bcc`, `Reply-To`, and `Attachments`
- ✅ Sends HTML or plain text
- ✅ Fully transport-driven, extend, or swap backend logic
- ✅ Ships with default `MailTransport` using native PHP `mail()`

---

## 📥 Installation

```bash
composer require nixphp/mail
```

---

## 🚀 Usage

### 📤 Basic mail sending

```php
$mail = mail()
    ->setFrom('hello@example.com')
    ->addTo('john@example.com')
    ->setSubject('Hello from NixPHP')
    ->setContent('<b>Welcome!</b>', true);

mailer()->send($mail);
```

---

### 📎 Add attachments

```php
$mail = mail()
    ->setFrom('info@example.com')
    ->addTo('client@example.com')
    ->setSubject('Monthly Report')
    ->addAttachment('report.pdf', '/path/to/report.pdf')

mailer()->send($mail);
```

You can also attach images inline and reference them via `cid:`:

```php
->addAttachment('logo.png', '/path/to/logo.png', true)
->setContent('<img src="cid:logo.png">')
```

---

### 🔄 Use a custom transport

To swap out the default `MailTransport`, inject your own:

```php
use NixPHP\Mail\Mailer;
use App\Mail\MyCustomTransport;

$mailer = new Mailer(new MyCustomTransport());
$mail   = mail()->addTo('john@example.com');
$mailer->send($mail);
```

Your transport must implement:

```php
NixPHP\Mail\Core\TransportInterface
```

---

## ✅ Requirements

* `nixphp/framework` >= 0.1.0
* PHP >= 8.3

---

## 📄 License

MIT License.