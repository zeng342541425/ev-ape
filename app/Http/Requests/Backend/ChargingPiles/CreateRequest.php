<?php


namespace App\Http\Requests\Backend\ChargingPiles;

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
            'parking_lot_id' => ['required', 'integer', 'min:1'],
            'no' => ['required', 'string', ],
            'serial_number' => ['required', 'string', ],
            'toll' => ['required', 'integer', 'min:0'],
            'charging' => ['required', 'integer', 'min:0'],
            'preferential' => ['required', 'integer', 'min:0'],
            'power_id' => ['required', 'integer', 'min:1'],
            'specification_id' => ['required', 'integer', 'min:1'],
            'stat' => ['required', 'integer', 'in:0,1'],
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
            'parking_lot_id' => '充電站',
            'no' => '充電樁編號',
            'machine_code' => '充電樁機器號',
            'toll' => '充電樁優惠收費',
            'charging' => '充電樁收費',
            'preferential' => '優惠扣除金額',
            'power_id' => '充電樁功率',
            'specification_id' => '充電樁規格',
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
