<?php

namespace App\Util;

/**
 * 數組操作工具類
 * Class ArrayTool
 */
class ArrayTool
{

    /**
     * 設置將孩子安置在父級中
     * @param array $data 數據集合
     * @param int|string $p_id 頂級id
     * @param string $p_field 父級id字段
     * @param string $s_field id字段
     * @param string $c_field 子集合名稱
     * @param int $level 層級
     * @param mixed $callable 可執行函數
     * @return array
     */
    public static function setChildrenInParent(
        array $data,
        $p_id = 0,
        string $p_field = 'pid',
        string $s_field = 'id',
        string $c_field = 'children',
        int $level = 1,
        $callable = null
    ): array {
        $children = [];
        foreach ($data as $i => $item) {
            if ($item[$p_field] == $p_id) {
                if (!empty($callable) && is_callable($callable)) {
                    $item = call_user_func_array($callable, [$item, $level]);
                }
                $children[] = $item;
                unset($data[$i]);
            }
        }
        if ($data) {
            foreach ($children as $i => $item) {
                $son = self::setChildrenInParent($data, $item[$s_field], $p_field, $s_field, $c_field, $level + 1,
                    $callable);
                if ($son) {
                    $children[$i][$c_field] = $son;
                }
            }
        }
        return $children;
    }

    /**
     * 設置將孩子安置在父級中 新
     * @param array $data
     * @param int $p_id
     * @param string $p_field
     * @param string $s_field
     * @param string $c_field
     * @param bool $id_key
     * @param bool $keep_pid
     * @param bool $keep_c_field
     * @return array
     */
    public static function setChildrenInParentNew(
        array $data,
        int $p_id = 0,
        string $p_field = 'pid',
        string $s_field = 'id',
        string $c_field = 'children',
        bool $id_key = false,
        bool $keep_pid = true,
        bool $keep_c_field = true
    ) {
        $arr1 = array_column($data, null, $s_field);
        $new_arr = [];
        foreach ($arr1 as $k => $v) {
            if (!$keep_pid) {
                unset($arr1[$k][$p_field]);
            }
            if ($keep_c_field && !isset($arr1[$k][$c_field])) {
                $arr1[$k][$c_field] = [];
            }
            if ($v[$p_field] != $p_id) {
                if ($id_key) {
                    $arr1[$v[$p_field]][$c_field][$v[$s_field]] = &$arr1[$k];
                } else {
                    $arr1[$v[$p_field]][$c_field][] = &$arr1[$k];
                }
            } else {
                if ($id_key) {
                    $new_arr[$v[$s_field]] = &$arr1[$k];
                } else {
                    $new_arr[] = &$arr1[$k];
                }
            }
        }
        return $new_arr;
    }

    /**
     * 判斷數組內值為數字
     * @param array $data
     * @return bool
     */
    public static function isNumberArray(array $data): bool
    {
        foreach ($data as $key => $val) {
            if ((!is_string($val) && !is_numeric($val)) || !preg_match("/^\d+$/", $val)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判斷數組內為url
     * @param array $data
     * @return bool
     */
    public static function isUrlArray(array $data): bool
    {
        foreach ($data as $key => $val) {
            if (!filter_var($val, FILTER_VALIDATE_URL)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 獲取數組字段最小值
     * @param array $data
     * @param $field
     * @return mixed
     */
    public static function getArrayMin(array $data, $field)
    {
        $field_list = array_column($data, $field);
        return min($field_list);
    }

    /**
     * 篩選列表
     * @param $filter
     * @param array $list
     * @return array
     */
    public static function filterList($filter, array $list): array
    {
        if (is_string($filter)) {
            $filter = explode(',', $filter);
        }
        $filter = self::strArrayToManyArray($filter);
        foreach ($list as &$data) {
            $data = self::filterItem($filter, $data);
        }
        return $list;
    }

    /**
     * 分割字符字段為數組
     * @param $field
     * @return false|mixed|string[]
     */
    public static function explodeFieldToArray($field)
    {
        return is_string($field) ? explode(',', $field) : $field;
    }

    /**
     * 過濾多維數組
     * @param string|array $filter 過濾字段
     * @param array $data 數據
     * @return array
     */
    public static function filterData($filter, array $data): array
    {
        if (is_string($filter)) {
            $filter = explode(',', $filter);
        }
        $filter = self::strArrayToManyArray($filter);
        return self::filterItem($filter, $data);
    }

    /**
     * 過濾數組
     * @param array $filter 過濾字段
     * @param array $data 數據
     * @return array
     */
    public static function filterItem(array $filter, array $data): array
    {
        $list = [];
        foreach ($filter as $key => $val) {
            if (is_string($key) && is_array($val) && isset($data[$key])) {
                $list[$key] = self::filterItem($val, $data[$key]);
            } else {
                if (is_int($key) && isset($data[$val])) {
                    $list[$val] = $data[$val];
                } else {
                    if (is_string($key) && isset($data[$key])) {
                        $list[$key] = $data[$key];
                    } else {
                        if (is_string($key) && !isset($data[$key])) {
                            $list[$key] = $val;
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 字符數組轉多維數組
     * ['a.b', 'a.c', 'd.c', 'd.e'] to ['a' => ['b', 'c'], 'd' => ['c', 'e']]
     * @param array $data
     * @return array
     */
    public static function strArrayToManyArray(array $data): array
    {
        $list = [];
        foreach ($data as $key => $val) {
            if (is_int($key) && is_string($val)) {
                $item = self::explodeStrByPoint($val);
            } else {
                $item = self::explodeStrByPoint($key, $val);
            }
            $list = array_merge_recursive($list, $item);
        }
        return $list;
    }

    /**
     * 對象點對象字符轉數組
     * 'a.b.c' to  ['a' => [ 'b' => ['c']]]
     * @param string $string
     * @param $value
     * @return array
     */
    public static function explodeStrByPoint(string $string, $value = null): array
    {
        $data = [];
        $start = strpos($string, '.');
        if ($start !== false) {
            $key = substr($string, 0, $start);
            $val = self::explodeStrByPoint(substr($string, $start + 1), $value);
            $data[$key] = $val;
        } else {
            if (isset($value)) {
                $data[$string] = $value;
            } else {
                $data[] = $string;
            }
        }
        return $data;
    }

    /**
     * 按照字段值分組
     * @param array $data
     * @param string $field
     * @param null $key
     * @param bool $keep_old_index 保存老下標
     * @param bool $unset 刪除分組字段
     * @return array
     */
    public static function arrayColumnGroup(
        array $data,
        string $field,
        $key = null,
        bool $keep_old_index = false
    ): array {
        $list = [];
        if (is_string($key)) {
            if (strpos($key, ',') !== false) {
                $key = preg_split("/[\s,]+/", $key);
            }
        }
        if ($keep_old_index) {
            foreach ($data as $k => $v) {
                if (isset($v[$field])) {
                    $list[$v[$field]][$k] = is_string($key) ? ($v[$key] ?? null) : (is_array($key) ? self::filterItem($key,
                        $v) : $v);
                }
            }
        } else {
            foreach ($data as $k => $v) {
                if (isset($v[$field])) {
                    $list[$v[$field]][] = is_string($key) ? ($v[$key] ?? null) : (is_array($key) ? self::filterItem($key,
                        $v) : $v);
                }
            }
        }
        return $list;
    }

    /**
     * 數組搜索
     * @param array $data
     * @param $filter
     * @return array
     */
    public static function arraySearch(array $data, $filter): array
    {
        $list = [];
        foreach ($data as $k => $v) {
            $status = true;
            foreach ($filter as $key => $value) {
                if (!isset($v[$key]) || $v[$key] != $value) {
                    $status = false;
                    break;
                }
            }
            if ($status) {
                $list[] = $v;
            }
        }
        return $list;
    }

    /**
     * 獲取指定鍵值並刪除
     * @param array $data
     * @param $key
     * @return mixed
     */
    public static function getKeyUnset(array &$data, $key)
    {
        $value = $data[$key];
        unset($data[$key]);
        return $value;
    }

    /**
     * 統計合併數組
     * @param mixed ...$param
     * @return array|mixed
     */
    public static function mergeArraySum(...$param): array
    {
        if (!$param) {
            return [];
        }
        $list = current($param) ?? [];
        if (count($param) < 2) {
            return $list;
        }
        $list_two = next($param);
        foreach ($list as $key => $value) {
            if (isset($list_two[$key])) {
                if (is_array($value) && is_array($list_two[$key])) {
                    $list[$key] = self::mergeArraySum($value, $list_two[$key]);
                    unset($list_two[$key]);
                } else {
                    if (is_numeric($value) && is_numeric($list_two[$key])) {
                        $list[$key] = $value + $list_two[$key];
                        unset($list_two[$key]);
                    }
                }
            }
        }
        if ($list_two) {
            foreach ($list_two as $key => $value) {
                if (!isset($list[$key])) {
                    $list[$key] = $value;
                }
            }
        }
        $param = array_slice($param, 2);
        if (count($param) >= 1) {
            $list = self::mergeArraySum($list, ...$param);
        }
        return $list;
    }

    /**
     * 返回數組中指定的列
     * @param array $data
     * @param $field
     * @param string|null $index
     * @return array
     */
    public static function column(array $data, $field, string $index = null): array
    {
        if (is_string($field) && strpos($field, ',') === false) {
            return array_column($data, $field, $index);
        }
        $field = array_fill_keys(is_string($field) ? explode(',', $field) : $field, 0);
        $array = [];
        foreach ($data as $key => $value) {
            $item = array_intersect_key($value, $field);
            if (isset($index)) {
                $array[$value[$index]] = $item;
            } else {
                $array[] = $item;
            }
        }
        return $array;
    }

    /**
     * 列表根據前綴分組
     * @param $list
     * @param $prefix
     * @param bool $unset
     * @param null $alias
     * @return array
     */
    public static function prefixGroupList($list, $prefix, bool $unset = true, $alias = null): array
    {
        $data = [];
        foreach ($list as $i => $item) {
            $data[] = self::prefixGroup($item, $prefix, $unset, $alias);
        }
        return $data;
    }

    /**
     * 根據前綴分組
     * @param $data
     * @param $prefix
     * @param bool $unset
     * @param null $alias
     * @return array
     */
    public static function prefixGroup($data, $prefix, bool $unset = true, $alias = null): array
    {
        $alias = $alias ?? $prefix;
        foreach ($data as $key => $value) {
            $index = strpos($key, $prefix);
            if ($index === 0) {
                $length = strlen($prefix);
                $field = substr($key, $length);
                if (!isset($data[$alias])) {
                    $data[$alias] = [];
                }
                $data[$alias][$field] = $value;
                if ($unset) {
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    /**
     * 獲取重複的鍵值
     * @param array $array
     * @param integer $num
     * @return array
     */
    public static function getRepeatValues(array $array, int $num = 1): array
    {
        $new = [];
        $array_count_value = array_count_values($array);
        foreach ($array_count_value as $key => $item) {
            if ($item > $num) {
                $new[] = $key;
            }
        }
        return $new;
    }

    /**
     * 深度差異
     * @param $array1
     * @param $array2
     * @param ...$arrays
     * @return array
     */
    public static function deepDiff($array1, $array2, ...$arrays)
    {
        $diff_array = [];
        foreach ($array1 as $k1 => $v1) {
            if (!isset($array2[$k1]) || (is_array($v1) && !is_array($array2[$k1]))) {
                $diff_array[$k1] = $v1;
            } else {
                if (is_array($v1)) {
                    $new_diff = self::deepDiff($v1, $array2[$k1]);
                    if ($new_diff) {
                        $diff_array[$k1] = $new_diff;
                    }
                } else {
                    if ($v1 != $array2[$k1]) {
                        $diff_array[$k1] = $v1;
                    }
                }
            }
        }
        if (!empty($arrays)) {
            return self::deepDiff($diff_array, ...$arrays);
        }
        return $diff_array;
    }

    /**
     * 合併 ids 字段
     * @param $list
     * @param $field
     * @param $unique
     * @return array|false|mixed
     */
    public static function mergeIdsField($list, $field = null, $unique = true)
    {
        $data = $field ? array_column($list, $field) : $list;
        if (!empty($data)) {
            if (count($data) > 1) {
                $data = array_merge(...$data);
            } else {
                $data = current($data);
            }
        }
        return $unique ? array_unique($data) : $data;
    }

    /**
     * 獲取 Id 顯
     * @param $data
     * @param $ids
     * @param bool $key
     * @return array
     */
    public static function getIdsItems($data, $ids, $key = true)
    {
        $list = [];
        if ($key) {
            foreach ($ids as $id) {
                if (isset($data[$id])) {
                    $list[$id] = $data[$id];
                }
            }
        } else {
            foreach ($ids as $id) {
                if (isset($data[$id])) {
                    $list[] = $data[$id];
                }
            }
        }
        return $list;
    }

    /**
     * 數組類型格式化
     * @param $format
     * @param $param
     * @return array
     */
    public static function typeFormat($format, $param)
    {
        $data = [];
        $first_key = array_key_first($format);
        $first_value = reset($format);
        foreach ($param as $key => $value) {
            if ($first_key == '*') {
                if (is_array($first_value) || is_array($param[$key])) {
                    $data[$key] = self::typeFormat($format['*'], $param[$key]);
                } else {
                    $data[$key] = $value;
                }
            } else {
                if (isset($format[$key])) {
                    if (is_array($format[$key])) {
                        $data[$key] = self::typeFormat($format[$key], $param[$key]);
                    } else {
                        $data[$key] = ($format[$key])($param[$key]);
                    }
                } else {
                    $data[$key] = $value;
                }
            }
        }
        return $data;
    }

}
