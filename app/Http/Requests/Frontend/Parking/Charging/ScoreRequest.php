<?php

namespace App\Http\Requests\Frontend\Parking\Charging;

use Illuminate\Foundation\Http\FormRequest;

class ScoreRequest extends FormRequest
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
            'order_id' => ['required'],
            'star' => ['required', 'in:1,2,3,4,5'],
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
            // 'no' => __('validation.attributes.order_id'),
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
