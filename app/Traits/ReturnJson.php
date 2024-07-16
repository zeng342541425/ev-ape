<?php

namespace App\Traits;

use App\Constants\ReturnCode;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ReturnJson
{


    /**
     * 成功
     * @param mixed $data
     * @param string $msg
     * @return JsonResponse
     */
    public function success(mixed $data = null, string $msg = ''): JsonResponse
    {

        $msg = $msg ?: __('api_code.ok');

        return $this->returnJson($msg, compact('data'), ReturnCode::OK);
    }

    /**
     * 失敗
     * @param string $msg
     * @param mixed $data
     * @param int $code
     * @return JsonResponse
     */
    public function error(string $msg = '', mixed $data = null, int $code = ReturnCode::ERROR): JsonResponse
    {
        $msg = $msg ?: '請求失敗';

        return $this->returnJson($msg, compact('data'), $code);
    }


    /**
     * 返回 Json
     * @param string $msg
     * @param mixed $data
     * @param int|null $code
     * @return JsonResponse
     */
    public function returnJson(string $msg, mixed $data = [], ?int $code = null): JsonResponse
    {
        $code = $code ?? ReturnCode::OK;

        $data = array_merge(['code' => $code, 'message' => $msg], $data);

        return $this->respond($data);
    }

    /**
     * 響應 json
     * @param mixed $data
     * @param int $status
     * @param array $header
     * @return JsonResponse
     */
    public function respond(mixed $data, int $status = Response::HTTP_OK, array $header = []): JsonResponse
    {
        return response()->json($data, $status, $header);
    }
}
