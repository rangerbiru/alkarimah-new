<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SectionPage extends Component
{
    public $label, $icon, $breadcrumb, $breadcrumb_data;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label, $icon, $breadcrumb='dashboard', $breadcrumbData=false)
    {
        $this->label = $label;
        $this->icon = $icon;
        $this->breadcrumb = $breadcrumb;
        $this->breadcrumb_data = $breadcrumbData;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.section.page');
    }
}
