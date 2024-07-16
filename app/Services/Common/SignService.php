<?php

namespace App\Services\Common;

class SignService
{
    /**
     * @Description： 生成簽名
     * @param array $param
     * @return string
     * @author:
     */
    public static function generatedSign(array $param = []): string
    {
        $values = array_values($param);

        // 字典序排序
        sort($values,SORT_STRING);
        $string = implode('', $values);

        // sha1簽名
        return sha1($string);
    }

    /**
     * @Description：驗證簽名
     * @param array $param
     * @param string $signature
     * @return bool
     * @author:
     */
    public static function checkSignature(array $param = [], string $signature = ''): bool
    {
        return self::generatedSign($param) == $signature;
    }
}
