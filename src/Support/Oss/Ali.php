<?php

declare(strict_types=1);

namespace Catch\Support\Oss;

use Catch\Contracts\OssInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

class Ali implements OssInterface
{
    protected string $stsUrl = 'https://sts.aliyuncs.com';

    /**
     * @param  list<string>  $allowTypes
     */
    public function __construct(
        protected readonly string $accessKey,
        protected readonly string $secretKey,
        protected readonly string $bucket,
        protected readonly string $roleArn,
        protected readonly string $roleSessionName,
        protected readonly string $region,
        protected readonly int $contentLength,
        protected readonly array $allowTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function token(): array
    {
        $parameters = [
            'Format' => 'JSON',
            'Version' => '2015-04-01',
            'AccessKeyId' => $this->accessKey,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce' => bin2hex(random_bytes(16)),
            'Action' => 'AssumeRole',
            'RoleArn' => $this->roleArn,
            'RoleSessionName' => $this->roleSessionName,
            'DurationSeconds' => 3600,
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        $parameters['Signature'] = $this->getSignature($parameters);

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->get($this->stsUrl, $parameters);
        } catch (Throwable) {
            return [];
        }

        return $response->successful() ? $response->json() : [];
    }

    /**
     * @param  array<string, int|string>  $parameters
     */
    protected function getSignature(array $parameters): string
    {
        ksort($parameters);

        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&'.rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        $stringToSign = 'GET&%2F&'.rawurlencode(substr($canonicalizedQueryString, 1));

        return base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey.'&', true));
    }
}
