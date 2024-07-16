<?php

namespace App\Http\Controllers\Backend\System;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\System\FileSystem\FileRequest;
use App\Http\Requests\Backend\System\GenTable\CreateRequest;
use App\Http\Requests\Backend\System\GenTable\GenRequest;
use App\Http\Requests\Backend\System\GenTable\IdRequest;
use App\Http\Requests\Backend\System\GenTable\ListRequest;
use App\Http\Requests\Backend\System\GenTable\UpdateRequest;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\System\DictData;
use App\Models\Backend\System\DictType;
use App\Models\Backend\System\GenTable;
use App\Services\Backend\GenService;
use App\Util\ArrayTool;
use App\Util\Gen;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GenTableController extends Controller
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

        $query = GenTable::query();

        if (isset($param['name']) && $param['name'] != '') {
            $query->like('name', $param['name']);
        }
        if (isset($param['comment']) && $param['comment'] != '') {
            $query->like('comment', $param['comment']);
        }
        if (isset($param['engine']) && $param['engine'] != '') {
            $query->like('engine', $param['engine']);
        }
        if (isset($param['charset']) && $param['charset'] != '') {
            $query->like('charset', $param['charset']);
        }
        if (isset($param['collation']) && $param['collation'] != '') {
            $query->like('collation', $param['collation']);
        }

        if (!empty($param['created_at'])) {
            $query->timeBetween('created_at', $param['created_at']);
        }
        if (!empty($param['updated_at'])) {
            $query->timeBetween('updated_at', $param['updated_at']);
        }
        if (!empty($param['sort']) && !empty($param['order'])) {
            $query->orderBy($param['sort'], order_direction($param['order']));
        } else {
            $query->orderByDesc('created_at');
        }

        $list = $query->paginate($param['limit']);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
        ]);
    }

    /**
     * 下拉
     *
     * @return Response
     */
    public function all(): Response
    {
        return $this->success([
            'data' => Gen::selectAll()
        ], __('message.common.search.success'));
    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $param = $request->validated();
        $info = GenTable::find($param['id']);
        if (!$info) {
            return $this->error(__('message.data_not_found'));
        }
        $info->load([
            'genTableColumns'
        ]);
        $info->pid = [0];
        return $this->success($info, __('message.common.search.success'));
    }

    /**
     * 詳情
     *
     * @return Response
     */
    public function columnConfig(): Response
    {
        $select = [
            Gen::SELECT_EQ,
            Gen::SELECT_NE,
            Gen::SELECT_GT,
            Gen::SELECT_GE,
            Gen::SELECT_LT,
            Gen::SELECT_LE,
            Gen::SELECT_LIKE,
            Gen::SELECT_BETWEEN
        ];
        $type = [
            Gen::TYPE_INPUT_TEXT,
            Gen::TYPE_INPUT_TEXTAREA,
            Gen::TYPE_SELECT,
            Gen::TYPE_RADIO,
            Gen::TYPE_DATE,
            Gen::TYPE_FILE,
            Gen::TYPE_IMAGE,
            Gen::TYPE_IMAGES,
            Gen::TYPE_EDITOR
        ];
        $dict = DictType::selectAll();
        $dictData = DictData::selectAll();
        $permission['id'] = 0;
        $permission['icon'] = 'el-icon-star-on';
        $permission['name'] = 'top';
        $permission['pid'] = 0;
        $permission['title'] = __('message.gen.top_nav');
        $children = Permission::query()->where('hidden', Constant::COMMON_IS_NO)
            ->select(['id', 'pid', 'name', 'title', 'icon', 'active_menu'])
            ->orderByDesc('sort')
            ->get()->toArray();
        $children = ArrayTool::setChildrenInParentNew($children);
        $permission['children'] = $children;
        $doctrineSchemaManager = DB::connection()->getDoctrineSchemaManager();
        try {
            $tables = array_values(array_filter(array_map(function (Table $table) use ($doctrineSchemaManager) {
                $db = [];
                $name = $table->getName();
                $db['name'] = $name;
                foreach ($table->getOptions() as $key => $option) {
                    $db[$key] = $option;
                }
                $db['info'] = Gen::getTableInfo($name);
                return $db;
            }, $doctrineSchemaManager->listTables())));
        } catch (Exception) {
            $tables = [];
        }
        return $this->success([
            'select' => $select,
            'type' => $type,
            'dict' => $dict,
            'dictData' => $dictData,
            'permission' => [$permission],
            'tables' => $tables
        ], __('message.common.search.success'));
    }

    /**
     * 創建
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $table = $request->post('table');
        foreach ($table as $item) {
            Gen::importTable($item);
        }
        return $this->success(msg: __('message.common.create.success'));
    }

    /**
     * 可導入的數據表
     * @return Response
     */
    public function importTable(): Response
    {
        return $this->success([
            'list' => Gen::getImportTableList()
        ], __('message.common.search.success'));
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $param = $request->validated();
        $model = GenTable::query()->find($param['id']);
        if (!$model) {
            return $this->error(__('message.data_not_found'));
        }

        $model->update($param);
        $model->genTableColumns()->delete();
        $model->genTableColumns()->insert($param['gen_table_columns']);
        return $this->success($model, __('message.common.update.success'));
    }

    /**
     * 刪除
     *
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $param = $request->validated();
        $item = GenTable::query()->find($param['id']);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }
        $item->delete();


        return $this->success(msg: __('message.common.delete.success'));
    }

    public function genOld(GenRequest $request): Response
    {
        $param = $request->validated();
        $pid = (array)$param['pid'];
        if (empty($pid)) {
            $pid = 0;
        } else {
            $pid = end($pid);
        }
        try {
            $path = Gen::gen($param['name'], $param['entity_name'], $pid, $param['comment']);
            return $this->success([
                'path' => $path
            ], __('message.common.create.success'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function gen(GenRequest $request): Response
    {
        $param = $request->all();
        $pid = (array)$param['pid'];
        if (empty($pid)) {
            $pid = 0;
        } else {
            $pid = end($pid);
        }
        try {
            $genService = new GenService($param['name'], $param['entity_name'], $pid, $param['comment']);

            $genService->init()->gen();

            // 如果是本地
            if (env('APP_ENV') === 'local') {
                if (!empty($param['copy_to_php'])) {
                    $genService->copyPhpTo();
                }
                if (!empty($param['copy_to_vue'])) {
                    $genService->copyVueTo($param['copy_to_vue']);
                }
            }
            $url = $genService->pack();

            return $this->success([
                'url' => $url
            ], __('message.common.create.success'));
        } catch (\Exception $e) {
            dd($e);
            return $this->error($e->getMessage());
        }
    }

    public function download(FileRequest $request): Response
    {
        $param = $request->validated();
        $file = $param['file'];
        if (file_exists($file)) {
            return response()->download($file, basename($file));
        }
        return $this->error(__('message.file.not_found'));
    }
}
