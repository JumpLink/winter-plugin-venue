<?php namespace JumpLink\Venue\Classes;

use DateTimeInterface;

/**
 * Turns the venue's records into the knowledge feed (format version 1, schema.org vocabulary).
 *
 * Pure: it takes plain arrays and knows nothing about Winter or the database, so the format can be
 * tested on its own. It does not judge what is valid today. The reader applies the validity dates in
 * the feed's time zone, so an expired menu stays in the feed until someone removes it.
 */
class FeedBuilder
{
    public const VERSION = 1;

    public const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /**
     * @param array $venue      name, description, url, street, postal_code, locality, time_zone, language, currency
     * @param array $hours      regular hours: name, description, days, opens, closes, is_closed, valid_from, valid_through
     * @param array $exceptions same shape; each needs valid_from and valid_through
     * @param array $menus      name, description, valid_from, valid_through, sections[name, description, items[name, description, price]]
     * @param array $faq        question, answer, assistant_may_say
     */
    public static function build(array $venue, array $hours, array $exceptions, array $menus, array $faq): array
    {
        $currency = self::text($venue['currency'] ?? null) ?? 'EUR';

        $address = self::filled([
            '@type' => 'PostalAddress',
            'streetAddress' => self::text($venue['street'] ?? null),
            'postalCode' => self::text($venue['postal_code'] ?? null),
            'addressLocality' => self::text($venue['locality'] ?? null),
        ], ['@type']);

        return self::filled([
            'version' => self::VERSION,
            'timeZone' => self::text($venue['time_zone'] ?? null) ?? 'UTC',
            'inLanguage' => self::text($venue['language'] ?? null),
            'name' => self::text($venue['name'] ?? null),
            'description' => self::text($venue['description'] ?? null),
            'url' => self::text($venue['url'] ?? null),
            'address' => count($address) > 1 ? $address : null,
            'openingHoursSpecification' => array_values(array_filter(array_map([self::class, 'hours'], $hours))),
            'specialOpeningHoursSpecification' => array_values(array_filter(array_map([self::class, 'hours'], $exceptions))),
            'hasMenu' => array_values(array_filter(array_map(fn ($menu) => self::menu($menu, $currency), $menus))),
            'faq' => array_values(array_filter(array_map([self::class, 'faq'], $faq))),
        ]);
    }

    /** schema.org without the feed-only extension (`faq`, `version`), for a page's JSON-LD. */
    public static function jsonLd(array $feed, string $type = 'Restaurant'): array
    {
        unset($feed['version'], $feed['faq'], $feed['timeZone']);
        $language = $feed['inLanguage'] ?? null;
        unset($feed['inLanguage']);

        return self::filled(['@context' => 'https://schema.org', '@type' => $type, 'inLanguage' => $language] + $feed);
    }

    private static function hours(array $row): ?array
    {
        $closed = !empty($row['is_closed']);
        $opens = $closed ? '00:00' : self::clock($row['opens'] ?? null);
        $closes = $closed ? '00:00' : self::clock($row['closes'] ?? null);
        if ($opens === null || $closes === null) {
            return null;
        }

        $days = array_values(array_intersect(self::WEEKDAYS, (array) ($row['days'] ?? [])));

        return self::filled([
            'name' => self::text($row['name'] ?? null),
            'description' => self::text($row['description'] ?? null),
            'dayOfWeek' => $days,
            'opens' => $opens,
            'closes' => $closes,
            'validFrom' => self::day($row['valid_from'] ?? null),
            'validThrough' => self::day($row['valid_through'] ?? null),
        ]);
    }

    private static function menu(array $row, string $currency): ?array
    {
        $name = self::text($row['name'] ?? null);
        $description = self::block($row['description'] ?? null);
        // A menu without a description for the assistant is not published (the field is mandatory).
        if ($name === null || $description === null) {
            return null;
        }

        $sections = [];
        foreach ((array) ($row['sections'] ?? []) as $section) {
            $sectionName = self::text($section['name'] ?? null);
            if ($sectionName === null) {
                continue;
            }
            $items = [];
            foreach ((array) ($section['items'] ?? []) as $item) {
                $itemName = self::text($item['name'] ?? null);
                if ($itemName === null) {
                    continue;
                }
                $price = self::amount($item['price'] ?? null);
                $items[] = self::filled([
                    'name' => $itemName,
                    'description' => self::block($item['description'] ?? null),
                    'offers' => $price === null ? null : ['price' => $price, 'priceCurrency' => $currency],
                ]);
            }
            $sections[] = self::filled([
                'name' => $sectionName,
                'description' => self::block($section['description'] ?? null),
                'hasMenuItem' => $items,
            ]);
        }

        return self::filled([
            'name' => $name,
            'description' => $description,
            'validFrom' => self::day($row['valid_from'] ?? null),
            'validThrough' => self::day($row['valid_through'] ?? null),
            'hasMenuSection' => $sections,
        ]);
    }

    private static function faq(array $row): ?array
    {
        $question = self::text($row['question'] ?? null);
        $answer = self::block($row['answer'] ?? null);
        if ($question === null || $answer === null) {
            return null;
        }

        return self::filled([
            'name' => $question,
            'acceptedAnswer' => ['text' => $answer],
            'assistantMaySay' => ($row['assistant_may_say'] ?? true) ? null : false,
        ]);
    }

    /** One line of text, or null when blank. */
    private static function text($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $text = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $text === '' ? null : $text;
    }

    /** Running text; line breaks stay. */
    private static function block($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));

        return $text === '' ? null : $text;
    }

    /** `HH:MM`, from `HH:MM` or `HH:MM:SS`; null when it is neither. */
    private static function clock($value): ?string
    {
        $text = self::text($value);

        return $text !== null && preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $text, $m) ? $m[1] : null;
    }

    /** `YYYY-MM-DD`, from a date object or a string that starts with one. */
    private static function day($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $text = self::text($value);

        return $text !== null && preg_match('/^\d{4}-\d{2}-\d{2}/', $text) ? substr($text, 0, 10) : null;
    }

    /** A price typed by a person ("18,50", "18.5 €") as a number; null when there is none. */
    private static function amount($value): ?float
    {
        $text = self::text($value);
        if ($text === null) {
            return null;
        }
        $text = str_replace(',', '.', preg_replace('/[^\d,.]/', '', $text));

        return is_numeric($text) ? (float) $text : null;
    }

    /** Drops null and empty entries; `$keep` names keys that stay even when they would be empty. */
    private static function filled(array $values, array $keep = []): array
    {
        return array_filter($values, fn ($value, $key) => in_array($key, $keep, true) || ($value !== null && $value !== []), ARRAY_FILTER_USE_BOTH);
    }
}
