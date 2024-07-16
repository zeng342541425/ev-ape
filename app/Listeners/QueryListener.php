<?php

namespace App\Listeners;

use DateTime;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class QueryListener
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        try {
            if (config('database.sql_debug')) {
                $sql = str_replace("?", "%s", $event->sql);
                foreach ($event->bindings as $i => $binding) {
                    if ($binding instanceof DateTime) {
                        $event->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } elseif (is_string($binding)) {
                        $event->bindings[$i] = "'$binding'";
                    }
                }
                if (!empty($event->bindings)) {
                    //$sql = vsprintf($sql, $event->bindings);
                    //dd($event->bindings);
                    $sql = $this->formatting($sql, $event->bindings);
                }

                $log = $sql.'  [ RunTime:'.$event->time.'ms ] ';
                Log::debug($log);
            }
        } catch (\Exception $exception) {
            Log::error('log sql error:'.$exception->getMessage());
        }
    }

    protected function formatting(string $sql, array $arr): string
    {
        foreach ($arr as $v) {
            $index = strpos($sql, '%s');
            $l = 2;
            $sql = substr($sql, 0, $index).$v.substr($sql, $index + $l);
        }
        return $sql;
    }
}
