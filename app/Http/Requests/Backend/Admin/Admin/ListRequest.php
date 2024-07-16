<?php

namespace App\Http\Requests\Backend\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRequest extends FormRequest
{

    protected $stopOnFirstFailure = true;

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
            'limit' => ['required', 'integer', 'min:1', 'max:50'],
            'name' => ['nullable', 'string', 'between:1,60',],
            'email' => ['nullable', 'string', 'between:1,60',],
            'status' => ['nullable', 'integer', rule_in_status()],
            'role_ids' => ['nullable', 'array',],
            'sort' => ['nullable', Rule::in([
                'created_at',
                'status',
                'updated_at',
                'name',
                'username',
                'email'
            ])],
            'order' => ['nullable', rule_in_order_direction()]
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
            'name' => __('message.admin.name'),
            'email' => __('validation.attributes.email'),
            'status' => __('message.admin.status'),
            'role_ids' => __('message.role.id'),
        ];
    }
}
