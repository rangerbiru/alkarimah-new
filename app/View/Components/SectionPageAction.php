<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SectionPageAction extends Component
{
    public $label, $icon, $count, $createRoute, $filter, $filter_option, $filter_placeholder;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label, $icon, $count, $createRoute='', $filter=false, $filterOption=[], $filterPlaceholder='')
    {
        $this->label = $label;
        $this->icon = $icon;
        $this->count = $count;
        $this->createRoute = $createRoute;
        $this->filter = $filter;
        $this->filter_option = $filterOption;
        $this->filter_placeholder = $filterPlaceholder;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.section.page-action');
    }
}
