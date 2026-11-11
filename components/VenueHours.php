<?php namespace JumpLink\Venue\Components;

use Cms\Classes\ComponentBase;
use JumpLink\Venue\Classes\HoursView;
use JumpLink\Venue\Models\OpeningHours;
use JumpLink\Venue\Models\Settings;

/** The opening hours valid from today on, as HTML in the page source: `{% component 'venueHours' %}`. */
class VenueHours extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'jumplink.venue::lang.hours.label',
            'description' => 'jumplink.venue::lang.plugin.description',
        ];
    }

    /** @return array{seasons: array, exceptions: array} see HoursView::build */
    public function view(): array
    {
        $zone = Settings::get('time_zone') ?: 'UTC';
        $days = [];
        foreach (\JumpLink\Venue\Classes\FeedBuilder::WEEKDAYS as $day) {
            $days[$day] = \Lang::get('jumplink.venue::lang.days.' . strtolower($day));
        }

        return HoursView::build(
            OpeningHours::ordered()->get()->toArray(),
            (new \DateTime('now', new \DateTimeZone($zone)))->format('Y-m-d'),
            [
                'days' => $days,
                'daily' => \Lang::get('jumplink.venue::lang.hours.daily'),
                'time_range' => \Lang::get('jumplink.venue::lang.hours.time_range'),
            ]
        );
    }
}
