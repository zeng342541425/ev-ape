<?php

namespace App\Http\Requests\Frontend\Parking\Charging;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentCancelRequest extends FormRequest
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
            'appointment_id' => ['required', 'integer', 'min:1'],
            'reason_id' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'url'],
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
            // 'no' => __('validation.attributes.pile.no'),
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
