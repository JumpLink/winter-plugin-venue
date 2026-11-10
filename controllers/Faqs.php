<?php namespace JumpLink\Venue\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

class Faqs extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ReorderController::class,
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['jumplink.venue.manage'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('JumpLink.Venue', 'venue', 'faqs');
    }
}
