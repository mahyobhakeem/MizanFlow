<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $purchase = $this->route('purchase');

        return $this->user()->can($purchase ? 'update' : 'create', $purchase ?? Purchase::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'issue_date' => ['required', 'date'],
            'supplier_invoice_ref' => ['nullable', 'string', 'max:50'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'يرجى اختيار المورد.',
            'supplier_id.exists' => 'المورد المختار غير موجود في النظام.',
            'warehouse_id.required' => 'يرجى اختيار المستودع المستلم.',
            'warehouse_id.exists' => 'المستودع المختار غير موجود.',
            'issue_date.required' => 'تاريخ الفاتورة إلزامي.',
            'issue_date.date' => 'صيغة تاريخ الفاتورة غير صحيحة.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.*.product_id.required' => 'يرجى تحديد المنتج لكل بند.',
            'items.*.product_id.exists' => 'المنتج المحدد في أحد البنود غير صالح.',
            'items.*.quantity.required' => 'الكمية إلزامية لكل بند.',
            'items.*.quantity.gt' => 'يجب أن تكون كمية الشراء أكبر من الصفر.',
            'items.*.unit_cost.required' => 'تكلفة الوحدة إلزامية لكل بند.',
            'items.*.unit_cost.min' => 'تكلفة الوحدة لا يمكن أن تكون سالبة.',
            'items.*.tax_percent.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%.',
        ];
    }
}
