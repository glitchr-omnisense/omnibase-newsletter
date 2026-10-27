---
title: In a host page
order: 35
---

# In a host page

## The form partial

```twig
{% include '@Newsletter/client/_form.html.twig' %}
{% include '@Newsletter/client/_form.html.twig' with {source: 'home', heading: false} %}
```

`source` is kept with the address (which page it was filled on); `heading: false` leaves the
heading out. The partial uses the page's `newsletter_form` when one is given and not rendered
yet (the `/newsletter` page's), else builds one with `newsletter_form(source)`. Its flash
messages are in the `newsletter` flash bag.

## Twig functions

`Base\Newsletter\Twig\NewsletterExtension`:

| Function | Returns |
|---|---|
| `newsletter_form(source = 'site')` | the sign-up form's `FormView` |
| `newsletter_count(locale = null)` | the confirmed subscribers still on the list |

## Templates

`@Newsletter/client/`: `index` (the `/newsletter` page), `_form`, `confirmed`, `unsubscribe`;
`@Newsletter/email/`: `confirm`, `campaign`; `@Newsletter/admin/widget/subscribers`. An
application overrides them in `templates/bundles/NewsletterBundle/...`.

## The stylesheet

`bundles/newsletter/css/newsletter.css` reads `--newsletter-accent`, `--newsletter-soft`,
`--newsletter-line`, `--newsletter-surface` and `--newsletter-measure` when the host sets
them; it hides the old trap field (`.newsletter-trap`).

## Translations

The domain `newsletter` (ICU), in English, French and German
(`translations/newsletter+intl-icu.{en,fr,de}.yaml`): the form, the flash messages
(`flash.check_inbox`, `flash.subscribed`, `flash.invalid`, `flash.too_fast`, `flash.flood`,
`flash.too_many`), the mails, the digest (`digest.subject`, `digest.intro`) and the back office.
