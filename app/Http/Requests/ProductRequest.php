<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->product ? 'update' : 'create', $this->product ?? Product::class);
    }

    public function rules(): array
    {
        $productId = $this->product?->id ?? $this->route('product')?->id;

        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'min_reorder_level' => ['required', 'numeric', 'min:0'],
            'default_sale_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'رمز الصنف (SKU) إلزامي ولا يمكن تركه فارغاً.',
            'sku.unique' => 'رمز الصنف (SKU) مستخدم مسبقاً، يجب أن يكون الرمز فريداً لكل صنف.',
            'barcode.unique' => 'الباركود مستخدم مسبقاً لمنتج آخر.',
            'name.required' => 'اسم المنتج إلزامي.',
            'category_id.required' => 'يرجى اختيار التصنيف.',
            'category_id.exists' => 'التصنيف المختار غير صالح.',
            'unit_id.required' => 'يرجى اختيار وحدة القياس.',
            'unit_id.exists' => 'وحدة القياس المختارة غير صالحة.',
            'default_sale_price.required' => 'سعر البيع الافتراضي إلزامي.',
            'default_sale_price.numeric' => 'سعر البيع يجب أن يكون قيمة رقمية صحيحة.',
            'default_sale_price.min' => 'سعر البيع لا يمكن أن يكون سالباً.',
            'min_reorder_level.required' => 'حد إعادة الطلب إلزامي.',
            'min_reorder_level.min' => 'حد إعادة الطلب لا يمكن أن يكون سالباً.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
