<?php

namespace App\Http\Requests\Backend\System\DictType;

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
            'id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:100'],
            'status' => ['required', 'integer', rule_in_status()],
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
            'name' => __('message.dict_type.name'),
            'type' => __('message.dict_type.type'),
            'status' => __('message.dict_type.status'),
            'remark' => __('message.dict_type.remark'),
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
