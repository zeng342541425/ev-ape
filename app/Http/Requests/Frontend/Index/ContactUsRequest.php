<?php

namespace App\Http\Requests\Frontend\Index;

use Illuminate\Foundation\Http\FormRequest;

class ContactUsRequest extends FormRequest
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
            'company' => ['required', ],
            'full_name' => ['required', ],
            'job_titles' => ['required', ],
            'telephone' => ['required', ],
            'email' => ['required', 'email'],
            'demand' => ['required', 'integer', 'in:1,2,3,4'],
            'description' => ['string', 'max:10000'],
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
            'company' => '公司名稱',
            'full_name' => '聯絡人姓名',
            'job_titles' => '聯絡人職稱',
            'telephone' => '聯絡人電話',
            'email' => '信箱',
            'demand' => '您的需求',
            'description' => '需求簡述',
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
            'email.required' => '郵箱不能爲空',
            'email.email' => '請填寫正確郵箱'
        ];
    }
}
