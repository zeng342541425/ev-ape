<?php

namespace App\Http\Requests\Backend\System\ExceptionError;

use Illuminate\Foundation\Http\FormRequest;

class FileRequest extends FormRequest
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
            'file' => ['required', 'string']
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
            'file' => __('message.exception.file'),
        ];
    }
}
