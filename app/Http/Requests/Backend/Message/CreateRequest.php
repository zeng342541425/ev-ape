<?php


namespace App\Http\Requests\Backend\Message;

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
            'title' => ['required', 'string', ],
            'published_at' => ['nullable', 'date'],
            // 'sort' => ['required', ],
            // 'status' => ['required', ],
            'content' => ['required', 'string', ],
            'brief_introduction' => ['required', 'string', ],
            'type' => ['required', 'in:1,2,4' ],
            'send_type' => ['required', 'in:1,2' ],
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
            'title' => '標題',
            'published_at' => '發佈時間',
            'sort' => '排序',
            'status' => '狀態',
            'content' => '内容',
            'brief_introduction' => '簡介',
            'type' => '發佈位置',
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
