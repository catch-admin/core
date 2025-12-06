<?php

namespace Catch\Exceptions;

use Catch\Events\ReportException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SplFileObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Closure;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Create a new exception handler instance.
     */
    public function __construct(Container|Closure $container)
    {
        //
        if ($container instanceof Closure) {
            $container = $container();
        }

        parent::__construct($container);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // 自定义异常上报
            if (! $e instanceof WebhookException && method_exists($this->container, 'terminating')) {
                $this->container->terminating(function () use ($e) {
                    ReportException::dispatch($e);
                });
            }
        });
    }

    /**
     * render
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): JsonResponse|Response
    {
        $e = match (true) {
            $e instanceof ValidationException => new FailedException('验证错误: '.$e->getMessage()),
            $e instanceof ThrottlesExceptions => new FailedException('请求过于频繁，请稍后再试'),
            $e instanceof AuthenticationException => new FailedException('登录失效，请重新登录'),
            $e instanceof NotFoundHttpException => new Exception('路由 ['.$request->getRequestUri().'] 未找到或未注册，请检查路由是否正确'),
            $e instanceof MethodNotAllowedHttpException => new Exception('路由 HTTP 请求方法错误，当前请求方法: '.$request->getMethod().'，请检查对应路由 HTTP 请求方法是否正确'),
            $e instanceof QueryException => new Exception('数据库报错: '.$e->errorInfo[2]),
            $e instanceof ModelNotFoundException => new Exception('模型找不到: '.$e->getMessage()),
            default => new FailedException($e->getMessage()),
        };
        $response = parent::render($request, $e);

        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', '*');
        $response->header('Access-Control-Allow-Headers', '*');

        return $response;
    }

    /**
     * @return array|string[]
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        $message = $e->getMessage();

        return config('app.debug') ? [
            'message' => $message,
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->getTraceFileContent($e),
        ] : [
            'message' => $message ?: 'Server Error',
        ];
    }

    protected function getTraceFileContent(Throwable $e): mixed
    {
        $traces = collect($e->getTrace())
            ->map(fn ($trace) => Arr::except($trace, ['args']))
            ->map(function ($trace) {
                if (isset($trace['file'])) {
                    $trace['content'] = $this->getFileContents($trace['file'], $trace['line']);
                    $trace['path'] = $trace['file'];
                    $trace['file'] = Str::of($trace['file'])->replace(base_path().DIRECTORY_SEPARATOR, '');
                }

                return $trace;
            })
            ->all();

        array_unshift($traces, [
            'file' => Str::of($e->getFile())->replace(base_path().DIRECTORY_SEPARATOR, ''),
            'line' => $e->getLine(),
            'path' => $e->getFile(),
            'content' => $this->getFileContents($e->getFile(), $e->getLine()),
        ]);

        return $traces;
    }

    protected function getFileContents($filename, $line): array
    {
        $contents = [];
        $file = new SplFileObject($filename);

        $start = $line - 10;
        $start = max($start, 0);
        for ($i = $start; $i <= $line + 5; $i++) {
            $preLine = $i - 1;
            if ($preLine < 0) {
                continue;
            }
            $file->seek($i - 1);
            if ($content = $file->current()) {
                $contents[] = [
                    'line' => $i,
                    'content' => $content,
                ];
            }
        }

        return $contents;
    }
}
