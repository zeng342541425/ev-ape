<?php

namespace App\Http\Requests\Backend\Admin\Permission;

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
            'limit' => ['required', 'integer', 'min:1'],
            'hidden' => ['nullable', 'integer', rule_in_is()],
            'sort' => ['nullable', Rule::in([
                'created_at',
                'sort',
                'updated_at',
            ])],
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
            'limit' => __('message.common.limit'),
            'order' => __('message.common.order'),
            'sort' => __('message.common.sort'),
            'name' => __('message.permission.name'),
            'title' => __('message.permission.title'),
            'path' => __('message.permission.path'),
            'guard_name' => __('message.permission.guard_name'),
        ];
    }
}
