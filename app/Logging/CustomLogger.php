<?php

namespace App\Logging;

use App\Listeners\QueryListener;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\UidProcessor;

class CustomLogger
{
    /**
     * 创建一个自定义 Monolog 实例。
     *
     * @param array $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $request = request();

        // 日志路径 默认 /storage/logs
        $folder = 'other';
        $path = $config['path'] ?? storage_path('logs');
        if ($request->routeIs('admin.*')) {
            $folder = 'admin';
        } elseif ($request->routeIs('api.*')) {

            $folder = 'api';
//            $webapp = strtolower(request()->header('webapp', ''));
//            if (in_array($webapp, ['web', 'ios', 'android'])) {
//                $folder = $webapp;
//            }

        } elseif (php_sapi_name() == 'cli') {
            $folder = 'cli';
        }
        $path .= DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;

        // 文件路徑 比如：...\storage\logs\admin\app.cli.20230222.log
        $file = $path . 'app.' . php_sapi_name() . '.' . date('Ymd') . '.log';

        // 日志格式
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, "Y-m-d H:i:s.u", true, true);
//        $formatter->setJsonPrettyPrint(true);

        $level = $this->getLevel($config['level'] ?? 'debug');

        // 記錄器
        $stream = new StreamHandler($file, $level);
        $stream->setFormatter($formatter);

        $logger = new Logger($config['name'] ?? 'custom');

        // 生成日志唯一ID
        $logger->pushProcessor(new UidProcessor(32));

        // 添加日誌調用的來源 line/file/class/method
        $logger->pushProcessor(new IntrospectionProcessor($level, [
            'Illuminate\\',
            'Spatie\\',
            QueryListener::class
        ]));
        $logger->pushHandler($stream);
        $logger->pushHandler(new FirePHPHandler());
        return $logger;
    }

    /**
     * 获取级别
     * @param $level
     * @return int|void
     */
    public function getLevel($level)
    {
        switch ($level) {
            case 'debug':
                return Logger::DEBUG; // 詳細調試信息
            case 'info':
                return Logger::INFO; // Interesting events
            case 'notice':
                return Logger::NOTICE; // Uncommon events
            case 'warning':
                return Logger::WARNING;
            case 'error':
                return Logger::ERROR;
            case 'critical':
                return Logger::CRITICAL; // Critical conditions
            case 'alert':
                return Logger::ALERT; // Action must be taken immediately
            case 'emergency':
                return Logger::EMERGENCY; // Urgent alert.
        }
    }


}
