<?php

namespace App\Http\Requests;

use App\Enums\RequestOperation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => trim((string) $this->input('phone')),
            'amount' => str_replace([',', '٬', ' '], '', strtr((string) $this->input('amount'), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.'])),
        ]);
    }

    public function rules(): array
    {
        $active = fn (string $table) => Rule::exists($table, 'id')->where('is_active', true);

        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,25}$/'],
            'country_id' => ['nullable', 'integer', $active('countries')],
            'service_id' => ['nullable', 'integer', $active('services')],
            'operation' => ['required', Rule::enum(RequestOperation::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'currency_id' => ['required', 'integer', $active('currencies')],
            'delivery_method_id' => ['nullable', 'integer', $active('delivery_methods')],
            'receive_via' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'], // honeypot: real people never see this field
        ];
    }

    public function attributes(): array
    {
        return collect(['name', 'phone', 'country_id' => 'country', 'service_id' => 'service', 'operation', 'amount',
            'currency_id' => 'currency', 'delivery_method_id' => 'delivery', 'receive_via', 'notes'])
            ->mapWithKeys(fn ($label, $key) => [is_int($key) ? $label : $key => __('web.request.' . $label)])->all();
    }
}
