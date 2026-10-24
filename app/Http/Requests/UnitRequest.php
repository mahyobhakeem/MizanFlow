<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->unit ? 'update' : 'create', $this->unit ?? Unit::class);
    }

    public function rules(): array
    {
        $unitId = $this->unit?->id ?? $this->route('unit')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')->ignore($unitId),
            ],
            'name' => ['required', 'string', 'max:50'],
            'allow_decimals' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز الوحدة إلزامي (مثال: PCS, KG).',
            'code.unique' => 'رمز الوحدة مستخدم مسبقاً.',
            'name.required' => 'اسم الوحدة إلزامي.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_decimals' => $this->boolean('allow_decimals', false),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
