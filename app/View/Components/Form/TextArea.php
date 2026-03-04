<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class TextArea extends Component
{
    public $label, $old, $optional, $info;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label=false, $old='', $optional=false, $info='')
    {
        $this->label = $label;
        $this->old = $old;
        $this->optional = $optional;
        $this->info = $info;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.text-area');
    }
}
