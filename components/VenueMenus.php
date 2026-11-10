<?php namespace JumpLink\Venue\Components;

use Cms\Classes\ComponentBase;
use JumpLink\Venue\Models\Menu;

/** The menus valid today, as HTML in the page source (good for search engines). */
class VenueMenus extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'jumplink.venue::lang.menus.label',
            'description' => 'jumplink.venue::lang.plugin.description',
        ];
    }

    /** @return \Illuminate\Support\Collection<int, Menu> */
    public function menus()
    {
        return Menu::isActive()->validOn()->ordered()->get();
    }
}
