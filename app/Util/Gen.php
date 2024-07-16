<?php

namespace App\Util;

use App\Models\Backend\System\GenTable;
use App\Models\Backend\System\GenTableColumn;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use ZipArchive;

class Gen
{
    const SELECT_EQ = '=';
    const SELECT_NE = '!=';
    const SELECT_GT = '>';
    const SELECT_GE = '>=';
    const SELECT_LT = '<';
    const SELECT_LE = '<=';
    const SELECT_LIKE = 'LIKE';
    const SELECT_BETWEEN = 'BETWEEN';

    const TYPE_INPUT_TEXT = 'text';
    const TYPE_INPUT_TEXTAREA = 'textarea';
    const TYPE_SELECT = 'select';
    const TYPE_RADIO = 'radio';
    const TYPE_DATE = 'date';
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';
    const TYPE_IMAGES = 'images';
    const TYPE_EDITOR = 'editor';

    /**
     * 可導入的數據表
     * @return array
     */
    public static function getImportTableList(): array
    {
        $tables = self::getTableList();
        $alreadyImportTable = GenTable::query()->pluck('name')->toArray();

        // 排除系統數據表
        $alreadyImportTable = array_merge($alreadyImportTable, [
            "activity_log",
            "admins",
            "dict_data",
            "dict_types",
            "exception_errors",
            "failed_jobs",
            "gen_table_columns",
            "gen_tables",
            "migrations",
            "model_has_permissions",
            "model_has_roles",
            "notifications",
            "permissions",
            "personal_access_tokens",
            "role_has_permissions",
            "roles",
            "card_requests",
            "order_notify_requests",
            "order_payment_requests",
            "regions",
            "appointment_cancellations",
            "favorites",
            "privacy",
            "invoices",
            "user_credit_cards",
        ]);
        return array_values(array_filter($tables, function (array $table) use ($alreadyImportTable) {
            return !in_array($table['name'], $alreadyImportTable);
        }));
    }

    /**
     * 獲取數據庫表
     *
     * @return array<string, mixed>
     */
    public static function getTableList(): array
    {
        $doctrineSchemaManager = DB::connection()->getDoctrineSchemaManager();
        $tables = [];
        try {
            $tables = $doctrineSchemaManager->listTables();
        } catch (Exception) {
        }

        return array_values(array_filter(array_map(function (Table $table) {
            $db = [];
            try {
                $table->getPrimaryKeyColumns();
                $name = $table->getName();
                $db['name'] = $name;
                foreach ($table->getOptions() as $key => $option) {
                    $db[$key] = $option;
                }
            } catch (Exception) {

            }
            return $db;
        }, $tables)));
    }

    /**
     * 獲取數據庫詳情
     *
     * @param string $table
     * @return array
     */
    public static function getTableInfo(string $table): array
    {
        $doctrineSchemaManager = DB::connection()->getDoctrineSchemaManager();
        try {
            $exist = $doctrineSchemaManager->tablesExist($table);
            if ($exist) {
                $table = $doctrineSchemaManager->listTableDetails($table);
                $info['name'] = $table->getName();
                foreach ($table->getOptions() as $key => $option) {
                    $info[$key] = $option;
                }

                $primaryKeyColumns = array_keys($table->getPrimaryKeyColumns());
                $info['columns'] = array_values(array_map(function (Column $column) use ($primaryKeyColumns) {
                    $type = $column->getType()->getName();
                    $c = $column->toArray();
                    $c['type'] = $type;
                    $c['primary'] = in_array($column->getName(), $primaryKeyColumns);
                    return $c;
                }, $table->getColumns()));
                $info['indexes'] = array_values(array_map(function (Index $index) {
                    $i['name'] = $index->getName();
                    $i['columns'] = $index->getColumns();
                    return $i;
                }, $table->getIndexes()));
                $info['foreign_keys'] = array_values(array_map(function (ForeignKeyConstraint $constraint) {
                    $c['name'] = $constraint->getName();
                    $c['local_columns'] = $constraint->getUnquotedLocalColumns();
                    $c['unqualified_foreign_table_name'] = $constraint->getUnqualifiedForeignTableName();
                    $c['unqualified_foreign_columns'] = $constraint->getUnquotedForeignColumns();
                    $c['on_delete'] = $constraint->getOption('onDelete');
                    $c['on_update'] = $constraint->getOption('onUpdate');
                    return $c;
                }, $table->getForeignKeys()));
            } else {
                $info['exception'] = 'table does not exist';
            }
        } catch (Exception $e) {
            $info['exception'] = $e->getMessage();
        }
        return $info;
    }

    public static function getTableConfig(string $table): array
    {
        $info = Gen::getTableInfo($table);
        if (isset($info['exception'])) {
            return $info;
        }

        $columns = $info['columns'];

        $uniques = array_values(array_map(function (array $column) {
            return $column['columns'][0];
        }, array_filter($info['indexes'], function (array $index) {
            return count($index['columns']) === 1 && Str::of($index['name'])->endsWith('_unique');
        })));

        $foreignKeys = array_values(array_map(function (array $foreign) {
            return [
                'local_column' => $foreign['local_columns'][0],
                '_foreign' => true,
                '_foreign_table' => $foreign['unqualified_foreign_table_name'],
                '_foreign_column' => $foreign['unqualified_foreign_columns'][0],
            ];
        }, array_filter($info['foreign_keys'], function (array $index) {
            return count($index['local_columns']) === 1 && count($index['unqualified_foreign_columns']) === 1;
        })));

        $columnsConfigData = array_map(function (array $column) use ($uniques, $foreignKeys) {

            $foreign = array_values(array_filter($foreignKeys, function (array $fk) use ($column) {
                return $fk['local_column'] === $column['name'];
            }));

            $insert = true;
            $update = true;
            $list = true;
            $select = true;
            // 如果是自增主鍵
            if ($column['primary'] && $column['autoincrement']) {
                $insert = false;
                $list = false;
                $select = false;
            }

            // 如果是時間
            if (in_array($column['name'], [Model::CREATED_AT, Model::UPDATED_AT])) {
                $insert = false;
                $update = false;
            }

            $query = Gen::query($column['type']);
            $validate = Gen::validate($column['type']);
            $show = Gen::show($column['name']);
            // 外鍵使用select
            if (count($foreign) === 1) {
                $show = Gen::TYPE_SELECT;
            }

            $column['_insert'] = $insert;
            $column['_update'] = $update;
            $column['_list'] = $list;
            $column['_select'] = $select;
            $column['_query'] = $query;
            $column['_required'] = $column['notnull'];
            $column['_show'] = $show;
            $column['_sort'] = false;
            $column['_validate'] = $validate;
            $column['dict_type_id'] = null;
            $column['_unique'] = in_array($column['name'], $uniques);

            $column['_foreign'] = false;
            $column['_foreign_table'] = null;
            $column['_foreign_column'] = null;
            $column['_foreign_show'] = null;

            if (count($foreign) === 1) {
                $column['_foreign'] = true;
                $column['_foreign_table'] = $foreign[0]['_foreign_table'];
                $column['_foreign_column'] = $foreign[0]['_foreign_column'];
                $info = Gen::getTableInfo($foreign[0]['_foreign_table']);
                $foreignColumn = array_values(array_map(function (array $column): string {
                    return $column['name'];
                }, array_filter($info['columns'], function (array $column) use ($foreign): bool {
                    return $column['name'] !== $foreign[0]['_foreign_column'];
                })));
                if (count($foreignColumn) > 0) {
                    $column['_foreign_show'] = $foreignColumn[0];
                }
            }

            return $column;
        }, $columns);

        $info['columns'] = $columnsConfigData;

        return $info;
    }

    /**
     * 驗證方式
     *
     * @param string $type
     * @return string
     */
    public static function validate(string $type): string
    {
        $types = [
            'integer' => [
                'integer', 'bigint'
            ],
            'string' => [
                'string', 'text'
            ],
            'numeric' => [
                'decimal', 'float'
            ],
            'date' => [
                'datetime', 'date'
            ],
            'boolean' => [
                'boolean'
            ]
        ];

        $r = 'string';
        foreach ($types as $select => $array) {
            if (in_array($type, $array)) {
                $r = $select;
                break;
            }
        }

        return $r;
    }

    /**
     * 查詢方式
     *
     * @param string $type
     * @return string
     */
    public static function query(string $type): string
    {
        $types = [
            Gen::SELECT_EQ => [
                'integer', 'boolean', 'bigint'
            ],
            Gen::SELECT_LIKE => [
                'string', 'text'
            ],
            Gen::SELECT_BETWEEN => [
                'datetime', 'time', 'date'
            ]
        ];

        $r = Gen::SELECT_EQ;
        foreach ($types as $select => $array) {
            if (in_array($type, $array)) {
                $r = $select;
                break;
            }
        }

        return $r;
    }

    /**
     * 顯示類型
     *
     * @param string $name
     * @return string
     */
    public static function show(string $name): string
    {
        $types = [
            Gen::TYPE_SELECT => [
                'sex', 'type'
            ],
            Gen::TYPE_RADIO => [
                'status'
            ],
            Gen::TYPE_DATE => [
                '_at', '_time'
            ],
            Gen::TYPE_FILE => [
                'file'
            ],
            Gen::TYPE_IMAGE => [
                'pic', 'image'
            ]
        ];

        $r = Gen::TYPE_INPUT_TEXT;
        foreach ($types as $select => $array) {
            foreach ($array as $item) {
                if (($name === $item) || Str::of($name)->endsWith($item)) {
                    $r = $select;
                    break;
                }
            }
        }

        return $r;
    }

    /**
     * @return string[][][]
     */
    public static function columnMethodAndType(): array
    {
        return [
            'methods' => [
                ['name' => '等於', 'value' => Gen::SELECT_EQ],
                ['name' => '不等於', 'value' => Gen::SELECT_NE],
                ['name' => '大於', 'value' => Gen::SELECT_GT],
                ['name' => '大於等於', 'value' => Gen::SELECT_GE],
                ['name' => '小於', 'value' => Gen::SELECT_LT],
                ['name' => '小於等於', 'value' => Gen::SELECT_LE],
                ['name' => '包含', 'value' => Gen::SELECT_LIKE],
                ['name' => '介於', 'value' => Gen::SELECT_BETWEEN],
            ],
            'types' => [
                ['name' => '文本框', 'value' => Gen::TYPE_INPUT_TEXT],
                ['name' => '文本域', 'value' => Gen::TYPE_INPUT_TEXTAREA],
                ['name' => '下拉框', 'value' => Gen::TYPE_SELECT],
                ['name' => '單選框', 'value' => Gen::TYPE_RADIO],
                ['name' => '日期控件', 'value' => Gen::TYPE_DATE],
                ['name' => '上傳文件控件', 'value' => Gen::TYPE_FILE],
                ['name' => '上傳圖片控件', 'value' => Gen::TYPE_IMAGE],
                ['name' => '富文本', 'value' => Gen::TYPE_EDITOR],
            ]
        ];
    }

    /**
     * 列表
     *
     * @return Collection
     */
    public static function selectAll(): Collection
    {
        return GenTable::select([
            'gen_tables.name', 'gen_tables.comment', 'gen_tables.engine', 'gen_tables.charset', 'gen_tables.collation', 'gen_tables.created_at', 'gen_tables.updated_at', 'gen_tables.id'
        ])->get();
    }

    /**
     * @param string $tableName
     * @return bool
     * @throws Throwable
     */
    public static function importTable(string $tableName): bool
    {
        $config = Gen::getTableConfig($tableName);
        if (!empty($config['exception'])) {
            throw new Exception($config['exception']);
        }
        DB::beginTransaction();
        try {
            GenTable::name($tableName)->delete();
            $table = GenTable::query()->create([
                'name' => $config['name'],
                'entity_name' => $config['name'],
                'comment' => $config['comment'],
                'engine' => $config['engine'],
                'charset' => $config['charset'],
                'collation' => $config['collation'],
            ]);
            $columns = array_map(function (array $column) use ($table): array {
                unset($column['columnDefinition']);
                unset($column['fixed']);
                unset($column['length']);
                unset($column['charset']);
                unset($column['collation']);
                $column['gen_table_id'] = $table->id;
                $column['created_at'] = now();
                $column['updated_at'] = now();
                if ($column['comment'] === '' || $column['comment'] === null) {
                    if ($column['name'] === 'id') {
                        $column['comment'] = '自增ID';
                    } elseif ($column['name'] === Model::CREATED_AT) {
                        $column['comment'] = '創建時間';
                    } elseif ($column['name'] === Model::UPDATED_AT) {
                        $column['comment'] = '更新時間';
                    }
                }
                return $column;
            }, $config['columns']);
            GenTableColumn::query()->insert($columns);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        return true;
    }

    /**
     * 生成代碼
     * @param string $tableName
     * @param $entityName
     * @param int $permissionId
     * @param string|null $permissionName
     * @return string
     * @throws Exception
     */
    public static function gen(string $tableName, $entityName, int $permissionId = 0, string $permissionName = null): string
    {
        $table = GenTable::name($tableName)->first();
        $columns = $table->genTableColumns()->get();

        $columns->whereNull('comment')->each(function (GenTableColumn $genTableColumn): void {
            if ($genTableColumn->name === 'id') {
                $genTableColumn->comment = '自增ID';
            } elseif ($genTableColumn->name === Model::CREATED_AT) {
                $genTableColumn->comment = '創建時間';
            } elseif ($genTableColumn->name === Model::UPDATED_AT) {
                $genTableColumn->comment = '更新時間';
            }
        });
        $unCommentColumns = $columns->whereNull('comment')->values();
        if ($unCommentColumns->isNotEmpty()) {
            $unCommentName = $unCommentColumns->pluck('name')->implode('|');
            throw new Exception("$unCommentName 備註不能為空");
        }

        $name = $table->name;
        $className = Str::of($entityName)->singular()->studly()->toString();
        $singular = Str::of($name)->singular()->camel()->toString();
        $snake = Str::of($name)->plural()->camel()->toString();
        if (!$permissionName) {
            $permissionName = $table->comment;
        }
        if (!$permissionName) {
            throw new Exception('權限名稱不能為空');
        }

        Storage::disk('codes')->deleteDirectory('php');
        Storage::disk('codes')->deleteDirectory('vue');
        Storage::disk('codes')->delete('generator.zip');

        // 菜單權限填充
        $permissionSeeder = str_replace([
            '{{className}}', '{{permissionId}}', '{{permissionName}}', '{{singular}}', '{{snake}}'
        ], [
            $className, $permissionId, $permissionName, $singular, $snake
        ], Gen::getStub('PermissionSeeder'));
        $path = "php/database/seeders/{$className}PermissionSeeder.php";
        Storage::disk('codes')->put($path, $permissionSeeder);


        // 路由
        $api = str_replace([
            '{{className}}', '{{permissionName}}', '{{singular}}', '{{snake}}'
        ], [
            $className, $permissionName, $singular, $snake
        ], Gen::getStub('Route'));
        $path = "php/routes/admin.php";
        Storage::disk('codes')->put($path, $api);

        // 控制器
        $controller = str_replace([
            '{{className}}'
        ], [
            $className
        ], Gen::getStub('Controller'));
        $path = "php/app/Http/Controllers/Backend/{$className}Controller.php";
        Storage::disk('codes')->put($path, $controller);

        $primaries = $columns->where('primary', '=', true)
            ->where('autoincrement', '=', true);
        if ($primaries->count() !== 1) {
            throw new Exception('數據表[主鍵自增]字段大於2或不存在， 暫不支持生成');
        }

        /* @var GenTableColumn $primary */
        $primary = $primaries->first();
        $idRequest = str_replace([
            '{{className}}', '{{tableName}}', '{{id}}', '{{validate}}', '{{message}}'
        ], [
            $className, $tableName, $primary->name, $primary->_validate, $primary->comment
        ], Gen::getStub('Request/Id'.'Request'));
        $path = "php/app/Http/Requests/Admin/{$className}/Id"."Request.php";
        Storage::disk('codes')->put($path, $idRequest);

        $selectColumns = $columns->where('_select', '=', true)->values();
        $selectDateColumns = $selectColumns->where('_validate', 'date')->values();
        $selectColumns = $selectColumns->reject(function (GenTableColumn $column) use ($selectDateColumns): bool {
            return in_array($column->name, $selectDateColumns->pluck('name')->toArray());
        });
        $selectDateColumns = $selectDateColumns->map(function (GenTableColumn $column) {
            $columnStart = clone $column;
            $columnEnd = clone $column;
            $columnStart->name = $column->name . '_start';
            $columnEnd->name = $column->name . '_end';
            $columnStart->comment = $column->comment . '開始';
            $columnEnd->comment = $column->comment . '結束';
            return [$columnStart, $columnEnd];
        })->collapse();
        $selectColumns = $selectColumns->mergeRecursive($selectDateColumns);
        $selectRules = $selectColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => ['nullable', '%s'],", $column->name, $column->_validate);
        })->implode("\n            ");
        $selectAttributes = $selectColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => '%s',", $column->name, $column->comment);
        })->implode("\n            ");
        $selectRequest = str_replace([
            '{{className}}', '{{rules}}', '{{attributes}}'
        ], [
            $className, $selectRules, $selectAttributes
        ], Gen::getStub('Request/ListRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/List"."Request.php";
        Storage::disk('codes')->put($path, $selectRequest);

        $insertColumns = $columns->where('primary', '=', false)
            ->where('autoincrement', '=', false)
            ->where('_insert', '=', true)
            ->values();
        $insertRules = $insertColumns->map(function (GenTableColumn $column) use ($tableName, $singular): string {
            $required = $column->_required ? 'required' : 'nullable';
            $rule = [
                "'$required'",
                "'$column->_validate'",
            ];
            if ($column->dict_type_id) {
                array_push($rule, "Rule::exists('dict_data', 'value')->where('dict_type_id', $column->dict_type_id)->where('status', 1)");
            }
            if ($column->_unique) {
                array_push($rule, "Rule::unique('$tableName', '$column->name')");
            }
            if ($column->_foreign) {
                $fkClassName = Str::of($column->_foreign_table)->singular()->studly()->toString();
                $exceptions = [];
                if (!class_exists('\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller')) {
                    array_push($exceptions, '\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller 類不存在');
                }
                if (!method_exists('\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller', 'select')) {
                    array_push($exceptions, '\App\Http\Controllers\Admin\\' . $fkClassName . 'Controller ::select 方法不存在');
                }
                $fkSingular = Str::of($column->_foreign_table)->singular()->camel()->toString();
                $route = "api/admin/$fkSingular/select";

                try {
                    Route::getRoutes()->match(Request::create($route, 'POST'));
                } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
                    array_push($exceptions, "$route POST 路由不存在");
                }

                if (count($exceptions) > 0) {
                    throw new Exception(implode(',', $exceptions));
                }
                array_push($rule, "Rule::exists('$column->_foreign_table', '$column->_foreign_column')");
            }
            return "'$column->name' => [" . implode(', ', $rule) . "],";
        })->implode("\n            ");
        $insertAttributes = $insertColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => '%s',", $column->name, $column->comment);
        })->implode("\n            ");
        $insertRequest = str_replace([
            '{{className}}', '{{rules}}', '{{attributes}}'
        ], [
            $className, $insertRules, $insertAttributes
        ], Gen::getStub('Request/RegisterRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/Create"."Request.php";
        Storage::disk('codes')->put($path, $insertRequest);

        $updateColumns = $columns->where('_update', '=', true)->values();
        $updateRules = $updateColumns->map(function (GenTableColumn $column) use ($tableName, $primary): string {
            $required = $column->_required ? 'required' : 'nullable';
            $rule = [
                "'$required'",
                "'$column->_validate'",
            ];
            if ($column->dict_type_id) {
                array_push($rule, "Rule::exists('dict_data', 'value')->where('dict_type_id', $column->dict_type_id)->where('status', 1)");
            }
            if ($column->_unique) {
                array_push($rule, "Rule::unique('$tableName', '$column->name')->ignore(" . '$' . $primary->name . ")");
            }
            if ($column->_foreign) {
                array_push($rule, "Rule::exists('$column->_foreign_table', '$column->_foreign_column')");
            }
            if ($column->primary && $column->autoincrement) {
                array_push($rule, "Rule::exists('$tableName', '$column->name')");
            }
            return "'$column->name' => [" . implode(', ', $rule) . "],";
        })->implode("\n            ");
        $updateRequestColumn = '$' . $primary->name . ' = $this->post(\'' . $primary->name . '\', 0);';
        $updateAttributes = $updateColumns->map(function (GenTableColumn $column): string {
            return sprintf("'%s' => '%s',", $column->name, $column->comment);
        })->implode("\n            ");
        $updateRequest = str_replace([
            '{{className}}', '{{idRequest}}', '{{rules}}', '{{attributes}}'
        ], [
            $className, $updateRequestColumn, $updateRules, $updateAttributes
        ], Gen::getStub('Request/UpdateRequest'));
        $path = "php/app/Http/Requests/Admin/{$className}/Update"."Request.php";
        Storage::disk('codes')->put($path, $updateRequest);

        $fillable = $columns->where('primary', '=', false)
            ->where('autoincrement', '=', false)
            ->pluck('name')
            ->map(fn(string $name): string => "'$name'")
            ->implode(', ');

        $where = $columns->where('_select', '=', true)->map(function (GenTableColumn $column) use ($tableName) {
            $when = '';
            if ($column->_validate === 'integer') {
                $when = ' && is_numeric($validated[\'' . $column->name . '\'])';
            }
            if ($column->_query === Gen::SELECT_LIKE) {
                $where = 'when(isset($validated[\'' . $column->name . '\'])' . $when . ', function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'LIKE\', \'%\' . $validated[\'' . $column->name . '\'] . \'%\');
        })';
            } else if ($column->_query === Gen::SELECT_BETWEEN) {
                $where = 'when(isset($validated[\'' . $column->name . '_start\']) && isset($validated[\'' . $column->name . '_end\']), function (Builder $query) use ($validated): Builder {
            return $query->whereBetween(\'' . $tableName . '.' . $column->name . '\', [$validated[\'' . $column->name . '_start' . '\'], $validated[\'' . $column->name . '_end' . '\']]);
        })->when(isset($validated[\'' . $column->name . '_start\']) && !isset($validated[\'' . $column->name . '_end\']), function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'>=\', $validated[\'' . $column->name . '_start' . '\']);
        })->when(!isset($validated[\'' . $column->name . '_start\']) && isset($validated[\'' . $column->name . '_end\']), function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'<=\', $validated[\'' . $column->name . '_end' . '\']);
        })';
            } else {
                $where = 'when(isset($validated[\'' . $column->name . '\'])' . $when . ', function (Builder $query) use ($validated): Builder {
            return $query->where(\'' . $tableName . '.' . $column->name . '\', \'' . $column->_query . '\', $validated[\'' . $column->name . '\']);
        })';
            }
            return $where;
        })->implode('->');
        $selectDbColumns = $columns->where('_list', '=', true)->pluck('name')
            ->add($primary->name)
            ->map(function (string $name) use ($tableName): string {
                return "'{$tableName}.{$name}'";
            })
            ->implode(', ');
        $columnNameList = $columns->pluck('name')->toArray();
        $timestamps = in_array(Model::CREATED_AT, $columnNameList) && in_array(Model::UPDATED_AT, $columnNameList);
        $sort = $timestamps ? Model::CREATED_AT : $primary->name;

        $keyType = 'string';
        if (in_array($primary->type, ['bigint', 'integer'])) {
            $keyType = 'int';
        }

        $model = str_replace([
            '{{className}}', '{{fillable}}', '{{singular}}', '{{where}}', '{{select}}', '{{tableName}}', '{{primaryKey}}', '{{keyType}}', '{{timestamps}}', '{{sort}}', '{{count}}'
        ], [
            $className, $fillable, $singular, $where, $selectDbColumns, $tableName, $primary->name, $keyType, $timestamps ? 'true' : 'false', $sort, $tableName . '.' . $primary->name
        ], Gen::getStub('Model'));
        $path = "php/app/Models/Common/{$className}.php";
        Storage::disk('codes')->put($path, $model);

        $apis = str_replace([
            '{{singular}}', '{{snake}}', '{{className}}'
        ], [
            $singular, $snake, $className
        ], Gen::getStub('Vue/ApiJs'));
        $path = "vue/src/api/{$singular}Api.js";
        Storage::disk('codes')->put($path, $apis);


        $form = $selectColumns->map(function (GenTableColumn $genTableColumn): string {
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-input v-model="form.' . $genTableColumn->name . '" />
        </el-form-item>';
                if ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $form = '<el-form-item label="' . $label . '" prop="' . $genTableColumn->name . '">
          <el-select v-model="form.' . $genTableColumn->name . '" clearable>
            <el-option :key="1" :value="1" :label="$t(\'common.yes\')" />
            <el-option :key="0" :value="0" :label="$t(\'common.no\')" />
          </el-select>
        </el-form-item>';
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-select v-model="form.' . $genTableColumn->name . '" clearable filterable>
            <el-option
              v-for="item in ' . $vFor . '"
              :key="item.' . $genTableColumn->_foreign_column . '"
              :value="item.' . $genTableColumn->_foreign_column . '"
              :label="item.' . $label . '"
            />
          </el-select>
        </el-form-item>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-select v-model="form.' . $genTableColumn->name . '" clearable filterable>
            <el-option
              v-for="item in dict.filter((e) => e.dict_type_id === ' . $genTableColumn->dict_type_id . ')"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_DATE) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '">
          <el-date-picker
            v-model="form.' . $genTableColumn->name . '"
            type="datetime"
            value-format="yyyy-MM-dd HH:mm:ss"
            time-arrow-control
          />
        </el-form-item>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未記錄， 暫不支持生成搜索條件');
            }

            return $form;
        })->implode("\n        ");

        $listColumns = $columns->where('_list', '=', true)->values();
        $table = $listColumns->map(function (GenTableColumn $genTableColumn): string {
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT || $genTableColumn->_show === Gen::TYPE_DATE) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable />';
                if ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $label . '" sortable>
          <template scope="scope">
            <el-tag v-if="scope.row.' . $genTableColumn->name . '" type="success">{{ $t(\'common.yes\') }}</el-tag>
            <el-tag v-else type="danger">{{ $t(\'common.no\') }}</el-tag>
          </template>
        </el-table-column>';
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <ForeignString v-if="' . $vFor . '.length > 0" column="' . $genTableColumn->_foreign_column . '" show="' . $label . '" :data="' . $vFor . '" :value="scope.row.' . $genTableColumn->name . '" />
          </template>
        </el-table-column>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <DictTag v-if="dict.length > 0" :dict-data="dict" :dict-type-id="' . $genTableColumn->dict_type_id . '" :value="scope.row.' . $genTableColumn->name . '" />
          </template>
        </el-table-column>';
            } elseif ($genTableColumn->_show === Gen::TYPE_IMAGE) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <el-image class="table-image table-image-50" :src="scope.row.' . $genTableColumn->name . '" :preview-src-list="[scope.row.' . $genTableColumn->name . ']">
              <div slot="error" class="image-error-slot">
                <i class="el-icon-picture-outline" />
              </div>
            </el-image>
          </template>
        </el-table-column>';
            } elseif ($genTableColumn->_show === Gen::TYPE_FILE) {
                $table = '<el-table-column prop="' . $genTableColumn->name . '" label="' . $genTableColumn->comment . '" sortable>
          <template scope="scope">
            <el-link v-if="scope.row.' . $genTableColumn->name . '" icon="el-icon-download" :underline="false" :href="scope.row.' . $genTableColumn->name . '" target="_blank">{{ $t(\'common.download\') }}</el-link>
          </template>
        </el-table-column>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未記錄， 暫不支持生成表格數據');
            }

            return $table;
        })->implode("\n        ");

        $dataForm = $selectColumns->map(function (GenTableColumn $genTableColumn): string {
            return "$genTableColumn->name: ''";
        })->implode(",\n        ");

        $components = [];
        $components[] = "create: () => import('@/views/{$singular}/create')";
        $components[] = "update: () => import('@/views/{$singular}/update')";
        $dataFormColumns = [];
        $mounted = [];
        $methods = [];
        $import = [];
        $import[] = "import { {$singular}Delete, {$singular}List } from '@/api/{$singular}Api'";
        if ($listColumns->where('dict_type_id', '=', true)->count() > 0) {
            $components[] = "DictTag: () => import('@/components/DictTag')";
            $dataFormColumns[] = 'dict: []';
            $mounted[] = "this.getDictData()";
            $methods[] = 'getDictData() {
      dictDataSelect().then(response => {
        const { select = [] } = response.data
        this.dict = select
      })
    }';
            $import[] = "import { dictDataSelect } from '@/api/dict'";
        }
        $foreignColumns = $listColumns->where('_foreign', '=', true)->values();
        if ($foreignColumns->count() > 0) {
            $components[] = "ForeignString: () => import('@/components/Foreign/string')";
            $dataFormColumns = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData: []')->toString();
            })->merge($dataFormColumns);
            $mounted = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return 'this.get' . Str::of($genTableColumn->_foreign_table)->singular()->studly()->append('Select()')->toString();
            })->merge($mounted);
            $methods = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->studly()->toString();
                $method = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return 'get' . $name . 'Select() {
      ' . $method . 'Select().then(response => {
        const { select = [] } = response.data
        this.' . $method . 'SelectData = select
      })
    }';
            })->merge($methods);
            $import = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return "import { {$name}Select } from '@/api/{$name}Api'";
            })->merge($import);
        }
        $dataFormColumns = collect($dataFormColumns)->implode(",\n      ");
        $components = implode(",\n    ", $components);
        $mounted = collect($mounted)->implode("\n    ");
        $methods = collect($methods)->implode(",\n    ");
        $import = collect($import)->implode("\n");

        // 格式化
        if ($dataForm !== '') {
            $dataForm = ",\n        " . $dataForm;
        }
        if ($dataFormColumns !== '') {
            $dataFormColumns = ",\n      " . $dataFormColumns;
        }
        if ($mounted !== '') {
            $mounted = "\n    " . $mounted;
        }
        if ($methods !== '') {
            $methods = ",\n    " . $methods;
        }
        $indexVue = str_replace([
            '{{form}}', '{{table}}', '{{name}}', '{{dataForm}}', '{{primaryId}}', '{{components}}', '{{dataFormColumns}}', '{{mounted}}', '{{methods}}', '{{import}}', '{{singular}}', '{{sort}}'
        ], [
            $form, $table, $singular . '.' . $snake, $dataForm, $primary->name, $components, $dataFormColumns, $mounted, $methods, $import, $singular, $sort
        ], Gen::getStub('Vue/IndexVue'));
        $path = "vue/src/views/{$singular}/index.vue";
        Storage::disk('codes')->put($path, $indexVue);


        $createColumns = $columns->where('_insert', '=', true)->values();
        $createForm = $createColumns->map(function (GenTableColumn $genTableColumn): string {
            $required = $genTableColumn->_required ? 'class="form-item-required" ' : '';
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT) {
                if ($genTableColumn->_validate === 'string') {
                    $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-input v-model="form.' . $genTableColumn->name . '" clearable />
      </el-form-item>';
                } elseif ($genTableColumn->_validate === 'integer') {
                    $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-input-number v-model="form.' . $genTableColumn->name . '" clearable />
      </el-form-item>';
                } elseif ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $form = '<el-form-item label="' . $label . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-model="form.' . $genTableColumn->name . '">
          <el-option :key="1" :value="1" :label="$t(\'common.yes\')" />
          <el-option :key="0" :value="0" :label="$t(\'common.no\')" />
        </el-select>
      </el-form-item>';
                } else {
                    throw new Exception('字段[' . $genTableColumn->name . ']未記錄， 暫不支持生成新增TEXT表格');
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-if="' . $vFor . '.length > 0" v-model="form.' . $genTableColumn->name . '" clearable filterable>
          <el-option
            v-for="item in ' . $vFor . '"
            :key="item.' . $genTableColumn->_foreign_column . '"
            :value="item.' . $genTableColumn->_foreign_column . '"
            :label="item.' . $label . '"
          />
        </el-select>
      </el-form-item>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-if="dict.length > 0" v-model="form.' . $genTableColumn->name . '" clearable filterable>
          <el-option
            v-for="item in dict.filter((e) => e.dict_type_id === ' . $genTableColumn->dict_type_id . ')"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_IMAGE) {
                $httpRequestName = Str::of($genTableColumn->name)->singular()->studly()->toString();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-upload
          ref="' . $genTableColumn->name . '"
          class="avatar-uploader"
          action="#"
          accept="image/*"
          name="image"
          list-type="picture"
          :limit="1"
          :http-request="upload' . $httpRequestName . 'Image"
          :on-remove="' . $genTableColumn->name . 'UploadRemove"
          :file-list="' . $genTableColumn->name . 'FileList"
          :on-exceed="' . $genTableColumn->name . 'UploadExceed"
        >
          <i class="el-icon-plus avatar-uploader-icon" />
        </el-upload>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_FILE) {
                $httpRequestName = Str::of($genTableColumn->name)->singular()->studly()->toString();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-upload
          ref="' . $genTableColumn->name . '"
          action="#"
          accept="application/msword,application/pdf,text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,video/*"
          name="file"
          :limit="1"
          :file-list="' . $genTableColumn->name . 'FileList"
          :http-request="upload' . $httpRequestName . 'File"
          :on-exceed="' . $genTableColumn->name . 'UploadExceed"
          :on-remove="' . $genTableColumn->name . 'UploadRemove"
        >
          <el-button type="primary">{{ $t(\'file.uploadFileText.uploadText2\') }}</el-button>
          <div slot="tip" class="el-upload__tip">{{ $t(\'common.uploadTip\') }}</div>
        </el-upload>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_EDITOR) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <WangEditor ref="' . $genTableColumn->name . 'Editor" v-model="form.' . $genTableColumn->name . '" />
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_DATE) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-date-picker
          v-model="form.' . $genTableColumn->name . '"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          time-arrow-control
        />
      </el-form-item>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未記錄， 暫不支持生成新增表格');
            }

            return $form;
        })->implode("\n      ");

        $import = [];
        $components = [];
        $dataFormColumns = [];
        $init = [];
        $methods = [];
        $resetForm = [];
        $import[] = "import { {$singular}Create } from '@/api/{$singular}Api'";
        if ($createColumns->where('dict_type_id', '=', true)->count() > 0) {
            $import[] = "import { dictDataSelect } from '@/api/dict'";
            $dataFormColumns[] = 'dict: []';
            $init[] = "this.getDictData()";
            $methods[] = 'getDictData() {
      dictDataSelect().then(response => {
        const { select = [] } = response.data
        this.dict = select
      })
    }';
        }
        $foreignColumns = $createColumns->where('_foreign', '=', true)->values();
        if ($foreignColumns->count() > 0) {
            $import = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return "import { {$name}Select } from '@/api/{$name}Api'";
            })->merge($import);
            $dataFormColumns = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData: []')->toString();
            })->merge($dataFormColumns);
            $init = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return 'this.get' . Str::of($genTableColumn->_foreign_table)->singular()->studly()->append('Select()')->toString();
            })->merge($init);
            $methods = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->studly()->toString();
                $method = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return 'get' . $name . 'Select() {
      ' . $method . 'Select().then(response => {
        const { select = [] } = response.data
        this.' . $method . 'SelectData = select
      })
    }';
            })->merge($methods);
        }
        $uploads = $createColumns->whereIn('_show', [Gen::TYPE_FILE, Gen::TYPE_IMAGE])->values();
        if ($uploads->count() > 0) {
            $fileImport[] = 'fileRemoveFile';
            $fileUploads = $uploads->where('_show', '=', Gen::TYPE_FILE);
            if ($fileUploads->count() > 0) {
                $fileImport[] = 'fileUploadFile';
                $dataFormColumns = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    return Str::of($genTableColumn->name)->append('FileList: []')->toString();
                })->merge($dataFormColumns);
                $methods = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    $name = Str::of($genTableColumn->name)->studly()->toString();
                    return 'upload' . $name . 'File(file) {
      const loading = this.$loading({
        lock: true,
        text: \'Loading\',
        spinner: \'el-icon-loading\',
        background: \'rgba(0, 0, 0, 0.7)\'
      })
      const data = new FormData()
      data.append(file.filename, file.file)
      fileUploadFile(data).then(response => {
        const { path = \'\' } = response.data
        this.form.' . $genTableColumn->name . ' = path
        this.$message({ type: \'success\', message: response.message })
      }).finally(_ => {
        loading.close()
      })
    },
    ' . $genTableColumn->name . 'UploadExceed(files, fileList) {
      this.$message({
        type: \'error\',
        message: fileList[0].name + \' \' + this.$t(\'common.alreadyUpload\')
      })
    },
    ' . $genTableColumn->name . 'UploadRemove(file, fileList) {
      const deleteFile = this.form.' . $genTableColumn->name . '
      this.form.' . $genTableColumn->name . ' = null
      fileRemoveFile({
        path: deleteFile
      }).finally(() => {
      })
    }';
                })->merge($methods);
                $resetForm = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    return 'this.$refs.' . $genTableColumn->name . '.clearFiles()';
                })->merge($resetForm);
            }
            $imageUploads = $uploads->where('_show', '=', Gen::TYPE_IMAGE);
            if ($imageUploads->count() > 0) {
                $fileImport[] = 'fileUploadImage';
                $dataFormColumns = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    return Str::of($genTableColumn->name)->append('FileList: []')->toString();
                })->merge($dataFormColumns);
                $methods = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    $name = Str::of($genTableColumn->name)->studly()->toString();
                    return 'upload' . $name . 'Image(file) {
      this.' . $genTableColumn->name . 'FileList = []
      const loading = this.$loading({
        lock: true,
        text: \'Loading\',
        spinner: \'el-icon-loading\',
        background: \'rgba(0, 0, 0, 0.7)\'
      })
      const data = new FormData()
      data.append(file.filename, file.file)
      fileUploadImage(data).then(response => {
        const { path = \'\' } = response.data
        this.form.' . $genTableColumn->name . ' = path
        this.$message({ type: \'success\', message: response.message })
        this.' . $genTableColumn->name . 'FileList.push({
          name: path,
          url: path
        })
      }).finally(_ => {
        loading.close()
      })
    },
    ' . $genTableColumn->name . 'UploadExceed(files, fileList) {
      this.$refs.' . $genTableColumn->name . '.handleRemove(\'\', fileList[0].raw)
      const file = {
        filename: this.$refs.' . $genTableColumn->name . '.name,
        file: files[0]
      }
      this.upload' . $name . 'Image(file)
    },
    ' . $genTableColumn->name . 'UploadRemove() {
      const deleteImage = this.form.' . $genTableColumn->name . '
      this.form.' . $genTableColumn->name . ' = null
      fileRemoveFile({
        path: deleteImage
      }).finally(() => {
      })
    }';
                })->merge($methods);
                $resetForm = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    return 'this.$refs.' . $genTableColumn->name . '.clearFiles()';
                })->merge($resetForm);
            }
            $import[] = "import { " . implode(', ', $fileImport) . " } from '@/api/file'";
        }
        $editor = $createColumns->where('_show', '=', Gen::TYPE_EDITOR)->values();
        if ($editor->count() > 0) {
            $components[] = "WangEditor: () => import('@/components/WangEditor')";
            $resetForm = $editor->map(function (GenTableColumn $genTableColumn): string {
                return 'this.$refs.' . $genTableColumn->name . 'Editor.clear()';
            })->merge($resetForm);
        }

        $import = collect($import)->implode("\n");
        $components = implode(",\n    ", $components);

        $dataForm = $createColumns->map(function (GenTableColumn $genTableColumn): string {
            $default = $genTableColumn->default;
            if (is_null($default)) {
                $default = "''";
            } else {
                if ($genTableColumn->type === 'integer' || $genTableColumn->dict_type_id !== null) {
                    $default = "'$default'";
                }
            }
            return "$genTableColumn->name: $default";
        })->implode(",\n        ");

        $dataFormColumns = collect($dataFormColumns)->implode(",\n      ");
        $init = collect($init)->implode("\n      ");
        $methods = collect($methods)->implode(",\n    ");
        $resetForm = collect($resetForm)->implode("\n      ");

        if ($dataFormColumns !== '') {
            $dataFormColumns = "\n      " . $dataFormColumns . ",";
        }
        if ($init !== '') {
            $init = "\n      " . $init;
        }
        if ($resetForm !== '') {
            $resetForm = "\n      " . $resetForm;
        }
        if ($methods !== '') {
            $methods = ",\n    " . $methods;
        }
        if ($components !== '') {
            $components = "    " . $components;
        }
        $createVue = str_replace([
            '{{createForm}}', '{{import}}', '{{singular}}', '{{components}}', '{{dataForm}}', '{{dataFormColumns}}', '{{init}}', '{{methods}}', '{{resetForm}}'
        ], [
            $createForm, $import, $singular, $components, $dataForm, $dataFormColumns, $init, $methods, $resetForm
        ], Gen::getStub('Vue/CreateVue'));
        $path = "vue/src/views/{$singular}/create.vue";
        Storage::disk('codes')->put($path, $createVue);


        $updateColumns = $columns->where('_update', '=', true)->where('primary', '=', false)->values();
        $updateForm = $updateColumns->map(function (GenTableColumn $genTableColumn): string {
            $required = $genTableColumn->_required ? 'class="form-item-required" ' : '';
            if ($genTableColumn->_show === Gen::TYPE_INPUT_TEXT) {
                if ($genTableColumn->_validate === 'string') {
                    $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-input v-model="form.' . $genTableColumn->name . '" clearable />
      </el-form-item>';
                } elseif ($genTableColumn->_validate === 'integer') {
                    $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-input-number v-model="form.' . $genTableColumn->name . '" clearable />
      </el-form-item>';
                } elseif ($genTableColumn->_validate === 'boolean') {
                    $label = Str::of($genTableColumn->comment)->explode(' ')->first();
                    $form = '<el-form-item label="' . $label . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-model="form.' . $genTableColumn->name . '">
          <el-option :key="1" :value="1" :label="$t(\'common.yes\')" />
          <el-option :key="0" :value="0" :label="$t(\'common.no\')" />
        </el-select>
      </el-form-item>';
                } else {
                    throw new Exception('字段[' . $genTableColumn->name . ']未記錄， 暫不支持生成新增TEXT表格');
                }
            } elseif ($genTableColumn->_foreign) {
                $vFor = Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData')->toString();
                $label = Str::of($genTableColumn->_foreign_show)->explode(',')->first();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-if="' . $vFor . '.length > 0" v-model="form.' . $genTableColumn->name . '" clearable filterable>
          <el-option
            v-for="item in ' . $vFor . '"
            :key="item.' . $genTableColumn->_foreign_column . '"
            :value="item.' . $genTableColumn->_foreign_column . '"
            :label="item.' . $label . '"
          />
        </el-select>
      </el-form-item>';
            } elseif (is_int($genTableColumn->dict_type_id)) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-select v-if="dict.length > 0" v-model="form.' . $genTableColumn->name . '" clearable filterable>
          <el-option
            v-for="item in dict.filter((e) => e.dict_type_id === ' . $genTableColumn->dict_type_id . ')"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_IMAGE) {
                $httpRequestName = Str::of($genTableColumn->name)->singular()->studly()->toString();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-upload
          ref="' . $genTableColumn->name . '"
          class="avatar-uploader"
          action="#"
          accept="image/*"
          name="image"
          list-type="picture"
          :limit="1"
          :http-request="upload' . $httpRequestName . 'Image"
          :on-remove="' . $genTableColumn->name . 'UploadRemove"
          :file-list="' . $genTableColumn->name . 'FileList"
          :on-exceed="' . $genTableColumn->name . 'UploadExceed"
        >
          <i class="el-icon-plus avatar-uploader-icon" />
        </el-upload>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_FILE) {
                $httpRequestName = Str::of($genTableColumn->name)->singular()->studly()->toString();
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-upload
          ref="' . $genTableColumn->name . '"
          action="#"
          accept="application/msword,application/pdf,text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,video/*"
          name="file"
          :limit="1"
          :file-list="' . $genTableColumn->name . 'FileList"
          :http-request="upload' . $httpRequestName . 'File"
          :on-exceed="' . $genTableColumn->name . 'UploadExceed"
          :on-remove="' . $genTableColumn->name . 'UploadRemove"
        >
          <el-button type="primary">{{ $t(\'file.uploadFileText.uploadText2\') }}</el-button>
          <div slot="tip" class="el-upload__tip">{{ $t(\'common.uploadTip\') }}</div>
        </el-upload>
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_EDITOR) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <WangEditor ref="' . $genTableColumn->name . 'Editor" v-model="form.' . $genTableColumn->name . '" />
      </el-form-item>';
            } elseif ($genTableColumn->_show === Gen::TYPE_DATE) {
                $form = '<el-form-item label="' . $genTableColumn->comment . '" prop="' . $genTableColumn->name . '" ' . $required . ':error="error.' . $genTableColumn->name . '">
        <el-date-picker
          v-model="form.' . $genTableColumn->name . '"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          time-arrow-control
        />
      </el-form-item>';
            } else {
                throw new Exception('字段[' . $genTableColumn->name . ']未記錄， 暫不支持生成新增表格');
            }

            return $form;
        })->implode("\n      ");

        $import = [];
        $components = [];
        $dataFormColumns = [];
        $init = [];
        $methods = [];
        $import[] = "import { {$singular}Info, {$singular}Update } from '@/api/{$singular}Api'";
        if ($updateColumns->where('dict_type_id', '=', true)->count() > 0) {
            $import[] = "import { dictDataSelect } from '@/api/dict'";
            $dataFormColumns[] = 'dict: []';
            $init[] = "this.getDictData()";
            $methods[] = 'getDictData() {
      dictDataSelect().then(response => {
        const { select = [] } = response.data
        this.dict = select
      })
    }';
        }
        $foreignColumns = $updateColumns->where('_foreign', '=', true)->values();
        if ($foreignColumns->count() > 0) {
            $import = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return "import { {$name}Select } from '@/api/{$name}Api'";
            })->merge($import);
            $dataFormColumns = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return Str::of($genTableColumn->_foreign_table)->singular()->camel()->append('SelectData: []')->toString();
            })->merge($dataFormColumns);
            $init = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                return 'this.get' . Str::of($genTableColumn->_foreign_table)->singular()->studly()->append('Select()')->toString();
            })->merge($init);
            $methods = $foreignColumns->map(function (GenTableColumn $genTableColumn): string {
                $name = Str::of($genTableColumn->_foreign_table)->singular()->studly()->toString();
                $method = Str::of($genTableColumn->_foreign_table)->singular()->camel()->toString();
                return 'get' . $name . 'Select() {
      ' . $method . 'Select().then(response => {
        const { select = [] } = response.data
        this.' . $method . 'SelectData = select
      })
    }';
            })->merge($methods);
        }
        $uploads = $updateColumns->whereIn('_show', [Gen::TYPE_FILE, Gen::TYPE_IMAGE])->values();
        if ($uploads->count() > 0) {
            $fileImport = [];
            $fileUploads = $uploads->where('_show', '=', Gen::TYPE_FILE);
            if ($fileUploads->count() > 0) {
                $fileImport[] = 'fileUploadFile';
                $dataFormColumns = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    return Str::of($genTableColumn->name)->append('FileList: []')->toString();
                })->merge($dataFormColumns);
                $methods = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    $name = Str::of($genTableColumn->name)->studly()->toString();
                    return 'upload' . $name . 'File(file) {
      const loading = this.$loading({
        lock: true,
        text: \'Loading\',
        spinner: \'el-icon-loading\',
        background: \'rgba(0, 0, 0, 0.7)\'
      })
      const data = new FormData()
      data.append(file.filename, file.file)
      fileUploadFile(data).then(response => {
        const { path = \'\' } = response.data
        this.form.' . $genTableColumn->name . ' = path
        this.$message({ type: \'success\', message: response.message })
      }).finally(_ => {
        loading.close()
      })
    },
    ' . $genTableColumn->name . 'UploadExceed(files, fileList) {
      this.$message({
        type: \'error\',
        message: fileList[0].name + \' \' + this.$t(\'common.alreadyUpload\')
      })
    },
    ' . $genTableColumn->name . 'UploadRemove(file, fileList) {
      this.form.' . $genTableColumn->name . ' = null
    }';
                })->merge($methods);
                $init = $fileUploads->map(function (GenTableColumn $genTableColumn): string {
                    return 'if (this.form.' . $genTableColumn->name . ' && this.form.' . $genTableColumn->name . '.length > 0) {
          this.' . $genTableColumn->name . 'FileList = [{
            name: this.form.' . $genTableColumn->name . ',
            url: this.form.' . $genTableColumn->name . '
          }]
        } else {
          this.' . $genTableColumn->name . 'FileList = []
        }';
                })->merge($init);
            }
            $imageUploads = $uploads->where('_show', '=', Gen::TYPE_IMAGE);
            if ($imageUploads->count() > 0) {
                $fileImport[] = 'fileUploadImage';
                $dataFormColumns = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    return Str::of($genTableColumn->name)->append('FileList: []')->toString();
                })->merge($dataFormColumns);
                $methods = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    $name = Str::of($genTableColumn->name)->studly()->toString();
                    return 'upload' . $name . 'Image(file) {
      this.' . $genTableColumn->name . 'FileList = []
      const loading = this.$loading({
        lock: true,
        text: \'Loading\',
        spinner: \'el-icon-loading\',
        background: \'rgba(0, 0, 0, 0.7)\'
      })
      const data = new FormData()
      data.append(file.filename, file.file)
      fileUploadImage(data).then(response => {
        const { path = \'\' } = response.data
        this.form.' . $genTableColumn->name . ' = path
        this.$message({ type: \'success\', message: response.message })
        this.' . $genTableColumn->name . 'FileList.push({
          name: path,
          url: path
        })
      }).finally(_ => {
        loading.close()
      })
    },
    ' . $genTableColumn->name . 'UploadRemove() {
      this.form.' . $genTableColumn->name . ' = null
    },
    ' . $genTableColumn->name . 'UploadExceed(files, fileList) {
      this.$refs.' . $genTableColumn->name . '.handleRemove(\'\', fileList[0].raw)
      const file = {
        filename: this.$refs.' . $genTableColumn->name . '.name,
        file: files[0]
      }
      this.upload' . $name . 'Image(file)
    }';
                })->merge($methods);
                $init = $imageUploads->map(function (GenTableColumn $genTableColumn): string {
                    return 'if (this.form.' . $genTableColumn->name . ' && this.form.' . $genTableColumn->name . '.length > 0) {
          this.' . $genTableColumn->name . 'FileList = [{
            name: this.form.' . $genTableColumn->name . ',
            url: this.form.' . $genTableColumn->name . '
          }]
        } else {
          this.' . $genTableColumn->name . 'FileList = []
        }';
                })->merge($init);
            }
            $import[] = "import { " . implode(', ', $fileImport) . " } from '@/api/file'";
        }
        $editor = $updateColumns->where('_show', '=', Gen::TYPE_EDITOR)->values();
        if ($editor->count() > 0) {
            $components[] = "WangEditor: () => import('@/components/WangEditor')";
        }
        $init = $createColumns->filter(function (GenTableColumn $genTableColumn): bool {
            return $genTableColumn->dict_type_id !== null;
        })->map(function (GenTableColumn $genTableColumn): string {
            return '  this.form.' . $genTableColumn->name . ' = this.form.' . $genTableColumn->name . '.toString()';
        })->merge($init);


        $import = collect($import)->implode("\n");
        $components = implode(",\n    ", $components);
        $dataFormColumns = collect($dataFormColumns)->implode(",\n      ");
        $init = collect($init)->implode("\n        ");
        $methods = collect($methods)->implode(",\n    ");
        if ($dataFormColumns !== '') {
            $dataFormColumns = "\n      " . $dataFormColumns . ",";
        }
        if ($init !== '') {
            $init = "\n      " . $init . "\n";
        }
        if ($methods !== '') {
            $methods = ",\n    " . $methods;
        }
        if ($components !== '') {
            $components = "    " . $components;
        }

        $updateVue = str_replace([
            '{{updateForm}}', '{{import}}', '{{singular}}', '{{components}}', '{{dataFormColumns}}', '{{primaryId}}', '{{init}}', '{{methods}}'
        ], [
            $updateForm, $import, $singular, $components, $dataFormColumns, $primary->name, $init, $methods
        ], Gen::getStub('Vue/UpdateVue'));
        $path = "vue/src/views/{$singular}/update.vue";
        Storage::disk('codes')->put($path, $updateVue);

        $zip = new ZipArchive();
        $zipPath = config('filesystems.disks.codes.root') . "/generator.zip";
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);   //打開壓縮包
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(config('filesystems.disks.codes.root')),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if (basename($filePath) !== '.gitignore') {
                    $relativePath = substr($filePath, strlen(storage_path("codes")) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        Storage::disk('codes')->deleteDirectory('php');
        Storage::disk('codes')->deleteDirectory('vue');
        return $zipPath;
    }

    private static function getStub(string $type): string
    {
        return file_get_contents(resource_path('stubs/' . $type . '.stub'));
    }
}
