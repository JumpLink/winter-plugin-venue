<?php namespace JumpLink\Venue\Classes;

/**
 * Opening hours as a page wants to show them: regular hours grouped by season, then by service,
 * and the exceptions and public-holiday hours as blocks. Pure PHP, so it needs no framework to test.
 */
class HoursView
{
    /**
     * @param array  $rows   opening hours rows: kind, name, description, season, period_text, days, is_closed,
     *                       opens, closes, valid_from, valid_through, in display order
     * @param string $today  Y-m-d in the venue's time zone; entries that ended before it are left out
     * @param array  $labels days (Monday …), daily, closed, time_range (":from" and ":until")
     *
     * @return array{seasons: array, exceptions: array}
     *   seasons:    season, period, services: name, description, lines: days, time
     *   exceptions: name, period, lines: service, time, closed (exceptions by date, then holiday rows)
     */
    public static function build(array $rows, string $today, array $labels): array
    {
        $seasons = [];
        $exceptions = [];
        $holidays = [];

        foreach ($rows as $row) {
            $through = self::day($row['valid_through'] ?? null);
            if ($through !== null && $through < $today) {
                continue;
            }
            $kind = $row['kind'] ?? 'regular';
            if ($kind === 'exception') {
                $key = ($row['name'] ?? '') . '|' . self::day($row['valid_from'] ?? null) . '|' . $through;
                $exceptions[$key] ??= ['from' => self::day($row['valid_from'] ?? null), 'name' => $row['name'] ?? '', 'period' => $row['period_text'] ?? null, 'lines' => []];
                $exceptions[$key]['lines'][] = self::line($row, $labels);
            } elseif ($kind === 'holiday') {
                $key = $row['name'] ?? '';
                $holidays[$key] ??= ['from' => null, 'name' => $key, 'period' => $row['period_text'] ?? null, 'lines' => []];
                $holidays[$key]['lines'][] = self::line($row, $labels);
            } else {
                $from = self::day($row['valid_from'] ?? null);
                $key = ($row['season'] ?? '') . '|' . ($row['period_text'] ?? '') . '|' . $from . '|' . $through;
                $seasons[$key] ??= ['from' => $from, 'season' => $row['season'] ?? '', 'period' => $row['period_text'] ?? null, 'services' => []];
                $service = $row['name'] ?? '';
                $seasons[$key]['services'][$service] ??= ['name' => $service, 'description' => null, 'lines' => []];
                $seasons[$key]['services'][$service]['description'] ??= self::blank($row['description'] ?? null);
                $seasons[$key]['services'][$service]['lines'][] = [
                    'days' => self::days((array) ($row['days'] ?? []), $labels),
                    'time' => self::time($row, $labels),
                ];
            }
        }

        usort($seasons, fn ($a, $b) => strcmp((string) $a['from'], (string) $b['from']));
        usort($exceptions, fn ($a, $b) => strcmp((string) $a['from'], (string) $b['from']));

        return [
            'seasons' => array_map(
                fn ($season) => ['season' => $season['season'], 'period' => $season['period'], 'services' => array_values($season['services'])],
                $seasons
            ),
            'exceptions' => array_map(
                fn ($block) => ['name' => $block['name'], 'period' => $block['period'], 'lines' => $block['lines']],
                array_merge($exceptions, array_values($holidays))
            ),
        ];
    }

    /** "Monday – Friday", "Saturday + Sunday", "Daily": runs of consecutive days, joined by " + ". */
    public static function days(array $days, array $labels): string
    {
        $indexes = [];
        foreach (FeedBuilder::WEEKDAYS as $i => $day) {
            if (in_array($day, $days, true)) {
                $indexes[] = $i;
            }
        }
        if (count($indexes) === 7 || $indexes === []) {
            return $indexes === [] ? '' : (string) ($labels['daily'] ?? 'Daily');
        }

        $runs = [];
        foreach ($indexes as $i) {
            $last = array_key_last($runs);
            if ($last !== null && $runs[$last][1] === $i - 1) {
                $runs[$last][1] = $i;
            } else {
                $runs[] = [$i, $i];
            }
        }

        $name = fn (int $i) => $labels['days'][FeedBuilder::WEEKDAYS[$i]] ?? FeedBuilder::WEEKDAYS[$i];

        return implode(' + ', array_map(function ($run) use ($name) {
            [$first, $last] = $run;
            if ($first === $last) {
                return $name($first);
            }

            return $name($first) . ($last - $first === 1 ? ' + ' : ' – ') . $name($last);
        }, $runs));
    }

    /** `9:00` and `11:30` become "9" and "11:30": whole hours lose their minutes and the leading zero. */
    public static function clock(string $time): string
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '00');
        $hours = (string) (int) $hours;

        return (int) $minutes === 0 ? $hours : $hours . ':' . $minutes;
    }

    private static function time(array $row, array $labels): ?string
    {
        if (!empty($row['is_closed'])) {
            return null;
        }

        return strtr($labels['time_range'] ?? ':from – :until', [
            ':from' => self::clock((string) ($row['opens'] ?? '00:00')),
            ':until' => self::clock((string) ($row['closes'] ?? '00:00')),
        ]);
    }

    private static function line(array $row, array $labels): array
    {
        $time = self::time($row, $labels);

        return ['service' => self::blank($row['description'] ?? null), 'time' => $time, 'closed' => $time === null];
    }

    private static function day($value): ?string
    {
        $text = self::blank($value === null ? null : (string) $value);

        return $text === null ? null : substr($text, 0, 10);
    }

    private static function blank(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
