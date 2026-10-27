---
title: Configuration reference
order: 40
---

# Configuration reference

## `newsletter:`

`Base\Newsletter\DependencyInjection\NewsletterConfiguration`, dumped with Symfony's
`YamlReferenceDumper` in omnisense/omnibase's harness image:

```yaml
newsletter:

    # An address receives nothing before it confirms from the mail it was sent. Off, it is signed up at once.
    double_opt_in:        true

    # Seconds between the form being opened and sent: faster is a robot.
    min_delay:            3

    # Seconds between two sign-ups from the same address.
    flood_interval:       60

    # The sender's name on the letters; null: the site's (base.settings.mail.name).
    from_name:            null
    digest:

        # newsletter:draft-digest drafts at all.
        enabled:              true

        # The digest's subject, {month} replaced; null: "Your dates in {month}", translated.
        title:                null
```

| Option | Type | Default | Read by | Effect |
|---|---|---|---|---|
| `double_opt_in` | bool | `true` | `NewsletterController` | `false`: on the list at once, no confirmation mail |
| `min_delay` | int, 0 or more | `3` | `SubscribeType` (the guard's `min_delay`), `SubscribeGuard` | the seconds a person needs; `0` turns the check off |
| `flood_interval` | int, 0 or more | `60` | `SubscribeGuard` | seconds between two sign-ups from one IP; `0` off |
| `from_name` | string or null | `null` | `NewsletterMailer` | the letters' sender name; `null` keeps the site address's own |
| `digest.enabled` | bool | `true` | `DraftDigestCommand` | `false`: the command does nothing |
| `digest.title` | string or null | `null` | `DigestDrafter` | the digest's subject, `{month}` replaced |

Parameters: `newsletter.double_opt_in`, `newsletter.min_delay`, `newsletter.flood_interval`,
`newsletter.from_name`, `newsletter.digest`, `newsletter.digest.enabled`,
`newsletter.digest.title`.

## The rate limiter

`NewsletterExtension` prepends to FrameworkBundle, when symfony/rate-limiter is installed:

```yaml
framework:
    rate_limiter:
        newsletter_subscribe:
            policy: sliding_window
            limit: 5
            interval: '1 hour'
```

A host's own `framework.rate_limiter.newsletter_subscribe`, read after, wins. `SubscribeGuard`
gets `limiter.newsletter_subscribe` (`null` when it does not exist: no limit).

## Services

`config/services.php` autowires and autoconfigures every class of `src/` but
`DependencyInjection/`, `Entity/`, `Enum/`, `Message/`, `Controller/Admin/`, `Admin/` and the
bundle class; `Controller/Client/` is tagged `controller.service_arguments`; with omnibase/admin,
`Controller/Admin/` and `Admin/`. `SendCampaignHandler` is `#[AsMessageHandler]`,
`DraftDigestCommand` `#[AsCommand]`, `DigestSourceInterface` tags `newsletter.digest_source`
(collected by `DigestDrafter` with `#[AutowireIterator]`).
