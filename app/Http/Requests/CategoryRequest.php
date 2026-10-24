<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->category ? 'update' : 'create', $this->category ?? Category::class);
    }

    public function rules(): array
    {
        $categoryId = $this->category?->id ?? $this->route('category')?->id;

        return [
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                // Cannot be parent of itself
                $categoryId ? Rule::notIn([$categoryId]) : 'nullable',
            ],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('categories', 'code')->ignore($categoryId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز التصنيف إلزامي.',
            'code.unique' => 'رمز التصنيف مستخدم مسبقاً.',
            'name.required' => 'اسم التصنيف إلزامي.',
            'parent_id.exists' => 'التصنيف الرئيسي المختار غير موجود.',
            'parent_id.not_in' => 'لا يمكن اختيار التصنيف ليكون رئيساً لنفسه.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
