<?php

namespace App\Http\Requests\Frontend\User\My;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInfoRequest extends FormRequest
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
            'gender' => ['nullable', 'in:0,1,2'],
            'birthday' => ['nullable', 'date'],
            'educate' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer'],
            'village_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
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
            'gender' => '性別',
            'birthday' => '生日',
            'educate' => '教育程度',
            'address' => '通訊地址',
            'region_id' => '通訊地址',
            'village_id' => '通訊地址',
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
