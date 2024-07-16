<?php

namespace App\Http\Requests\Backend\System\DictData;

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
            'order' => ['nullable', 'string', rule_in_order_direction()],
            'sort' => ['nullable', 'string'],
            'dict_type_id' => ['required', 'integer'],
            'label' => ['nullable', 'string', 'max:100'],
            'value' => ['nullable', 'string', 'max:100'],
            'default' => ['nullable', 'integer', rule_in_is()],
            'status' => ['nullable', 'integer', rule_in_status()],
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
            'dict_type_id' => __('message.dict_data.dict_type_id'),
            'label' => __('message.dict_data.label'),
            'value' => __('message.dict_data.value'),
            'default' => __('message.dict_data.default'),
            'status' => __('message.dict_data.status'),
        ];
    }
}
