<?php

namespace App\View\Components\Form;

use Illuminate\Support\Facades\Crypt;
use Illuminate\View\Component;

class InputFile extends Component
{
    public $label, $old, $optional, $info, $accept_file, $image_default, $image_height;

    /**
     * $accept_file
     * Input file nya berupa apa
     * - file (default)
     * - image
     *
     * $image_default
     * ID dari table attachment
     *
     * $image_height
     * Height untuk gambar previewnya, satuannya px
     * Hanya diisi apabila $accept=image
     * Contoh : 80px
     *
     * Wajib mendeklarasikan atribut "id" pada input filenya
     */

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label=false, $optional=false, $acceptFile='file', $info='', $imageDefault='', $imageHeight='')
    {
        $this->label = $label;
        $this->optional = $optional;
        $this->info = $info;
        $this->accept_file = $acceptFile;
        $this->image_height = $imageHeight;

        if ($acceptFile == 'image') {
            $default = (empty($imageDefault)) ? asset('images/default/image.png') : route('attachment.get', Crypt::encrypt($imageDefault));
            $this->image_default = $default;
        } else
            $this->image_default = $imageDefault;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form.input-file');
    }
}
