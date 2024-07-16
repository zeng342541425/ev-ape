<?php


namespace App\Http\Requests\Backend\Banner;

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
            'name' => ['required', ],
            'image_url' => ['required', 'url', ],
            'sort' => ['required', 'min:0'],
            'starting_time' => ['required', 'date'],
            'ending_time' => ['required', 'date'],
            'status' => ['required', 'in:0,1'],
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
            'sort' => '排序',
            'name' => '名稱',
            'starting_time' => '上架時間',
            'ending_time' => '上架時間',
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
