<?php

namespace App\Http\Requests\Backend\Admin\ActivityLog;

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
            'order' => ['nullable', 'string', rule_in_order_direction()],
            'sort' => ['nullable', 'string', Rule::in([
                'created_at'
            ])],
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
            'log_name' => __('message.activity.log_name'),
            'description' => __('message.activity.description'),
            'subject_id' => __('message.activity.subject_id'),
            'subject_type' => __('message.activity.subject_type'),
            'causer_id' => __('message.activity.causer_id'),
            'causer_type' => __('message.activity.causer_type'),
            'properties' => __('message.activity.properties'),
        ];
    }
}
