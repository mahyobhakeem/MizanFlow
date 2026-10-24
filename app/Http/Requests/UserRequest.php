<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $this->user()->can($target ? 'update' : 'create', $target ?? User::class);
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $userId = $target instanceof User ? $target->id : $target;
        $isCreate = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                $isCreate ? 'required' : 'nullable',
                'string',
                'min:8',
            ],
            'role' => ['required', Rule::in(['admin', 'manager', 'cashier'])],
            'default_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المستخدم إلزامي.',
            'username.required' => 'اسم تسجيل الدخول (Username) إلزامي.',
            'username.unique' => 'اسم تسجيل الدخول مستخدم مسبقاً.',
            'username.alpha_dash' => 'اسم المستخدم يجب أن يحتوي على حروف وأرقام وشرطات فقط دون مسافات.',
            'email.required' => 'البريد الإلكتروني إلزامي.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'email.email' => 'صيغة البريد الإلكتروني غير صالحة.',
            'password.required' => 'كلمة المرور إلزامية عند إنشاء حساب جديد.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 خانات.',
            'role.required' => 'يرجى تحديد دور وصلاحية المستخدم.',
            'role.in' => 'الدور المختار غير صالح.',
            'default_warehouse_id.exists' => 'المستودع الافتراضي المختار غير موجود.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
