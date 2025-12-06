<?php
namespace Catch\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class Terminal
{
    protected array $env = [];

    protected string|null $cwd = null;

    public function __construct(
        protected string|array $command
    ){
        $this->env = getenv() ?: [];
    }

    /**
     * @param string|array $command
     * @return static
     */
    public static function command(string|array $command): static
    {
        return new static($command);
    }

    /**
     * @param callable|null $callback
     * @return ProcessResult|\Illuminate\Process\ProcessResult
     */
    public function run(?callable $callback = null)
    {
        return Process::forever()
            ->path($this->cwd ??base_path())
            ->env($this->env)
            ->run($this->command, function (string $type, string $output) use ($callback) {
                $cleanOutput = $this->processOutput($output);

                if ($callback) {
                    $callback($cleanOutput, $type);
                }
            });
    }

    /**
     * 在前端目录执行
     *
     * @param callable|null $callback
     * @return ProcessResult|\Illuminate\Process\ProcessResult
     */
    public function runInWeb(?callable $callback = null): \Illuminate\Process\ProcessResult|ProcessResult
    {
        return $this->setCwd(base_path('web'))->run($callback);
    }

    /**
     * 设置执行目录
     *
     * @param string $cwd
     * @return $this
     */
    public function setCwd(string $cwd): static
    {
        $this->cwd = $cwd;

        return $this;
    }


    /**
     * 获取环境变量
     */
    public function setProcessEnv(array $env): static
    {
        $this->env = array_merge($this->env, $env);

        Log::info(json_encode($this->env, JSON_PRETTY_PRINT));
        return $this;
    }

    /**
     * 处理输出（编码转换 + ANSI 过滤）
     */
    protected function processOutput(string $output): string
    {
        $decoded = mb_convert_encoding($output, 'utf8', ['utf-8', 'gbk', 'gb2312', 'gb18030', 'big5']);

        return preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $decoded);
    }
}
