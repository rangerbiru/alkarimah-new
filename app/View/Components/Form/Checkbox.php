<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Checkbox extends Component
{
    public $label, $old;

    /**
     * Create a new component instance.
     */
    public function __construct($label, $old)
    {
        $this->label = $label;
        $this->old = $old;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form.checkbox');
    }
}
