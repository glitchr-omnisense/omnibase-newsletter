---
title: Testing
order: 60
---

# Testing

```bash
vendor/bin/phpunit                                                        # checked out on its own
php vendor/bin/phpunit -c vendor/omnibase/newsletter/phpunit.xml.dist     # from an application
```

In omnisense/omnibase's harness, this checkout mounted over the package:

```bash
docker run --rm --network none -v "$PWD":/srv/demo/vendor/omnibase/newsletter:ro omnisense/omnibase-harness test newsletter
```

```
...................SSSSSS.......                                  32 / 32 (100%)

OK, but incomplete, skipped, or risky tests!
Tests: 32, Assertions: 90, Skipped: 6.
```

The six skipped are `tests/Form/SubscribeGuardTest`'s: they boot the host application's kernel
and skip, saying so, when it does not load this bundle (the harness's does not register it), or
has no forms' guard.

| File | What |
|---|---|
| `Form/SubscribeGuardTest` | the form guarded in place of its own trap; a sign-up made as a person goes through; a filled trap, a sign-up too fast, one without the captcha's token are refused; without a captcha, the trap and the time alone |
| `Entity/SubscriberTest` | a new address lowercased and waiting; two addresses never share a token; an empty source is the site; confirming once, the first date kept; leaving keeps the row; an old link does not bring back an address that left; coming back with and without double opt-in; resubscribing an address still there changes nothing; the last letter remembered |
| `Entity/CampaignTest` | a new letter is a draft for everyone; it goes, then is sent with its count; only a draft goes |
| `Service/SignedTimestampTest` | what was signed is read back; another secret, a changed time or nothing are refused |
| `Service/DigestDrafterTest` | the coming month; each source asked for the period in the letter's language; nothing to announce, no draft; a part per source, entries in date order; the configured title replaces the default subject |
| `Controller/CrudRequiredFieldsTest` | the CRUDs' required fields |
