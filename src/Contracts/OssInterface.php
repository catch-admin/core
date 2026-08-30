<?php

declare(strict_types=1);

namespace Catch\Contracts;

interface OssInterface
{
    /**
     * 获取对象存储临时上传凭证。
     *
     * @return array<string, mixed>|string
     */
    public function token(): array|string;
}
