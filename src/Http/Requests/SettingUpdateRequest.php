<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settingId = $this->route('setting')?->id;

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('settings', 'key')->ignore($settingId),
            ],
            'value' => ['nullable'],
            'field_type' => ['required', 'string', Rule::in(['input', 'checkbox', 'multioptions'])],
            'options' => ['nullable', 'string'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'role' => ['nullable', 'string', Rule::in(['system', 'user', 'plugin'])],
        ];
    }
}
