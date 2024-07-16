<?php


namespace App\Http\Requests\Backend\ChargingPiles;

use App\Constants\Constant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1'],
            'parking_lot_id' => ['required', 'integer'],
            'no' => ['required', 'string', ],
            'serial_number' => ['required', 'string', ],
            'stat' => ['required', 'in:0,1'],
            'toll' => ['required', 'integer', 'min:0'],
            'charging' => ['required', 'integer', 'min:0'],
            'preferential' => ['required', 'integer', 'min:0'],
            'power_id' => ['required', 'integer'],
            'specification_id' => ['required', 'integer'],
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
            'id' => '自增ID',
            'parking_lot_id' => '充電站ID',
            'no' => '充電樁編號',
            'machine_code' => '充電樁上的機器號',
            'stat' => '狀態',
            'toll' => '充電樁優惠收費',
            'charging' => '充電樁收費',
            'preferential' => '優惠扣除金額',
            'power_id' => '功率ID',
            'specification_id' => '規格ID',
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
