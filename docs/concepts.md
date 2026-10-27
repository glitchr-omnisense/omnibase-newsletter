---
title: Subscribers and letters
order: 20
---

# Subscribers and letters

## `Subscriber`

`Base\Newsletter\Entity\Subscriber` (`newsletter_subscriber`, unique on `email` and on
`token`, indexed on `ip` and `createdAt`) - an address, never an account.

| Property | Column | Meaning |
|---|---|---|
| `email` | `string(180)` | trimmed and lowercased (`Subscriber::normalize()`) |
| `token` | `string(32)` | 16 random bytes in hex: the key of the confirm and unsubscribe links |
| `locale` | `string(5)` | the language of the page it signed up from (lowercased; `en` when empty): its letters are in it |
| `source` | `string(40)` | where it came from: `site`, `import`, a page's key; `site` when empty |
| `ip` | `string(45)`, nullable | the address it signed up from, for the flood interval |
| `createdAt` | `datetime_immutable` | |
| `confirmedAt` | nullable | when its link was followed |
| `unsubscribedAt` | nullable | when it left |
| `lastCampaign` | `integer`, nullable | the id of the last letter it was sent: a retried send skips it |

`new Subscriber(string $email, string $locale = 'en', string $source = 'site', ?string $ip = null)`.

States:

| Method | True when |
|---|---|
| `isConfirmed()` | it confirmed, whether or not it left since |
| `isUnsubscribed()` | it left |
| `isActive()` | confirmed and still there: what the letters go to |
| `isPending()` | signed up, not confirmed, not gone |

Transitions:

- `confirm()` - sets `confirmedAt` once (the first date is kept); an address that left is not
  brought back by an old link.
- `unsubscribe()` - sets `unsubscribedAt` once; the row stays.
- `resubscribe(bool $doubleOptIn = true)` - for an address that left: `unsubscribedAt`
  cleared, a new token, and `confirmedAt` cleared (double opt-in) or set to now. An address
  still on the list is left as it is.
- `sent(Campaign)`, `wasSent(Campaign)` - the last letter it got.

## `Campaign`

`Base\Newsletter\Entity\Campaign` (`newsletter_campaign`):

| Property | Column | Constraints / meaning |
|---|---|---|
| `subject` | `string(150)` | not blank, 150 at most |
| `body` | `text` | not blank; plain text, a blank line between two paragraphs (line ends normalised to `\n`) |
| `link` | `string(255)`, nullable | the button's page: a path of the site (`/concerts`) or a full `http(s)://` address |
| `linkLabel` | `string(80)`, nullable | the button's words |
| `locale` | `string(5)`, nullable | who it goes to: the subscribers of this language; `null`: everyone |
| `state` | `CampaignState` | `DRAFT` at first |
| `createdAt`, `sentAt` | | |
| `recipients` | `integer` | how many it went to |

Empty strings are stored as `null`. `getParagraphs()` splits the body on blank lines.
`markSending()` refuses (`LogicException`) anything but a draft; `markSent(int $recipients)`
sets `SENT`, the count and `sentAt`. `isDraft()`, `isSent()`.

`CampaignState`: `DRAFT` (`draft`), `SENDING` (`sending`: the message dispatched, the mails
going out), `SENT` (`sent`).

## In memory

Run in omnisense/omnibase's harness image, with this checkout's `src/` on the autoloader:

```php
use Base\Newsletter\Entity\Campaign;
use Base\Newsletter\Entity\Subscriber;
use Base\Newsletter\Service\DigestDrafter;
use Base\Newsletter\Service\SignedTimestamp;

$subscriber = new Subscriber('  Anna@Example.ORG ', 'DE', '');
echo $subscriber->getEmail(), ' ', $subscriber->getLocale(), ' ', $subscriber->getSource(), ' ', strlen($subscriber->getToken()), "\n";
$subscriber->confirm()->unsubscribe();
$old = $subscriber->getToken();
$subscriber->resubscribe(true);
var_dump($subscriber->isPending(), $old !== $subscriber->getToken());

$campaign = new Campaign('November', "First paragraph.\r\n\r\nSecond\nparagraph.");
print_r($campaign->getParagraphs());

$signed = SignedTimestamp::sign(1767225600, 'secret');
var_dump(SignedTimestamp::verify($signed, 'secret'), SignedTimestamp::verify($signed, 'another'));

[$from, $to] = DigestDrafter::nextMonth(new DateTimeImmutable('2026-10-20'));
echo $from->format('Y-m-d'), ' ', $to->format('Y-m-d'), "\n";
```

```
anna@example.org de site 32
bool(true)
bool(true)
Array
(
    [0] => First paragraph.
    [1] => Second
paragraph.
)
int(1767225600)
NULL
2026-11-01 2026-12-01
```

## Repositories

`SubscriberRepository`: `findOneByEmail()` (normalised), `findOneByToken()`,
`findConfirmed(?string $locale = null)` (confirmed and not gone, of one language when given,
by id), `countConfirmed(?string $locale = null)`, `countPending()`, `findLatest(int $limit = 5)`
(the last signed up, whatever became of them), `findLastFromIp(string $ip)`.
`CampaignRepository` has the default queries.
