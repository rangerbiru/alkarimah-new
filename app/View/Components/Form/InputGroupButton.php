<?php

namespace App\View\Components\Form;

use Illuminate\View\Component;

class InputGroupButton extends Component
{
    public $label, $old, $optional, $info, $button_id, $button_class, $button_label, $button_type, $button_position, $button_title, $bootstrap;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($buttonId, $buttonLabel, $label = false, $old = '', $optional = false, $info = '', $buttonClass = 'btn btn-light btn-sm', $buttonType = 'button', $buttonPosition = 'right', $buttonTitle = '', $bootstrap = '5')
    {
        $this->label = $label;
        $this->old = $old;
        $this->optional = $optional;
        $this->info = $info;
        $this->button_id = $buttonId;
        $this->button_class = (empty($buttonTitle)) ? $buttonClass : $buttonClass . ' set-tooltip';
        $this->button_label = $buttonLabel;
        $this->button_type = $buttonType;
        $this->button_position = $buttonPosition;
        $this->button_title = $buttonTitle;
        $this->bootstrap = $bootstrap;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.input-group-button');
    }
}
