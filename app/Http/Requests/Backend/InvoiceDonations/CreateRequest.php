<?php


namespace App\Http\Requests\Backend\InvoiceDonations;

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
            'code' => ['required', 'string', ],
            'institution' => ['required', 'string', ],
            'used' => ['required', Rule::exists('dict_data', 'value')->where('dict_type_id', 2)->where('status', Constant::COMMON_STATUS_ENABLE), ],
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
            'code' => '捐贈碼',
            'institution' => '捐贈機構',
            'used' => '使用',
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
