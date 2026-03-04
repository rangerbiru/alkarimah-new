<?php

namespace App\View\Components\Form;

use App\Helpers\Common;
use Illuminate\View\Component;

class DatePicker extends Component
{
    public $label, $old, $old_start, $old_end, $optional, $info, $picker_type, $id_start, $id_end, $name_start, $name_end;

    /*
    * # Date Picker requirement
    * Variable : $label, $picker_type
    * Attribute : name, id
    *
    * # Date Range Picker requirement
    * Variable : $label, $picker_type, $id_start, $id_end, $name_start, $name_end
    * Attribute : name
    *
    * # Year Picker requirement
    * Variable : $label, $picker_type
    * Attribute : name, id
    *
    * # Time Picker requirement
    * Variable : $label, $picker_type
    * Attribute : name, id
    */

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($pickerType, $old='', $oldEnd='', $label=false, $optional=false, $info='', $idStart='', $idEnd='', $nameStart='', $nameEnd='')
    {
        if ($pickerType == 'date-range')
            $oldval = (empty($old) or empty($oldEnd)) ? '' : Common::dateFormat($old, 'dd mmm yyyy', 'en') . ' - ' . Common::dateFormat($oldEnd, 'dd mmm yyyy', 'en');
        else
            $oldval = $old;

        $this->label = $label;
        $this->old = $oldval;
        $this->old_start = $old;
        $this->old_end = $oldEnd;
        $this->optional = $optional;
        $this->info = $info;
        $this->picker_type = $pickerType;
        $this->id_start = $idStart;
        $this->id_end = $idEnd;
        $this->name_start = $nameStart;
        $this->name_end = $nameEnd;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.date-picker');
    }
}
