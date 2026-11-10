<?php namespace JumpLink\Venue\Models;

use Model;

/** A question guests ask, with the answer. */
class Faq extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    public $table = 'jumplink_venue_faqs';

    public $rules = [
        'question' => 'required|max:200',
        'answer' => 'required|max:4000',
    ];

    public $fillable = ['question', 'answer', 'assistant_may_say', 'is_active', 'sort_order'];

    protected $casts = ['assistant_may_say' => 'boolean', 'is_active' => 'boolean'];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
