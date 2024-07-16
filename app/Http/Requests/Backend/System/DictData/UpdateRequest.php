<?php

namespace App\Http\Requests\Backend\System\DictData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'id' => ['required', 'integer', Rule::exists('dict_data')],
            'sort' => ['nullable', 'integer'],
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:100'],
            'list_class' => ['nullable', 'string', 'max:100'],
            'default' => ['nullable', 'integer', Rule::in([0, 1])],
            'status' => ['nullable', 'integer', Rule::in([0, 1])],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * 獲取驗證錯誤的自定義屬性。
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'sort' => __('message.dict_data.sort'),
            'label' => __('message.dict_data.label'),
            'value' => __('message.dict_data.value'),
            'list_class' => __('message.dict_data.list_class'),
            'default' => __('message.dict_data.default'),
            'status' => __('message.dict_data.status'),
            'remark' => __('message.dict_data.remark'),
        ];
    }

    /**
     * 獲取已定義驗證規則的錯誤消息。
     *
     * @return array
     */
    public function messages(): array
    {
        return [

        ];
    }
}
