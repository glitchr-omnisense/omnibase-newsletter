---
title: The monthly digest
order: 32
---

# The monthly digest

## Digest sources

A service implementing `Base\Newsletter\Digest\DigestSourceInterface` is found by itself
(`#[AutoconfigureTag('newsletter.digest_source')]`):

```php
use Base\Newsletter\Digest\DigestSourceInterface;

final class ConcertDigest implements DigestSourceInterface
{
    public function __construct(private readonly ConcertRepository $concerts)
    {
    }

    public function getName(): string
    {
        return 'Concerts';
    }

    /** @return list<array{title: string, text: string, url: ?string, date: ?\DateTimeImmutable}> */
    public function digest(\DateTimeImmutable $from, \DateTimeImmutable $to, string $locale): array
    {
        return array_map(fn (Concert $concert) => [
            'title' => $concert->getTitle(),
            'text' => $concert->getVenue(),
            'url' => $concert->getUrl(),
            'date' => $concert->getDate(),
        ], $this->concerts->findBetween($from, $to));
    }
}
```

omnibase/agenda ships one (`Base\Agenda\Digest\AgendaDigestSource`).

## The command

```bash
bin/console newsletter:draft-digest
bin/console newsletter:draft-digest --from=2026-11-01 --to=2026-12-01 --locale=de
```

| Option | Default | |
|---|---|---|
| `--from` | the first day of next month | the first day (Y-m-d) |
| `--to` | a month after `--from` | the day after the last |
| `--locale` | everyone, in `kernel.default_locale` | the letter's language and audience |

A date it cannot read, or `--to` not after `--from`, is exit 2. With
`newsletter.digest.enabled: false` it says the digest is off and does nothing. With nothing to
announce it writes no draft. A cron on the 20th of each month is the usual way.

## The draft

`Base\Newsletter\Service\DigestDrafter::draft($from, $to, ?string $locale = null, string $fallbackLocale = 'en'): ?Campaign`
asks every source for the period in the letter's language, drops entries without a title, and
writes, per source with something to say, its name in capitals and its entries in date order
(undated last): the title and its date (`IntlDateFormatter::FULL`, the time when it is not
midnight), the text, the link. The subject is `newsletter.digest.title` with `{month}`
replaced, else the translation `digest.subject` ("Your dates in {month}"); the text opens with
`digest.intro` ("What is coming in {month}."). The month is written `LLLL yyyy` in the language,
capitalised. The command persists the draft; nothing is sent - it waits in the back office.

`DigestDrafter::nextMonth(?\DateTimeImmutable $today = null)` gives the coming month: its first
day and the first of the next.
