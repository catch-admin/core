<?php

namespace Catch\Listeners;

use Catch\Enums\Code;
use Catch\Support\ResponseBuilder;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Http\JsonResponse;

class RequestHandledListener
{
    /**
     * Handle the event.
     *
     * @param RequestHandled $event
     * @return void
     */
    public function handle(RequestHandled $event): void
    {
        if (isRequestFromDashboard()) {
            $response = $event->response;

            // 自定义响应内容
            if ($response instanceof ResponseBuilder) {
                $event->response = $response();
            } else {
                // 标准响应
                if ($response instanceof JsonResponse) {
                    $exception = $response->exception;

                    if ($response->getStatusCode() == SymfonyResponse::HTTP_OK && !$exception) {
                        $response->setData($this->formatData($response->getData()));
                    }
                }
            }
        }
    }

    /**
     * @param mixed $data
     * @return array
     */
    protected function formatData(mixed $data): array
    {
        return array_merge(
            [
                'code' => Code::SUCCESS->value(),
                'message' => Code::SUCCESS->message(),
            ],

            format_response_data($data)
        );
    }
}
