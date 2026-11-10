<?php namespace JumpLink\Venue\Components;

use Cms\Classes\ComponentBase;
use JumpLink\Venue\Classes\FeedBuilder;
use JumpLink\Venue\Classes\VenueFeed;
use JumpLink\Venue\Models\Settings;

/** schema.org JSON-LD of the venue for the page head: `{% component 'venueJsonLd' %}`. */
class VenueJsonLd extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'JSON-LD',
            'description' => 'schema.org',
        ];
    }

    public function json(): string
    {
        $type = Settings::get('schema_type') ?: 'Restaurant';

        return json_encode(
            FeedBuilder::jsonLd(VenueFeed::current(), $type),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
        );
    }
}
