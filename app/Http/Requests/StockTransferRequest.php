<?php

namespace App\Http\Requests;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockTransfer::class);
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.required' => 'يرجى اختيار المستودع المصدر.',
            'from_warehouse_id.exists' => 'المستودع المصدر غير موجود.',
            'to_warehouse_id.required' => 'يرجى اختيار المستودع الوجهة.',
            'to_warehouse_id.exists' => 'المستودع الوجهة غير موجود.',
            'to_warehouse_id.different' => 'لا يمكن أن يكون المستودع المصدر هو نفسه المستودع الوجهة.',
            'items.required' => 'يجب تحديد صنف واحد على الأقل للتحويل.',
            'items.min' => 'يجب تحديد صنف واحد على الأقل للتحويل.',
            'items.*.product_id.required' => 'يرجى اختيار المنتج.',
            'items.*.product_id.exists' => 'المنتج المختار غير موجود.',
            'items.*.quantity.required' => 'الكمية المحولة إلزامية.',
            'items.*.quantity.gt' => 'يجب أن تكون كمية التحويل أكبر من الصفر.',
        ];
    }
}
