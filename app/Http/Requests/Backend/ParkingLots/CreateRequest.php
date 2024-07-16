<?php


namespace App\Http\Requests\Backend\ParkingLots;

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
            'no' => ['required', 'string', ],
            'parking_lot_name' => ['required', 'string', ],
            'region_id' => ['required', ],
            'village_id' => ['required', ],
            'address' => ['required', 'string', ],
            'status' => ['required', 'in:0,1'],
            'latitude' => ['required', ],
            'longitude' => ['required', ],
            'business_hours' => ['nullable', 'string', ],
            'parking_fee' => ['required', 'integer', 'min:0'],
            'notes' => ['string', ],
            'images' => ['required', 'array'],
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
            'no' => '充電站編號',
            'parking_lot_name' => '充電站名稱',
            'region_id' => '站點區域',
            'village_id' => '站點區域',
            'address' => '充電站地址',
            'status' => '状态',
            'latitude' => '緯度',
            'longitude' => '經度',
            'business_hours' => '營業時間',
            'parking_fee' => '停車費',
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
