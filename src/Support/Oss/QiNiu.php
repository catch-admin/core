<?php

declare(strict_types=1);

namespace Catch\Support\Oss;

use Catch\Contracts\OssInterface;

class QiNiu implements OssInterface
{
    public function __construct(
        protected readonly string $accessKey,
        protected readonly string $secretKey,
        protected readonly string $scope,
        protected readonly string $returnBody = '',
    ) {
    }

    public function token(): string
    {
        $policy = [
            'scope' => $this->scope,
            'deadline' => time() + 7200,
            'returnBody' => $this->body(),
        ];

        $encodedPolicy = $this->encode(json_encode($policy, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha1', $encodedPolicy, $this->secretKey, true);

        return sprintf('%s:%s:%s', $this->accessKey, $this->encode($signature), $encodedPolicy);
    }

    protected function body(): string
    {
        if ($this->returnBody !== '') {
            return $this->returnBody;
        }

        return <<<'JSON'
{
  "name": $(fname),
  "size": $(fsize),
  "w": $(imageInfo.width),
  "h": $(imageInfo.height),
  "hash": $(etag)
}
JSON;
    }

    protected function encode(string $value): string
    {
        return strtr(base64_encode($value), '+/', '-_');
    }
}
