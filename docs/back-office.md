---
title: The back office
order: 38
---

# The back office

Loaded when omnibase/admin is installed; omnibase/admin's default permissions.

## Letters

`CampaignCrudController` (slug `campaigns`): subject, language (the choices are
`kernel.enabled_locales`, else `en`, `fr`, `de`; empty for everyone), state (read), text,
button's page and words, recipients and dates (read). Filters: state, language.

On a draft, in the list and on the record:

- **Send me a test** (`/admin/campaigns/{entityId}/test`) - the letter as subscribers get it,
  `[Test]` in its subject, to the signed-in user's address (an address that is no subscriber:
  its unsubscribe link leads nowhere). Without an address on the user, an error flash.
- **Send** (`/admin/campaigns/{entityId}/send`), asked to confirm - `SENDING`, the message
  dispatched, the flash saying how many confirmed addresses it goes to. Anything but a draft is
  refused.

## Subscribers

`SubscriberCrudController` (slug `subscribers`): address, language, source, the three dates,
the IP on the detail page. Filters: confirmed (yes/no on `confirmedAt`), unsubscribed, language,
source. **Export** (`/admin/subscribers/export`, GET) downloads `newsletter-<date>.csv`: the
confirmed addresses still on the list, columns `email`, `locale`, `confirmed_at`, `source`.

## The dashboard tile

`SubscribersWidgetType`, named `newsletter_subscribers`
(`@Newsletter/admin/widget/subscribers.html.twig`): the confirmed subscribers, those not
confirmed yet, the five last to sign up.

```php
// App\Controller\Admin\DashboardController::configureWidgetItems()
yield MenuItem::block('newsletter_subscribers', 'Newsletter', 'fa-solid fa-envelope-open-text');
```
