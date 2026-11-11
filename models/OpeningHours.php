<?php namespace JumpLink\Venue\Models;

use Model;

/**
 * Opening hours: a regular entry, an exception that applies instead of it for a period, or the hours
 * of every public holiday (shown on the page; schema.org has no way to say "holiday", so not in the feed).
 */
class OpeningHours extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    public $table = 'jumplink_venue_opening_hours';

    public $rules = [
        'kind' => 'required|in:regular,exception,holiday',
        'opens' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
        'closes' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
        'valid_from' => 'required_if:kind,exception|nullable|date',
        'valid_through' => 'required_if:kind,exception|nullable|date|after_or_equal:valid_from',
    ];

    public $fillable = ['kind', 'name', 'description', 'season', 'period_text', 'days', 'is_closed', 'opens', 'closes', 'valid_from', 'valid_through', 'sort_order'];

    protected $jsonable = ['days'];

    protected $dates = ['valid_from', 'valid_through'];

    protected $casts = ['is_closed' => 'boolean'];

    public function beforeValidate()
    {
        // "Closed" is written as midnight to midnight, so the time rules need no special case.
        if ($this->is_closed) {
            $this->opens = $this->closes = '00:00';
        }
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('kind')->orderBy('sort_order')->orderBy('id');
    }

    public function getKindOptions()
    {
        return [
            'regular' => 'jumplink.venue::lang.hours.kind_regular',
            'exception' => 'jumplink.venue::lang.hours.kind_exception',
            'holiday' => 'jumplink.venue::lang.hours.kind_holiday',
        ];
    }

    public function getDaysOptions()
    {
        return array_combine(
            \JumpLink\Venue\Classes\FeedBuilder::WEEKDAYS,
            array_map(fn ($day) => 'jumplink.venue::lang.days.' . strtolower($day), \JumpLink\Venue\Classes\FeedBuilder::WEEKDAYS)
        );
    }
}
