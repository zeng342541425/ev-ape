<?php

namespace App\Http\Requests\Backend\System\ExceptionError;

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
                'created_at',
                'updated_at',
                'message',
                'uid',
                'id',
                'is_solve',
            ])],
            'is_solve' => ['nullable', rule_in_is()],
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
            'id' => __('message.exception.id'),
            'message' => __('message.exception.message'),
            'solve' => __('message.exception.solve'),
        ];
    }
}
