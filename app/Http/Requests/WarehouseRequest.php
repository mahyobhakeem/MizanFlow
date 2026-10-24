<?php

namespace App\Http\Requests;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->warehouse ? 'update' : 'create', $this->warehouse ?? Warehouse::class);
    }

    public function rules(): array
    {
        $warehouseId = $this->warehouse?->id ?? $this->route('warehouse')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('warehouses', 'code')->ignore($warehouseId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز المستودع إلزامي.',
            'code.unique' => 'رمز المستودع مستخدم مسبقاً، يرجى اختيار رمز آخر.',
            'code.max' => 'رمز المستودع يجب ألا يتجاوز 20 حرفاً.',
            'name.required' => 'اسم المستودع إلزامي.',
            'name.max' => 'اسم المستودع يجب ألا يتجاوز 100 حرف.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
