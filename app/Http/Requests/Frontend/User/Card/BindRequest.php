<?php

namespace App\Http\Requests\Frontend\User\Card;

use Illuminate\Foundation\Http\FormRequest;

class BindRequest extends FormRequest
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
            'prime' => ['required'],
            // 'cardholder.phone_number' => ['required'],
            // 'cardholder.name' => ['required'],
            // 'cardholder.email' => ['required'],
            // 'amount' => ['required', 'numeric'],
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
            'prime' => __('validation.attributes.prime'),
            'cardholder' => __('validation.attributes.cardholder'),
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
