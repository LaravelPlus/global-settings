<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Support;

use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use LaravelPlus\GlobalSettings\Enums\SettingGroup;
use LaravelPlus\GlobalSettings\Enums\SettingRole;
use LaravelPlus\GlobalSettings\Models\Setting;

/**
 * The one set of setting rules, shared by the admin FormRequests and by
 * SettingsService, so an HTTP request and a direct service call accept and
 * reject the same input.
 */
final class SettingRules
{
    /** @var list<string> */
    public const array FIELD_TYPES = ['input', 'checkbox', 'number', 'multioptions'];

    /**
     * Fill what an update leaves out from the stored setting (key, type,
     * options, role: a role only changes when one is sent), default a new
     * setting's role to user, and bring options/checkbox values to the shape
     * the model stores.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepare(array $data, ?Setting $current = null): array
    {
        $data['key'] ??= $current?->key;
        $data['field_type'] ??= $current?->field_type ?? 'input';
        $data['role'] ??= $current?->role->value ?? SettingRole::User->value;

        if ($current !== null && ! array_key_exists('options', $data)) {
            $data['options'] = $current->options;
        }

        // The admin form sends a comma-separated string; the model casts to an array.
        if (is_string($data['options'] ?? null)) {
            $data['options'] = array_values(array_filter(array_map('trim', explode(',', $data['options'])), 'strlen'));
        }

        if ($data['field_type'] === 'checkbox' && isset($data['value'])) {
            $bool = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['value'] = $bool === null ? $data['value'] : ($bool ? '1' : '0');
        }

        return $data;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?Setting $current = null): array
    {
        $type = static fn (string $fieldType): \Closure => static fn (Fluent $input): bool => $input->get('field_type') === $fieldType;

        return [
            'key' => ['required', 'string', 'max:255', Rule::unique(Setting::class, 'key')->ignore($current?->id)],
            'value' => [
                'nullable',
                Rule::when($type('checkbox'), ['in:0,1']),
                Rule::when($type('number'), ['numeric'], ['max:10000']),
                Rule::when($type('multioptions'), static fn (Fluent $input): array => [Rule::in((array) $input->get('options'))]),
            ],
            'field_type' => ['required', 'string', Rule::in(self::FIELD_TYPES)],
            'options' => ['nullable', 'array', 'required_if:field_type,multioptions'],
            'options.*' => ['string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'string', Rule::enum(SettingRole::class)],
            'group' => ['nullable', 'string', Rule::enum(SettingGroup::class)],
        ];
    }
}
