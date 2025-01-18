<?php

namespace App\View\Components;

use Illuminate\View\Component;

class EditButton extends Component
{
    public $route;
    public $icon;
    public $text;

    public function __construct($route, $icon = 'fas fa-pencil', $text = 'Editar')
    {
        $this->route = $route;
        $this->icon = $icon;
        $this->text = $text;
    }

    public function render()
    {
        return view('components.edit-button');
    }
}
