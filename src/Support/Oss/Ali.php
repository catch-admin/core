<?php

namespace Catch\Support\Oss;

use Catch\Contracts\OssInterface;
use Illuminate\Support\Facades\Http;

class Ali implements OssInterface
{
    protected string $stsUrl = 'https://sts.aliyuncs.com';

    public function __construct(
        protected string $accessKey,
        protected string $secretKey,
        protected string $bucket,  // Bucket 表示访问域名带上文件路径，例如qdxcy
        protected string $roleArn, // 指定角色的 ARN ，角色策略权限
        protected string $roleSessionName, // 用户自定义参数。此参数用来区分不同的 token，可用于用户级别的访问审计。格式：^[a-zA-Z0-9\.@\-_]+$
        protected string $region, // region表示您申请OSS服务所在的地域，例如 oss-cn-hangzhou。
        protected int $contentLength,
        protected array $allowTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif']
    ) {}

    public function token(): array|string
    {
        $action = 'AssumeRole'; // 通过扮演角色接口获取令牌
        date_default_timezone_set('UTC');
        $parameters = [
            'Format' => 'JSON',
            'Version' => '2015-04-01',
            'AccessKeyId' => $this->accessKey,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce' => uniqid(mt_rand(), true),
            'Action' => $action,
            'RoleArn' => $this->roleArn,
            'RoleSessionName' => $this->roleSessionName,
            'DurationSeconds' => 3600,
            'Timestamp' => gmdate("Y-m-d\TH:i:s\Z"),
        ];

        $parameters['Signature'] = $this->getSignature($parameters);

        $response = Http::withOptions(['verify' => false])->get($this->stsUrl, $parameters);
        if ($response->ok()) {
            return $response->json();
        }

        return [];
    }

    protected function getSignature($parameters): string
    {
        ksort($parameters);

        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . rawurlencode($key) . '=' . rawurlencode($value);
        }

        $stringToSign = 'GET&%2F&' . rawurlencode(substr($canonicalizedQueryString, 1));

        return base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey . '&', true));

    }
}
