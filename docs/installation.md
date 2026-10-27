---
title: Installation
order: 10
---

# Installation

## Requirements

What `composer.json` requires: PHP 8.2 or later, `doctrine/orm` 2.15 or 3,
`omnisense/omnibase` 3.0 or 4.0, `omnibridge/symfony` 1.x, `omnibase/admin`, and
`symfony/config`, `dependency-injection`, `form`, `http-kernel`, `security-bundle`,
`messenger`, `rate-limiter`, `mailer`, `twig-bridge` and `console` 6, 7 or 8.

The confirmation mail goes through the core's notifier; the letters through Symfony's
`MailerInterface`, from the notifier's technical recipient (the site's address,
`base.settings.mail`): a site without one cannot send a letter (`LogicException`).

## The bundle

```bash
composer require omnibase/newsletter:dev-main
```

```php
// config/bundles.php
return [
    // ...
    Base\BaseBundle::class => ['all' => true],
    Base\Admin\AdminBundle::class => ['all' => true],
    Base\Newsletter\NewsletterBundle::class => ['all' => true],
];
```

`NewsletterBundle` extends the core's `AbstractBaseBundle`: its root is the package's
(`templates/` is `@Newsletter`, `public/` is `bundles/newsletter/`), and `build()` aliases
`Base\Newsletter\Entity\*` to `App\Entity\Newsletter\*` and the repositories to
`App\Repository\Newsletter\*`, unless the application declares its own class there.

## The routes

```yaml
# config/routes.yaml
newsletter_controller:
    resource: "@NewsletterBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

| Route | Path | Methods | |
|---|---|---|---|
| `newsletter_index` | `/newsletter` | GET | a page with the form; in the sitemap (0.4, monthly) |
| `newsletter_subscribe` | `/newsletter` | POST | the form's target |
| `newsletter_confirm` | `/newsletter/confirm/{token}` | GET | the confirmation mail's link |
| `newsletter_unsubscribe` | `/newsletter/unsubscribe/{token}` | GET, POST | GET: a page with one button; POST: unsubscribed |

`{token}` is 32 hexadecimal characters.

## Messenger

The back office's "send" dispatches `Base\Newsletter\Message\SendCampaignMessage`. Route it to
an asynchronous transport, so the button answers at once whatever the list's length:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            Base\Newsletter\Message\SendCampaignMessage: async
```

Without routing the message is handled synchronously, in the request.

## Links built in a worker

A worker builds absolute links without a request: set the router's default URI, or the links
of the mails point at `localhost`.

```yaml
# config/packages/routing.yaml
framework:
    router:
        default_uri: '%env(DEFAULT_URI)%'
```

## The schema and the public files

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
bin/console assets:install
```

Tables `newsletter_subscriber` and `newsletter_campaign`; the stylesheet
`bundles/newsletter/css/newsletter.css`.

## What the layout provides

The pages extend `layout1.html.twig` and fill `title`, `description`, `content` and
`stylesheets`. The form goes where the site wants it ([In a host page](usage-twig.md)).
