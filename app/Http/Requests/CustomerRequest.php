<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->customer ? 'update' : 'create', $this->customer ?? Customer::class);
    }

    public function rules(): array
    {
        $customerId = $this->customer?->id ?? $this->route('customer')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'code')->ignore($customerId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز العميل إلزامي.',
            'code.unique' => 'رمز العميل مستخدم مسبقاً.',
            'name.required' => 'اسم العميل إلزامي.',
            'email.email' => 'البريد الإلكتروني المدخل غير صالح.',
            'credit_limit.min' => 'سقف الائتمان لا يمكن أن يكون سالباً.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'credit_limit' => $this->input('credit_limit', 0),
        ]);
    }
}
