<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sale = $this->route('sale');

        return $this->user()->can($sale ? 'update' : 'create', $sale ?? Sale::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id', 'required_if:payment_type,credit'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:cash,card,credit,split'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'complete_immediately' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'العميل المختار غير موجود في النظام.',
            'customer_id.required_if' => 'يرجى تحديد العميل عند اختيار طريقة الدفع الآجل.',
            'warehouse_id.required' => 'يرجى اختيار المستودع الذي سيتم الصرف منه.',
            'warehouse_id.exists' => 'المستودع المختار غير موجود.',
            'invoice_date.required' => 'تاريخ الفاتورة إلزامي.',
            'invoice_date.date' => 'صيغة تاريخ الفاتورة غير صحيحة.',
            'payment_type.required' => 'طريقة الدفع إلزامية.',
            'payment_type.in' => 'طريقة الدفع المحددة غير صالحة.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في فاتورة المبيعات.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل في فاتورة المبيعات.',
            'items.*.product_id.required' => 'يرجى تحديد المنتج لكل بند.',
            'items.*.product_id.exists' => 'المنتج المحدد في أحد البنود غير صالح.',
            'items.*.quantity.required' => 'الكمية إلزامية لكل بند.',
            'items.*.quantity.gt' => 'يجب أن تكون كمية البيع أكبر من الصفر.',
            'items.*.unit_price.required' => 'سعر الوحدة إلزامي لكل بند.',
            'items.*.unit_price.min' => 'سعر الوحدة لا يمكن أن يكون سالباً.',
            'items.*.tax_percent.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%.',
        ];
    }
}
