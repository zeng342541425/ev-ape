<?php

namespace App\Http\Requests\Backend\System\GenTable;

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
            'id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string'],
            'entity_name' => ['required', 'string'],
            'comment' => ['required', 'string'],
            'engine' => ['required', 'string'],
            'charset' => ['required', 'string'],
            'collation' => ['required', 'string'],
            'gen_table_columns' => ['required', 'array'],
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
            'id' => '自增ID',
            'name' => __('message.gen.name'),
            'comment' => __('message.gen.comment'),
            'engine' => __('message.gen.engine'),
            'charset' => __('message.gen.charset'),
            'collation' => __('message.gen.collation'),
            'gen_table_columns' => __('message.gen.gen_table_columns'),
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
