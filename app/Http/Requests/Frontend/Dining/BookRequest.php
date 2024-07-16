<?php

namespace App\Http\Requests\Frontend\Dining;

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
            'seat_info_id' => ['required', 'integer', 'min:1'],
            'booking_date' => ['required', 'date'],
            'birthday' => ['required', 'date'],
            'number' => ['required', 'integer', 'min:1'],
            'card_id' => ['required', 'integer', 'min:1'],
            'invoice_id' => ['required', 'integer', 'min:1'],
            'invoice_type' => ['required', 'integer', 'in:1,2,3,4'],
            'gender' => ['required', 'integer', 'in:0,1,2'],
            'user_notes' => ['string'],
            'booking_name' => ['required', 'string'],
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
