<?php

namespace App\Models\Backend\System;

use App\Models\BaseModel;
use App\Util\Gen;
use Exception;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class GenTableColumn extends BaseModel
{
    use  LogsActivity;

    protected $table = 'gen_table_columns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];


    protected $casts = [
        'notnull' => 'boolean',
        'primary' => 'boolean',
        'autoincrement' => 'boolean',
        'unsigned' => 'boolean',
        '_insert' => 'boolean',
        '_update' => 'boolean',
        '_list' => 'boolean',
        '_select' => 'boolean',
        '_required' => 'boolean',
        '_unique' => 'boolean',
        '_foreign' => 'boolean',
        '_sort' => 'boolean',
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gen_table_column')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * @param DictType $dictType
     * @return $this
     */
    public function setDict(DictType $dictType): GenTableColumn
    {
        $default = DictData::whereDictTypeId($dictType->id)
            ->where('status', 1)
            ->where('default', 1)
            ->value('value');
        $this->default = $default;
        $this->_query = Gen::SELECT_EQ;
        $this->_show = Gen::TYPE_SELECT;
        $this->_validate = 'string';
        $this->dict_type_id = $dictType->id;
        $this->_select = true;
        $this->_unique = false;
        $this->_foreign = false;
        $this->_foreign_table = null;
        $this->_foreign_column = null;
        $this->_foreign_show = null;
        $this->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function setType(string $type): GenTableColumn
    {
        $this->_show = $type;
        if (in_array($type, [Gen::TYPE_FILE, Gen::TYPE_EDITOR, Gen::TYPE_IMAGE])) {
            $this->_select = false;
            $this->_validate = 'string';
            $this->dict_type_id = null;
            $this->_unique = false;
            if ($type === Gen::TYPE_EDITOR) {
                $this->_list = false;
            }
        }
        $this->save();
        return $this;
    }

    /**
     * 設置外鍵顯示字段
     *
     * @param string[] $columns
     * @return $this
     * @throws Exception
     */
    public function setForeignShow(array $columns): GenTableColumn
    {
        if (!$this->_foreign) {
            throw new Exception("不存在對應的主鍵");
        }

        $foreignColumns = Gen::getTableInfo($this->_foreign_table);
        $foreignColumns = array_map(fn(array $column): string => $column['name'], $foreignColumns['columns']);
        $errorColumns = array_filter($columns, fn(string $column): bool => !in_array($column, $foreignColumns));
        if (count($errorColumns) > 0) {
            $errorColumnString = implode(', ', $errorColumns);
            throw new Exception("字段:$errorColumnString 不在 $this->_foreign_table 表內");
        }

        $this->_show = Gen::TYPE_SELECT;
        $this->_foreign_show = implode(',', $columns);
        $this->save();

        return $this;
    }

    public function setUnique(): GenTableColumn
    {
        $this->_unique = true;
        $this->save();

        return $this;
    }
}
