<?php

namespace App\Services\Backend;

use App\Constants\Constant;
use App\Models\Backend\System\DictType;
use App\Models\Backend\System\GenTable;
use App\Util\Gen;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class GenService
{
    protected $table;
    protected $columns;
    protected $dictTypes;

    protected $tableName;
    protected $entityName;
    protected $permissionName;
    protected $permissionId;
    protected $className;
    protected $routeName;
    protected $primaryKey;

    protected $zipName = 'generator.zip';
    protected $phpPath = 'php';
    protected $vuePath = 'vue';
    protected $disk = 'codes';

    /**
     *
     * @param string $tableName
     * @param $entityName
     * @param int $permissionId
     * @param string|null $permissionName
     */
    public function __construct(string $tableName, $entityName, int $permissionId = 0, string $permissionName = null)
    {
        $this->tableName = $tableName;
        $this->entityName = $entityName;
        $this->permissionId = $permissionId;
        $this->permissionName = $permissionName;
    }

    /**
     * 初始化
     * @return $this
     * @throws Exception
     */
    public function init()
    {
        // 數據表
        $this->table = GenTable::name($this->tableName)->first();
        if (!$this->table) {
            throw new Exception("數據表不存在");
        }

        // 表字段
        $this->columns = $this->table->genTableColumns()->get();

        $this->initTable();
        $this->initColumnComment();
        $this->initName();
        $this->clearCodes();

        // 字典
        $this->dictTypes = $this->getDictTypes();

        return $this;
    }

    /**
     * 初始化表格
     * @return void
     * @throws Exception
     */
    public function initTable()
    {
        $this->primaryKey = $this->getPrimaryKey();
        if (!$this->primaryKey) {
            throw new Exception('數據表[主鍵自增]不存在, 暫不支持生成');
        }
    }

    /**
     * 初始化字段備註
     * @return void
     * @throws Exception
     */
    public function initColumnComment()
    {
        $this->columns->whereNull('comment')->each(function ($genTableColumn): void {
            if ($genTableColumn->name === $this->primaryKey) {
                $genTableColumn->comment = 'ID';
            } elseif ($genTableColumn->name === Model::CREATED_AT) {
                $genTableColumn->comment = '創建時間';
            } elseif ($genTableColumn->name === Model::UPDATED_AT) {
                $genTableColumn->comment = '更新時間';
            }
        });

        $unCommentColumns = $this->columns->whereNull('comment')->values();
        if ($unCommentColumns->isNotEmpty()) {
            $unCommentName = $unCommentColumns->pluck('name')->implode('|');
            throw new Exception("$unCommentName 備註不能為空");
        }

    }

    /**
     * 初始化名稱
     * @return void
     * @throws Exception
     */
    public function initName()
    {
        $this->className = Str::of($this->entityName)->studly()->toString();
        $this->routeName = Str::of($this->entityName)->camel()->toString();
        if (!$this->permissionName) {
            $this->permissionName = $this->table->comment;
        }
        if (!$this->permissionName) {
            throw new Exception('權限名稱不能為空');
        }
    }

    /**
     * Disk
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk()
    {
        return Storage::disk($this->disk);
    }

    /**
     * @return $this
     */
    public function gen()
    {
        $this->genPermissionSeeder();
        $this->genModel();
        $this->genRequest();
        $this->genController();
        $this->genRoute();

        $this->genApiJs();
        $this->genIndexVue();
        $this->genCreateVue();
        $this->genUpdateVue();

        return $this;
    }

    /**
     * 生成 菜單權限填充
     * @return void
     */
    public function genPermissionSeeder()
    {
        $path = "php/database/seeders/{$this->className}PermissionSeeder.php";
        $this->disk()->put($path, $this->renderPermissionSeeder());
    }

    /**
     * 渲染 菜單權限填充
     * @return string
     */
    public function renderPermissionSeeder()
    {
        return view('gen.php.permission-seeder')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'permissionId' => $this->permissionId,
            'permissionName' => $this->permissionName,
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 生成 路由
     * @return void
     */
    public function genRoute()
    {
        $path = "php/routes/admin.php";
        $this->disk()->put($path, $this->renderRoute());
    }

    /**
     * 渲染 路由
     * @return string
     */
    public function renderRoute()
    {
        return view('gen.php.route')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'permissionName' => $this->permissionName,
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 渲染 路由代碼
     * @return string
     */
    public function renderRouteCode()
    {
        return view('gen.php.route-code')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'permissionName' => $this->permissionName,
        ])->render();
    }

    /**
     * 生成 控制器
     * @return void
     */
    public function genController()
    {
        $path = "php/app/Http/Controllers/Backend/{$this->className}Controller.php";
        $this->disk()->put($path, $this->renderController());
    }

    /**
     * 渲染 控制器
     * @return string
     */
    public function renderController()
    {
        return view('gen.php.controller')->with([
            'className' => $this->className,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
            'searchColumns' => $this->getSearchColumns(),
            'createColumns' => $this->getCreateColumns(),
            'updateColumns' => $this->getUpdateColumns(),
            'sortColumns' => $this->getSortColumns($this->columns),
            'timestamps' => $this->getTimestamps(),
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 生成 模型
     * @return void
     */
    public function genModel()
    {
        $path = "php/app/Models/Common/{$this->className}.php";
        $this->disk()->put($path, $this->renderModel());
    }

    /**
     * 渲染 控制器
     * @return string
     */
    public function renderModel()
    {
        return view('gen.php.model')->with([
            'className' => $this->className,
            'tableName' => $this->tableName,
            'primaryKey' => $this->primaryKey,
            'cats' => $this->getModelCats($this->columns),
            'timestamps' => $this->getTimestamps(),
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 生成 模型
     * @return void
     */
    public function genRequest()
    {
        $this->disk()->put(
            "php/app/Http/Requests/Backend/{$this->className}/List" . "Request.php",
            $this->renderListRequest()
        );
        $this->disk()->put(
            "php/app/Http/Requests/Backend/{$this->className}/Id" . "Request.php",
            $this->renderIdRequest()
        );
        $this->disk()->put(
            "php/app/Http/Requests/Backend/{$this->className}/Create" . "Request.php",
            $this->renderCreateRequest()
        );
        $this->disk()->put(
            "php/app/Http/Requests/Backend/{$this->className}/Update" . "Request.php",
            $this->renderUpdateRequest()
        );
    }

    /**
     * 渲染 驗證器 - Id
     * @return string
     */
    public function renderIdRequest()
    {
        return view('gen.php.request.id')->with([
            'className' => $this->className,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 渲染 驗證器 - 創建
     * @return string
     */
    public function renderCreateRequest()
    {
        $createColumns = $this->getCreateColumns();
        return view('gen.php.request.create')->with([
            'className' => $this->className,
            'primaryKey' => $this->primaryKey,
            'rules' => $this->getColumnsRules($createColumns),
            'createColumns' => $createColumns,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 渲染 驗證器 - 更新
     * @return string
     */
    public function renderUpdateRequest()
    {
        $updateColumns = $this->getUpdateColumns();
        return view('gen.php.request.update')->with([
            'className' => $this->className,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
            'rules' => $this->getColumnsRules($updateColumns),
            'updateColumns' => $updateColumns,
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 渲染 驗證器 - 列表
     * @return string
     */
    public function renderListRequest()
    {
        $columns = $this->getUpdateColumns();
        return view('gen.php.request.list')->with([
            'className' => $this->className,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
            'updateColumns' => $columns,
            'sortColumns' => $this->getSortColumns($columns),
            'phpStart' => '<?php',
        ])->render();
    }

    /**
     * 生成 Vue - Api.js
     * @return void
     */
    public function genApiJs()
    {
        $this->disk()->put(
            "vue/src/api/{$this->routeName}Api.js",
            $this->renderApiJs()
        );
    }

    /**
     * 渲染 Vue - Api.js
     * @return string
     */
    public function renderApiJs()
    {
        return view('gen.vue.api-js')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
        ])->render();
    }


    /**
     * 生成 Vue - index.vue
     * @return void
     */
    public function genIndexVue()
    {
        $this->disk()->put(
            "vue/src/views/{$this->routeName}/index.vue",
            $this->renderIndexVue()
        );
    }

    /**
     * 渲染 Vue - index.vue
     * @return string
     */
    public function renderIndexVue()
    {
        return view('gen.vue.index')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'permissionName' => $this->permissionName,
            'searchColumns' => $this->getSearchColumns(),
            'listColumns' => $this->getListColumns(),
            'dictTypes' => $this->dictTypes,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
        ])->render();
    }

    /**
     * 生成 Vue - create.vue
     * @return void
     */
    public function genCreateVue()
    {
        $this->disk()->put(
            "vue/src/views/{$this->routeName}/create.vue",
            $this->renderCreateVue()
        );
    }

    /**
     * 渲染 Vue - create.vue
     * @return string
     */
    public function renderCreateVue()
    {
        $createColumns = $this->getCreateColumns();

        return view('gen.vue.create')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'createColumns' => $createColumns,
            'formVarList' => $this->getFormVarList($createColumns),
            'dictTypes' => $this->dictTypes,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
        ])->render();
    }

    /**
     * 生成 Vue - update.vue
     * @return void
     */
    public function genUpdateVue()
    {
        $this->disk()->put(
            "vue/src/views/{$this->routeName}/update.vue",
            $this->renderUpdateVue()
        );
    }

    /**
     * 渲染 Vue - update.vue
     * @return string
     */
    public function renderUpdateVue()
    {
        $updateColumns = $this->getCreateColumns();
        return view('gen.vue.update')->with([
            'className' => $this->className,
            'routeName' => $this->routeName,
            'updateColumns' => $updateColumns,
            'formVarList' => $this->getFormVarList($updateColumns),
            'dictTypes' => $this->dictTypes,
            'primaryKey' => $this->primaryKey,
            'primaryKeyColumn' => $this->getPrimaryKeyColumn(),
        ])->render();
    }

    /**
     * 獲取主鍵
     * @return mixed|null
     */
    public function getPrimaryKey()
    {
        return $this->getPrimaryKeyColumn()['name'] ?? null;
    }

    /**
     * 獲取主鍵字段
     * @return mixed
     */
    public function getPrimaryKeyColumn()
    {
        return $this->columns->where('primary', true)->where('autoincrement', true)->first();
    }

    /**
     * 獲取 模型是否主動維護時間戳
     * @return bool
     */
    public function getTimestamps()
    {
        return $this->columns->whereIn('name', [
                Model::CREATED_AT,
                Model::UPDATED_AT,
            ])->count() === 2;
    }

    /**
     * 獲取 查詢字段
     * @return Collection
     */
    public function getSearchColumns()
    {
        return $this->columns->where('_select', 1);
    }

    /**
     * 獲取 創建字段
     * @return Collection
     */
    public function getCreateColumns()
    {
        return $this->columns->where('_insert', 1)
            ->where('autoincrement', 0)
            ->where('primary', 0);
    }

    /**
     * 獲取 創建字段
     * @return Collection
     */
    public function getUpdateColumns()
    {
        return $this->columns->where('_update', 1)
            ->where('autoincrement', 0)
            ->where('primary', 0);
    }

    /**
     * 獲取 創建字段
     * @return Collection
     */
    public function getListColumns()
    {
        return $this->columns->where('_list', 1);
    }

    /**
     * 獲取 字典
     * @return DictType[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getDictTypes()
    {
        $dictTypeIds = $this->columns->pluck('dict_type_id')->unique()->filter()->toArray();
        return DictType::status(Constant::COMMON_STATUS_ENABLE)->whereIn('id', $dictTypeIds)->with([
            'dictData' => function ($query) {
                $query->where('status', Constant::COMMON_STATUS_ENABLE);
            }
        ])->get();
    }

    /**
     * 獲取 字典
     * @param $id
     * @return mixed
     */
    public function getDictType($id)
    {
        return $this->dictTypes->firstWhere('id', $id);
    }

    /**
     * 獲取排序字段
     * @param $columns
     * @return array
     */
    public function getSortColumns($columns)
    {
        return $columns->where('_sort', 1);
    }


    /**
     * 獲取模型 Cats
     * @param $columns
     * @return array
     */
    public function getModelCats($columns)
    {
        $cats = [];

        $columns->each(function ($column) use (&$cats) {
            if ($column->_show == Gen::TYPE_IMAGES) {
                $cats[$column->name] = "'array'";
            }
        });

        return $cats;
    }

    /**
     * 獲取 Form 變量
     * @param $columns
     * @return array
     */
    public function getFormVarList($columns)
    {
        $vars = [];

        $columns->each(function ($column) use (&$vars) {
            if ($column->_show == Gen::TYPE_IMAGES) {
                $vars[$column->name] = '[]';
            } elseif ($column->_validate === 'string') {
                $vars[$column->name] = "''";
            } else {
                $vars[$column->name] = "null";
            }
        });

        return $vars;
    }

    /**
     * 獲取字段規則
     * @param $columns
     * @return array
     */
    public function getColumnsRules($columns)
    {
        $rules = [];
        $columns->each(function ($column) use (&$rules) {
            $rule = [];
            if ($column->_required) { // 必填
                $rule[] = "'required'";
            } elseif (!$column->notnull) { // 可以為 null
                $rule[] = "'nullable'";
            }

            // 字典
            if ($column->dict_type_id) {
                $rule[] = "Rule::exists('dict_data', 'value')->where('dict_type_id', $column->dict_type_id)->where('status', Constant::COMMON_STATUS_ENABLE)";
                //                $dictType = $this->getDictType($column->dict_type_id);
                //                if ($dictType && $dictType->dictData->isNotEmpty()) {
                //                    $values = $dictType->dictData->map(function ($dictData) use ($column) {
                //                        if (in_array($column->type, ['boolean', 'bigint', 'integer'])) {
                //                            return $dictData->value;
                //                        }
                //                        return "'{$dictData->value}'";
                //                    })->join(", ");
                //                    $rule[] = "Rule::in([" . $values . "])";
                //                }
            } elseif ($column->_show == Gen::TYPE_IMAGES) {
                $rule[] = "'array'";
            } elseif ($column->_validate === 'string') {
                $rule[] = "'{$column->_validate}'";
            }

            if ($rule) {
                $rules[$column->name] = $rule;
            }
        });
        return $rules;
    }

    /**
     * 拷貝前端到
     * @param $toPath
     * @return bool
     */
    public function copyVueTo($toPath)
    {
        $disConfig = config("filesystems.disks.{$this->disk}");
        $diskPath = $disConfig['root'];
        $vuePath = $diskPath . '/vue';
        return File::copyDirectory($vuePath, $toPath);
    }

    /**
     * 拷貝后端到
     * @return bool
     */
    public function copyPhpTo()
    {
        $disConfig = config("filesystems.disks.{$this->disk}");
        $diskPath = $disConfig['root'];

        $appPath = $diskPath . '/php/app';
        File::copyDirectory($appPath, base_path('app'));

        $seederPath = $diskPath . "/php/database/seeders/{$this->className}PermissionSeeder.php";
        File::copy($seederPath, base_path("database/seeders/{$this->className}PermissionSeeder.php"));

        $addRouteContent = $this->renderRouteCode();

        $routePath = base_path('routes/' . $this->routeName . '.php');
        $routeContent = File::get($routePath);

        preg_match('/( +?)(\/\/AutoFillRoute)/', $routeContent, $authFillRoute);
        if (!empty($authFillRoute)) {
            $addRouteContent = preg_replace('/(.+)/', $authFillRoute[1] . '$1', $addRouteContent);
            $addRouteContent = "\n\n" . $addRouteContent . "\n\n\n";
            $routeContent = preg_replace('/(( +?)(\/\/AutoFillRoute))/', $addRouteContent . '$1', $routeContent);
            File::put($routePath, $routeContent);
        }

        return true;
    }


    /**
     * 設置 壓縮包名稱
     * @param $name
     * @return $this
     */
    public function setZipName($name = null)
    {
        if (!isset($name)) {
            $name = "generator-{$this->entityName}-" . date('YmdHis') . ".zip";
        }
        $this->zipName = $name;
        return $this;
    }

    /**
     * 打包
     * @return string
     */
    public function pack()
    {
        $this->setZipName();
        $disConfig = config("filesystems.disks.{$this->disk}");
        $diskPath = $disConfig['root'];
        $zip = new ZipArchive();
        $zipPath = $diskPath . '/' . $this->zipName;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);   //打開壓縮包
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($diskPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if (basename($filePath) !== '.gitignore') {
                    $relativePath = substr($filePath, strlen($diskPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        $this->clearCodeFiles();
        return $disConfig['url'] . '/' . $this->zipName;
    }

    /**
     *  清除代碼文件
     * @return void
     */
    public function clearCodeFiles()
    {
        $this->disk()->deleteDirectory($this->phpPath);
        $this->disk()->deleteDirectory($this->vuePath);
    }

    /**
     *  清除代碼文件
     * @return void
     */
    public function clearCodes()
    {
        $this->disk()->deleteDirectory('/');
    }
}
