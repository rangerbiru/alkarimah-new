<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class InputGroupMask extends Component
{
    public $label, $old, $optional, $info, $addon, $addon_end, $addon_position, $mask, $bootstrap;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($addon, $mask, $label=false, $old='', $optional=false, $info='', $addonEnd='', $addonPosition='left', $bootstrap='5')
    {
        $this->label = $label;
        $this->old = ($mask == 'nominal') ? str_replace('.', '', $old) : $old;
        $this->optional = $optional;
        $this->info = $info;
        $this->addon = $addon;
        $this->addon_end = $addonEnd;
        $this->addon_position = $addonPosition;
        $this->mask = $mask;
        $this->bootstrap = $bootstrap;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.input-group-mask');
    }
}
