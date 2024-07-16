<?php


namespace App\Http\Requests\Backend\User\User;

use Illuminate\Foundation\Http\FormRequest;

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
            'phone' => ['required', 'string', ],
            'email' => ['required', 'string', ],
            'password' => ['required', 'string', ],
            'status' => ['required', ],
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
            'name' => '姓名',
            'phone' => '手機號碼',
            'email' => '信箱',
            'password' => '密碼',
            'status' => '狀態',
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
