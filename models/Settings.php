<?php namespace JumpLink\Venue\Models;

use Model;

/** The venue itself: name, address, time zone. One record per site. */
class Settings extends Model
{
    public $implement = [\System\Behaviors\SettingsModel::class];

    public $settingsCode = 'jumplink_venue_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->time_zone = 'Europe/Berlin';
        $this->language = 'de';
        $this->currency = 'EUR';
        $this->schema_type = 'Restaurant';
    }
}
