<?php

namespace App\Http\Requests\Frontend\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
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
            'parking_lot_id' => ['required', 'integer', 'min:1'],
            'longitude' => ['required', 'numeric'],
            'latitude' => ['required', 'numeric'],
            'trade_date' => ['required', 'date'],
            'starting_time' => ['required'],
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
