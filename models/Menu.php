<?php namespace JumpLink\Venue\Models;

use Model;

/** One menu. Its description is written for the AI assistant and is mandatory. */
class Menu extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    public $table = 'jumplink_venue_menus';

    public $rules = [
        'name' => 'required|max:200',
        'description' => 'required|max:4000',
        'valid_through' => 'nullable|date|after_or_equal:valid_from',
    ];

    public $fillable = ['name', 'description', 'valid_from', 'valid_through', 'is_active', 'sort_order', 'sections'];

    protected $jsonable = ['sections'];

    protected $dates = ['valid_from', 'valid_through'];

    protected $casts = ['is_active' => 'boolean'];

    /** The pictures or scans of the printed card, for the website. */
    public $attachMany = [
        'images' => [\System\Models\File::class, 'order' => 'sort_order'],
    ];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Menus valid on the given day (default today); open ends count as valid. */
    public function scopeValidOn($query, $day = null)
    {
        $day = ($day ?? now())->format('Y-m-d');

        return $query
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $day))
            ->where(fn ($q) => $q->whereNull('valid_through')->orWhere('valid_through', '>=', $day));
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
