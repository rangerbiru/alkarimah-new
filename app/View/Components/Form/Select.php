<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class Select extends Component
{
    public $option, $label, $old, $optional, $info, $loading, $init;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($option = [], $label = false, $old = '', $optional = false, $info = '', $loading = false, $init = 'true')
    {
        $this->option = $option;
        $this->label = $label;
        $this->old = $old;
        $this->optional = $optional;
        $this->info = $info;
        $this->loading = $loading;
        $this->init = $init;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.select');
    }
}
