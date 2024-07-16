<?php

namespace App\Http\Requests\Backend\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1'],
            'limit' => ['required', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in([
                'created_at',
                'updated_at',
            ])],
            'status' => ['nullable', 'integer', rule_in_status()],
            'order' => ['nullable', Rule::in(order_direction())]
        ];
    }

    /**
     * 獲取已定義驗證規則的錯誤消息。
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('message.role.name'),
            'guard_name' => __('message.permission.guard_name'),
        ];
    }
}
