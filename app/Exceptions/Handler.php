<?php

namespace App\Exceptions;

use App\Constants\Constant;
use App\Constants\ReturnCode;
use App\Models\Backend\System\ExceptionError;
use App\Traits\ReturnJson;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use Predis\Connection\ConnectionException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{

    use ReturnJson;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * UnauthorizedHttpException
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function isUnauthorizedHttpException(Throwable $exception): bool
    {
        return $exception instanceof AuthorizationException ||
            $exception instanceof UnauthorizedHttpException ||
            $exception instanceof AuthenticationException;
    }


    /**
     * ConnectionException
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function isRedisConnectException(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException;
    }

    /**
     * 驗證器異常
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function isValidationException(Throwable $exception): bool
    {
        return $exception instanceof ValidationException;
    }

    /**
     * AuthorizationException
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function isAuthorizationException(Throwable $exception): bool
    {
        return $exception instanceof UnauthorizedException;
    }

    /**
     * ThrottleRequestsException
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function isThrottleRequestsException(Throwable $exception): bool
    {
        return $exception instanceof ThrottleRequestsException;
    }

    protected function isNotFoundHttpException(Throwable $exception): bool
    {
        return $exception instanceof NotFoundHttpException;
    }

    protected function isMethodNotAllowedHttpException(Throwable $exception): bool
    {
        return $exception instanceof MethodNotAllowedHttpException;
    }

    protected function isSuspiciousOperationException(Throwable $exception): bool
    {
        return $exception instanceof SuspiciousOperationException;
    }


    /**
     * 令牌已過期 無法再刷新
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function isTokenExpiredException(Throwable $exception): bool
    {
        return $exception instanceof TokenExpiredException;
    }

    /**
     * @param Throwable $exception
     */
    protected function exceptionError(Throwable $exception)
    {
        if (!$this->isUnauthorizedHttpException($exception) && !$this->isValidationException($exception) &&
            !$this->isThrottleRequestsException($exception) && !$this->isNotFoundHttpException($exception) &&
            !$this->isAuthorizationException($exception) && !$this->isMethodNotAllowedHttpException($exception) &&
            !$this->isSuspiciousOperationException($exception) &&
            !$this->isTokenExpiredException($exception)) {
            try {
                ExceptionError::create([
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                    'trace_as_string' => $exception->getTraceAsString(),
                    'uid' => get_logger_uid(),
                    'is_solve' => Constant::COMMON_IS_NO
                ]);
            } catch (Exception $e) {
                Log::error($e);
            }
        }
    }



    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     *
     * @throws Exception|Throwable
     */
    public function report(Throwable $e)
    {
        $this->exceptionError($e);

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {

        // TODO: 添加不記錄 Exception

        // 路由未找到
        if ($this->isNotFoundHttpException($e)) {
            return $this->error(__('api_code.not_found'));
        }

        // 請求方法 Method 不被允許
        if ($this->isMethodNotAllowedHttpException($e)) {
            return $this->error(__('api_code.method_not_allowed'));
        }

        // 請求次數限制 限流
        if ($this->isThrottleRequestsException($e)) {
            return $this->error(__('api_code.too_many_request'));
        }

        // 驗證器異常
        if ($this->isValidationException($e)) {
            return $this->error($e->getMessage());
        }

        // 未授權
        if ($this->isUnauthorizedHttpException($e)) {
            return $this->error(__('api_code.unauthorized'), null, ReturnCode::NEED_LOGIN);
        }

        // 令牌已過期 無法再刷新
        if ($this->isTokenExpiredException($e)) {
            return $this->error(__('api_code.unauthorized'), null, ReturnCode::NEED_LOGIN);
        }

        // 權限禁止
        if ($this->isAuthorizationException($e)) {
            return $this->error(__('api_code.forbidden'));
        }

        // Redis 連接錯誤
        if ($this->isRedisConnectException($e)) {
            return $this->error(__('api_code.server_connect_fail'));
        }

        if (App::environment('local')) {
            return parent::render($request, $e);
        }

        return $this->error(__('api_code.server_error'));
    }
}
