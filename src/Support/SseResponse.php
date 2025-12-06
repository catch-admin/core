<?php

namespace Catch\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE 响应类
 *
 * 封装 Server-Sent Events 流式响应的创建和事件发送
 * 可在整个项目中通用使用
 */
class SseResponse
{
    protected array $events = [];

    /**
     * 创建 SSE 流式响应
     *
     * @param callable $handler 业务处理函数
     * @return StreamedResponse
     */
    public static function create(callable $handler): StreamedResponse
    {
        set_time_limit(0);

        return response()->stream(function () use ($handler) {
            static::prepareOutputBuffer();

            $sse = new static();

            try {
                $handler($sse);
            } catch (\Throwable $e) {
                $sse->error('操作失败: ' . $e->getMessage());
            }
        }, 200, static::getHeaders());
    }

    /**
     * 发送日志事件
     *
     * @param string $message 日志消息
     * @param string $type 日志类型 (stdout/stderr)
     * @return self
     */
    public function log(string $message, string $type = 'stdout'): self
    {
        return $this->send('log', compact('message', 'type'));
    }

    /**
     * 发送进度事件
     *
     * @param string $step 步骤名称
     * @param int $percent 进度百分比 (0-100)
     * @param string $message 进度消息
     * @return self
     */
    public function progress(string $step, int $percent, string $message): self
    {
        return $this->send('progress', compact('step', 'percent', 'message'));
    }

    /**
     * 发送完成事件
     *
     * @param array $data 附加数据
     * @return self
     */
    public function complete(array $data = []): self
    {
        return $this->send('complete', array_merge(['success' => true], $data));
    }

    /**
     * 发送错误事件
     *
     * @param string $message 错误消息
     * @return self
     */
    public function error(string $message): self
    {
        return $this->send('error', compact('message'));
    }

    /**
     * 发送自定义事件
     *
     * @param string $event 事件名称
     * @param array $data 事件数据
     * @return self
     */
    public function send(string $event, array $data): self
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();

        return $this;
    }

    /**
     * 准备输出缓冲
     *
     * @return void
     */
    protected static function prepareOutputBuffer(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_implicit_flush(true);
    }

    /**
     * 获取 SSE 响应头
     *
     * @return array
     */
    protected static function getHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];
    }
}
