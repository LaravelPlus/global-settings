<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LaravelPlus\GlobalSettings\Enums\SettingRole;
use LaravelPlus\GlobalSettings\Support\SettingRules;

final class SettingStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the form input the way SettingsService does.
     */
    protected function prepareForValidation(): void
    {
        $this->replace(SettingRules::prepare($this->all()));
    }

    /**
     * The shared setting rules; the admin form cannot create system settings.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...SettingRules::rules(),
            'role' => ['required', 'string', Rule::in([SettingRole::User->value, SettingRole::Plugin->value])],
        ];
    }
}
