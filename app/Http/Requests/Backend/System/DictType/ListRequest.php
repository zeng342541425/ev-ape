<?php

namespace App\Http\Requests\Backend\System\DictType;

use Illuminate\Foundation\Http\FormRequest;

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
            'order' => ['nullable', 'string', rule_in_order_direction()],
            'sort' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', 'integer'],
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
            'name' => __('message.dict_type.name'),
            'type' => __('message.dict_type.type'),
            'status' => __('message.dict_type.status'),
        ];
    }
}
