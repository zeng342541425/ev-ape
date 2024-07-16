<?php

namespace App\Http\Requests\Frontend\User\My;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePwdRequest extends FormRequest
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
            'old_password' => ['required', 'string', 'between:6,12', 'regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,12}$/'],
            'password' => ['required', 'string', 'between:6,12', 'regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,12}$/'],
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
            'old_password' => '舊密碼',
            'password' => '新密碼',
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
