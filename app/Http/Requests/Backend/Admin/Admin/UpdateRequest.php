<?php

namespace App\Http\Requests\Backend\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'between:2,60'],
            'username' => ['required', 'string', 'between:2,60'],
            // 'email' => ['nullable', 'string', 'email', 'between:2,60'],
            'password' => ['nullable', 'string', 'between:6,60'],
            'status' => ['required', 'integer', rule_in_status()],
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
            'id' => __('message.admin.id'),
            'name' => __('message.admin.name'),
            'email' => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
            'status' => __('message.admin.status'),
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
