<?php

namespace App\Http\Requests\Backend\Admin\Role;

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
            // 'name' => ['required', 'string', 'between:2,60'],
            'full_name' => ['required', 'string', 'between:2,60'],
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
            'name' => __('message.role.name'),
            'guard_name' => __('message.permission.guard_name'),
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
