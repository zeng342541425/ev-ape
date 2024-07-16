<?php

namespace App\Http\Requests\Backend\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DropRequest extends FormRequest
{
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'dragging' => ['required', 'integer'],
            'drop' => ['required', 'integer', 'different:dragging'],
            'type' => ['required', 'string', Rule::in(['before', 'after', 'inner']),],
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
            'dragging' => __('message.permission.permission'),
            'drop' => __('message.permission.permission'),
            'type' => __('message.permission.type'),
        ];
    }
}
