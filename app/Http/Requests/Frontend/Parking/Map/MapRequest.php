<?php

namespace App\Http\Requests\Frontend\Parking\Map;

use Illuminate\Foundation\Http\FormRequest;

class MapRequest extends FormRequest
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
            'longitude' => ['numeric'],
            'latitude' => ['numeric'],
            'favorite' => ['numeric', 'in:0,1'],
            'power_id' => ['integer', 'min:0'],
            'specification_id' => ['integer', 'min:0'],
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
            'longitude' => __('validation.attributes.longitude'),
            'latitude' => __('validation.attributes.latitude'),
            'favorite' => __('validation.attributes.favorite'),
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
