<?php

namespace App\Http\Requests\Frontend\User\Register;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'between:2,60'],
            'phone' => ['required', 'string', 'between:9,10'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'between:6,60', 'regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,12}$/'],
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
            'name' => '姓名',
            'password' => '密碼',
            'phone' => '手機號碼',
            'code' => '驗證碼',
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
