<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class ButtonSubmit extends Component
{
    public $icon, $iconPosition, $label, $loading, $cancel_route;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($icon= 'fa-solid fa-paper-plane', $iconPosition="left", $label='', $loading='', $cancelRoute='')
    {
        $this->cancel_route = $cancelRoute;
        $this->icon = $icon;
        $this->iconPosition = $iconPosition;
        $this->label = (empty($label)) ? __('label.save') : $label;
        $this->loading = (empty($loading)) ? strtoupper(__('label.saving')) : $loading;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.button-submit');
    }
}
