{!! $phpStart !!}

namespace App\Models\Common;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class {{ $className }} extends BaseModel
{
    use LogsActivity;

    /**
    * 表名
    *
    * @var string
    */
    protected $table = '{{ $tableName }}';

@if( $primaryKey != 'id' )

    /**
    * The primary key for the model.
    *
    * @var string
    */
    protected $primaryKey = '{{ $primaryKey }}';

@endif
@if( !empty( $cats ) )

    protected $casts = [
@foreach( $cats as $key => $val )
        '{{ $key }}' => {!! $val !!},
@endforeach
    ];

@endif

    /**
    * 指示模型是否主動維護時間戳.
    *
    * @var bool
    */
    public $timestamps = {{ $timestamps ? 'true' : 'false' }};

    /**
    * 不可批量賦值的屬性
    *
    * @var array
    */
    protected $guarded = [
        '{{ $primaryKey }}'
    ];

    /**
    * @return LogOptions
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('{{ $className }}')
        ->logFillable()
        ->logUnguarded();
    }

}

