# omnibase/newsletter

A newsletter for the sites built on [omnisense/omnibase](https://github.com/glitchr-omnisense/omnibase),
with no account behind it: the visitor leaves an address, confirms it from the mail it
receives, and gets the letters the site writes in its back office - each carrying its way out.
It is a Symfony bundle (`Base\Newsletter\NewsletterBundle`) on omnisense/omnibase 7.0 or later; the letters go
out through Symfony Messenger and Mailer. It was first made for two musicians' sites, where it
is the only thing a visitor can subscribe to.

- A `Subscriber` is an address (unique, lowercased), the key of its links (a 32-hex token), the
  language of the page it signed up from, where it came from, and its history: signed up,
  confirmed, left. Leaving keeps the row; coming back is a new confirmation under a new token.
- A `Campaign` is a letter: a subject, a plain text, an optional button, one language or
  everyone, its state (`draft`, `sending`, `sent`) and how many it went to.
- A monthly digest is drafted from the site's own sources (an agenda's dates, a blog's posts)
  by `newsletter:draft-digest`, as a draft letter to read and send.

Sign-ups are double opt-in by default, guarded against robots and against the use of the form
to flood a stranger's inbox, and answered with the same words whatever the address - the form
never tells who reads the letters. Every letter carries the one-click unsubscribe headers of
RFC 8058.

Use it for a site that writes to its readers a few times a year to a few thousand addresses.
It keeps no opening or click statistics and has no segments beyond the language.

## How this manual is organised

- [Installation](installation.md): requirements, the bundle, the routes, Messenger, the
  router's default URI, the schema.
- [Subscribers and letters](concepts.md): the entities, their states and methods, the queries.
- [Signing up](usage-subscribing.md): the form and its guards, double opt-in, confirming,
  leaving.
- [Sending a letter](usage-letters.md): the mails, the headers, the message and its handler.
- [The monthly digest](usage-digest.md): digest sources, the command, the draft it writes.
- [In a host page](usage-twig.md): the form partial, the Twig functions, the templates, the
  stylesheet, the translations.
- [The back office](back-office.md): the two screens, test and send, the export, the tile.
- [Configuration reference](configuration.md): `newsletter:`, the rate limiter, the services.
- [Testing](testing.md): the suite and what it checks.
