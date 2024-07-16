<?php

namespace App\Http\Requests\Backend\System\FileSystem;

use App\Rules\ContinuousCharacter;
use App\Rules\Directory\ParentDirectory;
use App\Rules\EmojiChar;
use Illuminate\Foundation\Http\FormRequest;

class makeDirectoryRequest extends FormRequest
{
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'directory' => [
                'required', 'string', 'between:1,60', new EmojiChar, new ParentDirectory, new ContinuousCharacter
            ],
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
            'directory' => __('message.file.directory'),
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
