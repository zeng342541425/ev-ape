<?php

namespace App\Http\Requests\Backend\System\GenTable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'entity_name' => ['required', 'string'],
            'comment' => ['required', 'string'],
            'pid' => ['nullable', 'array'],
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
            'name' => __('message.gen.name'),
            'comment' => __('message.gen.comment'),
            'pid' => __('message.gen.pid'),
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
