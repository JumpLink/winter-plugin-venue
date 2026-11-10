<?php namespace JumpLink\Venue;

use Backend;
use System\Classes\PluginBase;

/**
 * Venue: menus, opening hours and questions for a restaurant or any place with guests.
 * Everything is also served as a knowledge feed (routes.php) that AI assistants read.
 */
class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'jumplink.venue::lang.plugin.name',
            'description' => 'jumplink.venue::lang.plugin.description',
            'author'      => 'JumpLink',
            'icon'        => 'icon-cutlery',
            'homepage'    => 'https://github.com/JumpLink/winter-plugin-venue',
        ];
    }

    public function registerComponents()
    {
        return [
            \JumpLink\Venue\Components\VenueMenus::class => 'venueMenus',
            \JumpLink\Venue\Components\VenueJsonLd::class => 'venueJsonLd',
        ];
    }

    public function registerNavigation()
    {
        return [
            'venue' => [
                'label'       => 'jumplink.venue::lang.plugin.name',
                'url'         => Backend::url('jumplink/venue/menus'),
                'icon'        => 'icon-cutlery',
                'permissions' => ['jumplink.venue.manage'],
                'order'       => 480,
                'sideMenu'    => [
                    'menus' => [
                        'label' => 'jumplink.venue::lang.menus.label',
                        'icon'  => 'icon-book',
                        'url'   => Backend::url('jumplink/venue/menus'),
                        'permissions' => ['jumplink.venue.manage'],
                    ],
                    'openinghours' => [
                        'label' => 'jumplink.venue::lang.hours.label',
                        'icon'  => 'icon-clock-o',
                        'url'   => Backend::url('jumplink/venue/openinghours'),
                        'permissions' => ['jumplink.venue.manage'],
                    ],
                    'faqs' => [
                        'label' => 'jumplink.venue::lang.faq.label',
                        'icon'  => 'icon-question-circle',
                        'url'   => Backend::url('jumplink/venue/faqs'),
                        'permissions' => ['jumplink.venue.manage'],
                    ],
                ],
            ],
        ];
    }

    public function registerPermissions()
    {
        return [
            'jumplink.venue.manage' => [
                'tab'   => 'jumplink.venue::lang.plugin.name',
                'label' => 'jumplink.venue::lang.permissions.manage',
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'jumplink.venue::lang.settings.label',
                'description' => 'jumplink.venue::lang.settings.description',
                'category'    => 'jumplink.venue::lang.plugin.name',
                'icon'        => 'icon-cutlery',
                'class'       => \JumpLink\Venue\Models\Settings::class,
                'permissions' => ['jumplink.venue.manage'],
                'order'       => 500,
            ],
        ];
    }
}
