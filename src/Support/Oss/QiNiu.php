<?php

namespace Catch\Support\Oss;

use Catch\Contracts\OssInterface;

class QiNiu implements OssInterface
{
    public function __construct(
        protected string $accessKey,
        protected string $secretKey,
        protected string $scope,
        protected string $body = ''
    ) {
    }

    public function token(): string
    {
        // TODO: Implement token() method.
        $policy = [
            'scope' => $this->scope,
            'deadline' => time() + 7200,
            'returnBody' => $this->body(),
        ];
        $encodePolicy = $this->encode(json_encode($policy));
        $sign = hash_hmac('sha1', $encodePolicy, $this->secretKey, true);

        return sprintf('%s:%s:%s', $this->accessKey, $this->encode($sign), $encodePolicy);
    }

    protected function body(): string
    {
        if ($this->body) {
            return $this->body;
        }

        return '{
	      "name":$(fname),
	      "size":$(fsize),
	      "w":$(imageInfo.width),
	      "h":$(imageInfo.height),
	      "hash":$(etag)
	    }';
    }

    /**
     * @return array|string|string[]
     */
    protected function encode(string $policy): array|string
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($policy));
    }
}
