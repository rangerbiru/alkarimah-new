<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SectionForm extends Component
{
    public $icon, $label, $color;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label, $icon='', $color= 'info')
    {
        $this->icon = $icon;
        $this->label = $label;
        $this->color = $color;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.section.form');
    }
}
