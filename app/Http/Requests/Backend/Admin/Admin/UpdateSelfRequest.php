<?php

namespace App\Http\Requests\Backend\Admin\Admin;

use App\Models\Backend\Admin\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSelfRequest extends FormRequest
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
        /* @var Admin $admin */
        return [
            'name' => ['required', 'string', 'between:2,60'],
            // 'email' => ['required', 'string', 'email', 'between:2,60'],
            'password' => ['nullable', 'string', 'between:6,60'],
            'old_password' => ['nullable', 'string', 'between:6,60'],
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
