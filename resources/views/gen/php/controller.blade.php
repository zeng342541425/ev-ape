{!! $phpStart !!}
@php
    use App\Util\Gen;
@endphp
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\{{ $className }}\CreateRequest;
use App\Http\Requests\Backend\{{ $className }}\ListRequest;
use App\Http\Requests\Backend\{{ $className }}\IdRequest;
use App\Http\Requests\Backend\{{ $className }}\UpdateRequest;
use App\Models\Common\{{ $className }};
use Symfony\Component\HttpFoundation\Response;

class {{ $className }}Controller extends Controller
{
    /**
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $query = {{ $className }}::query();

@foreach( $searchColumns as $column )
@if( $column->_query === Gen::SELECT_LIKE )
        if (isset($param['{{ $column->name }}']) && $param['{{ $column->name }}'] != '') {
            $query->like('{{ $column->name }}', $param['{{ $column->name }}']);
        }
@elseif( $column->_query === Gen::SELECT_BETWEEN )
        if (!empty($param['{{ $column->name }}'])) {
            $query->timeBetween('{{ $column->name }}', $param['{{ $column->name }}']);
        }
@else
        if (isset($param['{{ $column->name }}']) && $param['{{ $column->name }}'] != '') {
            $query->where('{{ $column->name }}', '{{ $column->_query }}', $param['{{ $column->name }}']);
        }
@endif
@endforeach

@if( $sortColumns->count() )
        if (!empty($param['sort']) && !empty($param['order'])) {
            $query->orderBy($param['sort'], order_direction($param['order']));
        }@if($timestamps) else {
            $query->orderByDesc('created_at');
        }@endif
@else
@if($timestamps)
        $query->orderByDesc('created_at');
@endif
@endif

        $list = $query->paginate($param['limit']);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 所有列表
     *
     * @return Response
     */
    public function all(): Response
    {

        $list = {{ $className }}::query()->get();

        return $this->success([
            'list' => $list
        ]);
    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('{{ $primaryKey }}');

        $item = {{ $className }}::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 創建
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $param = $request->only([
            @foreach( $createColumns as $column )'{{ $column->name }}', @endforeach

        ]);

@foreach( $createColumns as $column )
@if( $column->_unique )
        // 驗證 {{ $column->comment }} 唯一
        if({{ $className }}::query()->where('{{ $column->name }}', $param['{{ $column->name }}'])->first()) {
            return $this->error(__('validation.unique', ['attribute' => '{{ $column->name }}']));
        }
@endif
@endforeach


        $item = {{ $className }}::query()->create($param);

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $id = $request->post('{{ $primaryKey }}');
        $param = $request->only([
            @foreach( $updateColumns as $column )'{{ $column->name }}', @endforeach

        ]);

        $item = {{ $className }}::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

@foreach( $updateColumns as $column )
@if( $column->_unique )
        // 驗證 {{ $column->comment }} 唯一
        if($param['{{ $column->name }}'] != $item->{{ $column->name }} && {{ $className }}::query()->where('{{ $column->name }}', $param['{{ $column->name }}'])->first()) {
            return $this->error(__('validation.unique', ['attribute' => '{{ $column->name }}']));
        }
@endif
@endforeach


        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success(['item' => $item], __('message.common.update.success'));
    }

    /**
     * 刪除
     *
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $id = $request->post('{{ $primaryKey }}');

        $item = {{ $className }}::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
