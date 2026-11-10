<?php namespace JumpLink\Venue\Classes;

use JumpLink\Venue\Models\Faq;
use JumpLink\Venue\Models\Menu;
use JumpLink\Venue\Models\OpeningHours;
use JumpLink\Venue\Models\Settings;

/** The feed of this site: reads the records and hands them to the FeedBuilder. */
class VenueFeed
{
    public static function current(): array
    {
        $settings = Settings::instance();
        $hours = OpeningHours::ordered()->get();

        return FeedBuilder::build(
            [
                'name' => $settings->name,
                'description' => $settings->description,
                'url' => $settings->url,
                'street' => $settings->street,
                'postal_code' => $settings->postal_code,
                'locality' => $settings->locality,
                'time_zone' => $settings->time_zone,
                'language' => $settings->language,
                'currency' => $settings->currency,
            ],
            $hours->where('kind', 'regular')->toArray(),
            $hours->where('kind', 'exception')->toArray(),
            Menu::isActive()->ordered()->get()->toArray(),
            Faq::isActive()->ordered()->get()->toArray()
        );
    }
}
