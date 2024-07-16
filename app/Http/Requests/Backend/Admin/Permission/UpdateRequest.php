<?php

namespace App\Http\Requests\Backend\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'id' => ['required', 'integer','min:1',],
            'pid' => ['required', 'integer', 'min:0',],
            'name' => ['required', 'string', 'between:2,60',],
            'title' => ['required', 'string', 'between:2,60',],
            'icon' => ['required', 'string', 'between:2,60',],
            'path' => ['required', 'string', 'between:2,60'],
            'component' => ['required', 'string', 'between:2,60',],
            'sort' => ['required', 'integer', 'min:0',],
            'hidden' => ['required', 'integer', rule_in_is(),],
            'active_menu' => ['nullable', 'string', 'between:2,60',],
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
            'pid' => __('message.permission.pid'),
            'name' => __('message.permission.name'),
            'title' => __('message.permission.title'),
            'icon' => __('message.permission.icon'),
            'path' => __('message.permission.path'),
            'component' => __('message.permission.component'),
            'guard_name' => __('message.permission.guard_name'),
            'sort' => __('message.permission.sort'),
            'hidden' => __('message.permission.hidden'),
            'active_menu' => __('message.permission.active_menu'),
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
