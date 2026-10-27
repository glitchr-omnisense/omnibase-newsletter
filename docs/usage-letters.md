---
title: Sending a letter
order: 31
---

# Sending a letter

## The two mails

`Base\Newsletter\Service\NewsletterMailer`:

- `confirm(Subscriber $subscriber)` - "Confirm your address", through the core's notifier: a
  `Base\Entity\User\Notification` with `@Newsletter/email/confirm.html.twig` (variables
  `subject`, `site_title`, `locale`, `confirm` - the link -, `home`), sent on the `email`
  channel to the address, in its language.
- `campaign(Subscriber $subscriber, Campaign $campaign, bool $test = false)` - a letter: a
  `TemplatedEmail` sent with Symfony's mailer, because the notifier's e-mail cannot carry a
  header of its own. From the notifier's technical recipient (the site's address), named
  `newsletter.from_name` when set; subject prefixed `[Test] ` for a test;
  `@Newsletter/email/campaign.html.twig` with `subject`, `campaign`, `link` (a path made
  absolute on the site's home), `site_title` (`base.settings.title`), `locale`,
  `unsubscribe`, `home`, `test`; the e-mail's locale set to the subscriber's (Symfony 6.4+).
  Headers:

```
List-Unsubscribe: <https://example.com/newsletter/unsubscribe/<token>>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

With the mailer itself routed to Messenger, each mail is queued too.

## The send

The back office's "send" calls `Campaign::markSending()` (a draft only), flushes, and dispatches
`SendCampaignMessage($campaignId)`. `Base\Newsletter\MessageHandler\SendCampaignHandler`:

1. A campaign that is gone or already `SENT` is left.
2. Each confirmed subscriber still on the list - of the letter's language when it has one - is
   mailed, then marked with the letter (`Subscriber::sent()`); a subscriber already marked
   (a retried send) is counted and skipped.
3. An address the transport refuses (`TransportExceptionInterface`) is logged as a warning and
   skipped.
4. The marks are flushed every 50 subscribers; at the end the campaign is `SENT` with the
   number of mails.

A send that fails half-way and is retried does not write twice to the first half.

## The templates

Both mails are overridden the Symfony way: `templates/bundles/NewsletterBundle/email/confirm.html.twig`
and `.../email/campaign.html.twig`, with the variables above.
