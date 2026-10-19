<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * x-form.select dan x-form.radio: nilai tersimpan (old) harus ter-selected / checked,
 * apa pun tipenya (string, int, boolean, enum, array untuk select multiple).
 */
class FormComponentsTest extends TestCase
{
    private function select(array $props, array $option = ['1' => 'Satu', '2' => 'Dua', '3' => 'Tiga']): string
    {
        return Blade::render('<x-form.select name="f" :option="$option" '.$this->attrs($props).' />', ['option' => $option] + $props);
    }

    private function radio(array $props, array $option = ['1' => 'Tetap', '2' => 'Honorer', '3' => 'Pengabdian']): string
    {
        return Blade::render('<x-form.radio name="f" :option="$option" '.$this->attrs($props).' />', ['option' => $option] + $props);
    }

    private function attrs(array $props): string
    {
        return collect($props)->keys()->map(fn ($k) => ':'.$k.'="$'.$k.'"')->implode(' ');
    }

    /** @return array<int, string> nilai option yang selected */
    private function selectedOptions(string $html): array
    {
        preg_match_all('/<option value="([^"]*)"\s+selected/', $html, $m);

        return $m[1];
    }

    /** @return array<int, string> nilai radio yang checked */
    private function checkedRadios(string $html): array
    {
        preg_match_all('/<input type="radio"[^>]*value="([^"]*)"[^>]*checked/', $html, $m);

        return $m[1];
    }

    public function test_select_preselects_old_string_and_int(): void
    {
        $this->assertSame(['2'], $this->selectedOptions($this->select(['old' => '2'])));
        $this->assertSame(['3'], $this->selectedOptions($this->select(['old' => 3])));
    }

    public function test_select_preselects_old_enum_value(): void
    {
        $html = $this->select(['old' => EmployeeStatus::Honorer], ['1' => 'Tetap', '2' => 'Honorer', '3' => 'Pengabdian']);

        $this->assertSame(['2'], $this->selectedOptions($html));
    }

    public function test_select_preselects_old_boolean_and_zero(): void
    {
        $option = ['1' => 'Ya', '0' => 'Tidak'];

        $this->assertSame(['0'], $this->selectedOptions($this->select(['old' => false], $option)));
        $this->assertSame(['1'], $this->selectedOptions($this->select(['old' => true], $option)));
        $this->assertSame(['0'], $this->selectedOptions($this->select(['old' => 0], $option)));
    }

    public function test_select_multiple_preselects_every_old_value(): void
    {
        $html = Blade::render('<x-form.select name="f[]" multiple :option="$option" :old="$old" />', [
            'option' => ['1' => 'Satu', '2' => 'Dua', '3' => 'Tiga'],
            'old' => [1, '3'],
        ]);

        $this->assertSame(['1', '3'], $this->selectedOptions($html));
    }

    public function test_select_without_value_selects_nothing(): void
    {
        $this->assertSame([], $this->selectedOptions($this->select(['old' => null])));
        $this->assertSame([], $this->selectedOptions($this->select(['old' => ''])));
        $this->assertSame([], $this->selectedOptions($this->select(['old' => '99'])));
    }

    public function test_select_with_selected_prop_ignores_old(): void
    {
        $this->assertSame(['3'], $this->selectedOptions($this->select(['old' => '1', 'selected' => '3'])));
        $this->assertSame(['1', '2'], $this->selectedOptions($this->select(['selected' => [1, 2]])));
    }

    public function test_select_with_null_selected_falls_back_to_old(): void
    {
        $this->assertSame(['2'], $this->selectedOptions($this->select(['old' => '2', 'selected' => null])));
    }

    public function test_radio_checks_stored_value_for_every_type(): void
    {
        $this->assertSame(['2'], $this->checkedRadios($this->radio(['old' => '2'])));
        $this->assertSame(['3'], $this->checkedRadios($this->radio(['old' => 3])));
        $this->assertSame(['2'], $this->checkedRadios($this->radio(['old' => EmployeeStatus::Honorer])));
    }

    public function test_radio_boolean_checks_only_the_matching_option(): void
    {
        $option = ['1' => 'Ya', '0' => 'Tidak'];

        // Dulu: false == '' mencentang radio pertama juga
        $this->assertSame(['0'], $this->checkedRadios($this->radio(['old' => false], $option)));
        $this->assertSame(['1'], $this->checkedRadios($this->radio(['old' => true], $option)));
        $this->assertSame(['0'], $this->checkedRadios($this->radio(['old' => 0], $option)));
    }

    public function test_radio_first_option_is_default_only_when_there_is_no_stored_value(): void
    {
        $this->assertSame(['1'], $this->checkedRadios($this->radio(['old' => ''])));
        $this->assertSame(['1'], $this->checkedRadios($this->radio(['old' => null])));
    }

    public function test_radio_first_option_never_overrides_a_stored_value(): void
    {
        $this->assertSame(['3'], $this->checkedRadios($this->radio(['old' => '3'])));
        // Nilai tersimpan yang tidak ada di opsi: biarkan kosong, jangan timpa dengan radio pertama
        $this->assertSame([], $this->checkedRadios($this->radio(['old' => '99'])));
    }
}
