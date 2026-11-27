<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use LaravelPlus\GlobalSettings\Support\SettingRules;

final class SettingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fill omitted fields from the stored setting (a missing role keeps the
     * current one) and normalize the way SettingsService does.
     */
    protected function prepareForValidation(): void
    {
        $this->replace(SettingRules::prepare($this->all(), $this->route('setting')));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return SettingRules::rules($this->route('setting'));
    }
}
