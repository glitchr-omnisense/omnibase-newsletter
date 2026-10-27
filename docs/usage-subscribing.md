---
title: Signing up
order: 30
---

# Signing up

## The form

`Base\Newsletter\Form\SubscribeType` (block prefix `newsletter`, translation domain
`newsletter`, CSRF id `newsletter_subscribe`): `email` (not blank, an e-mail, 180 at most) and
`source` (hidden, 40 at most: which page it was filled on). It posts to `newsletter_subscribe`.

With a core that has the forms' guard (`Base\Service\FormGuard`), its `guard` option is
`{action: newsletter, min_delay: newsletter.min_delay, captcha_after: 1}`: the trap, the time
the form takes, the core's address lists, and the captcha (omnisense/omnishield) after one
refused try. With a core from before the guard, the form adds its own trap (`website`, hidden
by `newsletter.css`) and the time it was opened (`opened_at`, signed - see below), which
`SubscribeGuard` reads.

## What `POST /newsletter` does

1. An invalid form (the guard refusing included) - back with the flash "too fast" when the
   guard refused for its haste (`too_fast`, `stale`), "invalid" otherwise.
2. `Base\Newsletter\Service\SubscribeGuard::check()` - in order:
   - the old trap filled: `trapped` - the robot is thanked as anyone ("check your inbox", or
     "subscribed" without double opt-in) and nothing is kept;
   - the old signed time missing, forged or younger than `min_delay`: `too_fast`;
   - another sign-up from the same IP within `flood_interval` seconds: `flood`;
   - the rate limiter `newsletter_subscribe` refusing (5 an hour per IP by default):
     `too_many`.
   Each reason becomes the flash `flash.<reason>`.
3. The address is found, or a `Subscriber` is created with the request's locale, the form's
   source (lowercased, `[a-z0-9_-]`, else `site`) and the client IP.
4. `resubscribe(double_opt_in)`; without double opt-in, `confirm()`. Flushed.
5. Only an address that has not confirmed is sent the confirmation mail.
6. Back to the page the form was filled on (the referer, when it is this host), at
   `#newsletter`, else `/newsletter#newsletter`, with the flash "check your inbox" (or
   "subscribed").

The answer is the same for a new address, a pending one and a confirmed one: the form cannot be
used to find out who reads the letters.

### The signed time

`Base\Newsletter\Service\SignedTimestamp::sign(int $time, string $secret)` gives
`<seconds>.<HMAC-SHA256 of "newsletter-opened-at:<seconds>">` under the kernel secret;
`verify()` returns the time, or `null` for another secret, a changed time or nothing.

## Confirming

`GET /newsletter/confirm/{token}` confirms the subscriber of that token and renders
`@Newsletter/client/confirmed.html.twig`; an unknown token is a 404. Confirming twice keeps the
first date; the link of an address that left does nothing.

## Leaving

`GET /newsletter/unsubscribe/{token}` renders a page with one button (mail scanners follow
links: a GET never unsubscribes anyone). `POST` to the same address unsubscribes - from that
button, or from a mail client's one-click unsubscribe (RFC 8058), which sends no CSRF token:
the token is the secret. An unknown token is answered "done" all the same. The row stays,
marked; coming back is a new sign-up, a new confirmation and a new token.

## Without double opt-in

`newsletter.double_opt_in: false`: the address is on the list at once and no confirmation mail
is sent.
