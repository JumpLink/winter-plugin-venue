# Venue

A [Winter CMS](https://wintercms.com) plugin for restaurants and other places with guests. Staff
keep **menus**, **opening hours** and **common questions** in the backend. The plugin shows them on
the website and publishes the same data as a **knowledge feed** that an AI phone assistant such as
[sprechstunde](https://github.com/JumpLink/sprechstunde) reads.

- Plugin code: `JumpLink.Venue`
- License: Apache-2.0
- Requires: Winter CMS 1.2, PHP 8.1 or newer

## Install

Copy or clone the plugin to `plugins/jumplink/venue`, then run `php artisan winter:up`.

```sh
git clone https://github.com/JumpLink/winter-plugin-venue plugins/jumplink/venue
php artisan winter:up
```

Then open **Settings → Venue** and fill in the name, address, time zone and language.

## What staff maintain

| Backend page | What it holds |
|---|---|
| Menus | Name, a **required note for the AI assistant**, validity dates, pictures of the printed card, sections with dishes and prices |
| Opening hours | Regular hours with an optional season heading, exceptions (closed days, Christmas) that apply instead for a period, and the hours of every public holiday (page only; schema.org has no way to say it) |
| Questions | Question and answer pairs; one can be kept for the website only |

A menu without its note for the assistant is not published in the feed. The assistant cannot judge
a bare list of dishes, so the note says what the menu is and when it applies.

## The feed

`GET /api/jumplink/venue/feed` returns one JSON document in schema.org vocabulary (format version 1,
throttled, cached for five minutes). It lists public information only. The format is defined by the
reader: see the
[feed documentation](https://github.com/JumpLink/sprechstunde/tree/main/docs/feed) in sprechstunde.

The plugin does not decide what is valid today. It writes the validity dates, and the reader applies
them in the venue's time zone. Inactive menus are left out of the feed.

Point sprechstunde at the address shown under **Settings → Venue** (source kind `feed`).

## On the website

```twig
{% component 'venueMenus' %}     {# menus valid today, as HTML #}
{% component 'venueHours' %}     {# opening hours from today on, by season, as HTML #}
{% component 'venueJsonLd' %}    {# schema.org JSON-LD for the page head #}
```

## Develop

The feed builder (`classes/FeedBuilder.php`) is plain PHP without Winter dependencies and has its
own tests:

```sh
composer install
vendor/bin/phpunit
```
