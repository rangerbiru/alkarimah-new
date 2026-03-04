<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Radio extends Component
{
    public $option, $label, $old, $optional;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($option, $label=false, $old='', $optional=false)
    {
        $this->option = $option;
        $this->label = $label;
        $this->old = $old;
        $this->optional = $optional;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.radio');
    }
}
