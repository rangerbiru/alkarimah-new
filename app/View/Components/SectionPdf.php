<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SectionPdf extends Component
{
    public $label, $label_width, $orientation, $paper;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label, $labelWidth=235, $orientation='landscape')
    {
        $this->label = $label;
        $this->label_width = $labelWidth;
        $this->orientation = $orientation;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.section.pdf');
    }
}
