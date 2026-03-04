<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputText extends Component
{
    public $label, $old, $optional, $info;

    /**
     * Create a new component instance.
     */
    public function __construct($label = false, $old = '', $optional = false, $info = '')
    {
        $this->label = $label;
        $this->old = $old;
        $this->optional = $optional;
        $this->info = $info;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form.input-text');
    }
}
