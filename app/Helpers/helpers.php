<?php


use App\Constants\Constant;
use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\Rule;
use Monolog\Processor\UidProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

if (!function_exists('get_logger_uid')) {
    /**
     * 獲取 日誌 Uid
     * @return string
     */
    function get_logger_uid()
    {
        $logger_processors = logger()->getProcessors();
        if (!empty($logger_processors)) {
            foreach ($logger_processors as $processor) {
                if ($processor instanceof UidProcessor) {
                    return $processor->getUid();
                }
            }
        }
        return '';
    }
}

if (!function_exists('order_direction')) {
    /**
     * 排序方向
     * @param $direction
     * @return int[]|string|string[]|null
     */
    function order_direction($direction = null)
    {
        $order_direction = [
            'descending' => 'desc',
            'ascending' => 'asc',
            'desc' => 'desc',
            'asc' => 'asc'
        ];
        if (!isset($direction)) {
            return array_keys($order_direction);
        }
        return $order_direction[$direction] ?? null;
    }

}

if (!function_exists('rule_in_order_direction')) {
    /**
     * 權限規則 in 排序選項
     * @return \Illuminate\Validation\Rules\In
     */
    function rule_in_order_direction()
    {
        return Rule::in([
            'descending',
            'ascending',
            'desc',
            'asc',
        ]);
    }
}

if (!function_exists('rule_in_is')) {
    /**
     * 權限規則 in 是否
     * @return \Illuminate\Validation\Rules\In
     */
    function rule_in_is()
    {
        return Rule::in([
            Constant::COMMON_IS_YES,
            Constant::COMMON_IS_NO,
        ]);
    }
}

if (!function_exists('rule_in_status')) {
    /**
     * 權限規則 in 狀態-啟用禁用
     * @return \Illuminate\Validation\Rules\In
     */
    function rule_in_status()
    {
        return Rule::in([
            Constant::COMMON_STATUS_ENABLE,
            Constant::COMMON_STATUS_DISABLE,
        ]);
    }
}

if (!function_exists('clear_log_instance')) {
    /**
     * 清除日誌
     */
    function clear_log_instance()
    {
        Facade::clearResolvedInstance('log');
        app()->forgetInstance('log');
    }
}
