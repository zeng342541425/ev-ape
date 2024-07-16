<?php

namespace App\Http\Requests\Backend\System\GenTable;

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
            'sort' => ['nullable', 'string', Rule::in([
                'created_at', 'updated_at', 'name', 'engine', 'charset', 'collation', 'comment'
            ])],
            'name' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'engine' => ['nullable', 'string'],
            'charset' => ['nullable', 'string'],
            'collation' => ['nullable', 'string'],
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
            'engine' => __('message.gen.engine'),
            'charset' => __('message.gen.charset'),
            'collation' => __('message.gen.collation'),
            'created_at_start' => __('message.gen.created_at_start'),
            'created_at_end' => __('message.gen.created_at_end'),
            'updated_at_start' => __('message.gen.updated_at_start'),
            'updated_at_end' => __('message.gen.updated_at_end'),
        ];
    }
}
