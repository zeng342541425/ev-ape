<?php


namespace App\Http\Requests\Backend\Advertisement;

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
            'image_url' => ['required', 'string', ],
            'name' => ['required', 'string', ],
            'status' => ['required', 'in:0,1'],
            'link_type' => ['required', 'in:0,1,2'],
            'link_value' => ['nullable', 'string', ],
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
            'image_url' => '圖片',
            'name' => '名稱',
            'status' => '狀態',
            'link_type' => '連結類型；1:內部連結;2:外部連結',
            'link_value' => '連結值',
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
