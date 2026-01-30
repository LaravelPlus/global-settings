<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'value' => ['nullable'],
            'field_type' => ['required', 'string', Rule::in(['input', 'checkbox', 'multioptions'])],
            'options' => ['nullable', 'string', 'required_if:field_type,multioptions'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'role' => ['nullable', 'string', Rule::in(['user', 'plugin'])], // Users cannot create system settings
        ];
    }
}
