<?php


namespace App\Http\Requests\Backend\FirebasePush;

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
            'type' => ['required', 'in:1,2'],
            'title' => ['required', 'string', ],
            'content' => ['required', 'string', ],
            'send_time' => ['nullable', 'date'],
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
            'send_type' => '發送類型',
            'type' => '發送類型',
            'title' => '推播標題',
            'content' => '內容',
            'send_time' => '發送時間',
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
