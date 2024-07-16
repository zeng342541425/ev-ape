<?php


namespace App\Http\Requests\Backend\DiningHotel;

use App\Constants\Constant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    /**
    * 表示驗證器是否應在第一個規則失敗時停止。
    *
    * @var bool
    */
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
    * 規則.
    *
    * @return array
    */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', ],
            'logo' => ['required', 'string', ],
            'address' => ['required', 'string', ],
            'starting_time' => ['required', ],
            'ending_time' => ['required', ],
            'cancel_days' => ['required', ],
            'status' => ['required', ],
            'introduce' => ['nullable', 'string', ],
            'things_to_know' => ['nullable', 'string', ],
            'notes' => ['required', 'string', ],
            'seat_info' => ['required', 'array', ],
            'sequencing' => ['required', 'min:0', 'max:1000000000'],
            // 'seat_info.type' => ['required', 'in:1,2', ],
            // 'seat_info.seats' => ['required', 'integer', ],
            // 'seat_info.charging' => ['required', 'integer', ],
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
            'name' => '餐旅名稱',
            'logo' => '餐旅logo',
            'address' => '餐旅地址',
            'starting_time' => '預約開放开始时间',
            'ending_time' => '預約開放结束时间',
            'cancel_days' => '可免費取消天數',
            'status' => '狀態',
            'introduce' => '餐廳介紹',
            'things_to_know' => '預約需知',
            'notes' => '預約成功後備註',
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
