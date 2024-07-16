<?php


namespace App\Http\Requests\Backend\Faults;

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
            'status' => ['required', ],
            'category_name' => ['nullable', 'string', ],
            'repaired_at' => ['nullable', ],
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
            'pile_no' => '充電樁編號',
            'parking_lot_id' => '充電站ID',
            'user_id' => '用戶ID',
            'description' => '問題描述',
            'status' => '狀態',
            'category_id' => '問題類型ID',
            'category_name' => '問題類型',
            'repaired_at' => '修復完成時間',
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
