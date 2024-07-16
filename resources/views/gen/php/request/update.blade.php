{!! $phpStart !!}


namespace App\Http\Requests\Backend\{{ $className }};

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
            '{{ $primaryKey }}' => ['required', 'integer', 'min:1'],
@foreach( $rules as $key => $rule )
            '{{ $key }}' => [@foreach($rule as $val){!! $val !!}, @endforeach],
@endforeach
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
            '{{ $primaryKey }}' => '{{ $primaryKeyColumn->comment }}',
@foreach( $updateColumns as $column )
            '{{ $column->name }}' => '{{$column->comment}}',
@endforeach
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
