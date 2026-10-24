<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->supplier ? 'update' : 'create', $this->supplier ?? Supplier::class);
    }

    public function rules(): array
    {
        $supplierId = $this->supplier?->id ?? $this->route('supplier')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('suppliers', 'code')->ignore($supplierId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز المورد إلزامي.',
            'code.unique' => 'رمز المورد مستخدم مسبقاً.',
            'name.required' => 'اسم المورد إلزامي.',
            'email.email' => 'البريد الإلكتروني المدخل غير صالح.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
