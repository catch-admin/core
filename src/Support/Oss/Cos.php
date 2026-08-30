<?php

declare(strict_types=1);

namespace Catch\Support\Oss;

use Catch\Contracts\OssInterface;
use Catch\Exceptions\FailedException;
use Illuminate\Support\Facades\Http;
use Throwable;

class Cos implements OssInterface
{
    public function __construct(
        protected readonly string $secretId,
        protected readonly string $secretKey,
        protected readonly string $bucket,
        protected readonly string $region,
        protected readonly string $schema,
        protected readonly string $domain,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function token(): array
    {
        $config = $this->config();
        $appId = substr($config['bucket'], 1 + strripos($config['bucket'], '-'));
        $resources = [];

        foreach ($config['allowPrefix'] as $prefix) {
            $resources[] = 'qcs::cos:'.$config['region'].':uid/'.$appId.':'.$config['bucket'].'/'.$prefix;
        }

        $policy = [
            'version' => '2.0',
            'statement' => [[
                'action' => $config['allowActions'],
                'effect' => 'allow',
                'resource' => $resources,
            ]],
        ];

        $parameters = [
            'SecretId' => $config['secretId'],
            'Timestamp' => time(),
            'Nonce' => random_int(10000, 99999),
            'Action' => 'GetFederationToken',
            'DurationSeconds' => $config['durationSeconds'],
            'Version' => '2018-08-13',
            'Name' => 'cos',
            'Region' => $config['region'],
            'Policy' => urlencode(str_replace('\/', '/', json_encode($policy, JSON_THROW_ON_ERROR))),
        ];

        $parameters['Signature'] = $this->getSignature($parameters, $config['secretKey'], 'POST', $config);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withBody($this->buildQuery($parameters, false), 'application/x-www-form-urlencoded')
                ->post($config['url']);
        } catch (Throwable) {
            throw new FailedException('获取腾讯云临时密钥失败');
        }

        $credentials = $response->json('Response');
        if (! $response->successful() || ! is_array($credentials)) {
            throw new FailedException('获取腾讯云临时密钥失败');
        }

        return array_change_key_case($credentials);
    }

    /**
     * @param  array<string, int|string>  $parameters
     * @param  array<string, mixed>  $config
     */
    protected function getSignature(array $parameters, string $key, string $method, array $config): string
    {
        $formatString = $method.$config['domain'].'/?'.$this->buildQuery($parameters);
        $signature = hash_hmac('sha1', $formatString, $key);

        return base64_encode(pack('H'.strlen($signature), $signature));
    }

    /**
     * @param  array<string, int|string>  $parameters
     */
    protected function buildQuery(array $parameters, bool $encode = true): string
    {
        ksort($parameters);

        $query = [];
        foreach ($parameters as $key => $value) {
            $query[] = $key.'='.($encode ? $value : rawurlencode((string) $value));
        }

        return implode('&', $query);
    }

    /**
     * @return array{
     *     url: string,
     *     domain: string,
     *     secretId: string,
     *     secretKey: string,
     *     bucket: string,
     *     region: string,
     *     durationSeconds: int,
     *     allowPrefix: list<string>,
     *     allowActions: list<string>
     * }
     */
    protected function config(): array
    {
        return [
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            'secretId' => $this->secretId,
            'secretKey' => $this->secretKey,
            'bucket' => $this->bucket,
            'region' => $this->region,
            'durationSeconds' => 3600,
            'allowPrefix' => ['*'],
            'allowActions' => [
                'name/cos:PutObject',
                'name/cos:PostObject',
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload',
            ],
        ];
    }
}
