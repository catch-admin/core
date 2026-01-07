<?php

namespace Catch\Support\Oss;

use Catch\Contracts\OssInterface;
use Catch\Exceptions\FailedException;
use Illuminate\Support\Facades\Http;

class Cos implements OssInterface
{
    public function __construct(
        protected string $secretId,
        protected string $secretKey,
        protected string $bucket,
        protected string $region,
        protected string $schema,
        protected string $domain
    ) {
    }

    public function token(): string|array
    {
        // TODO: Implement token() method.
        $config = $this->config();
        $appId = substr($config['bucket'], 1 + strripos($config['bucket'], '-'));
        $resource = [];
        foreach ($config['allowPrefix'] as $val) {
            $resource[] = 'qcs::cos:'.$config['region'].':uid/'.$appId.':'.$config['bucket'].'/'.$val;
        }

        $policy = [
            'version' => '2.0',
            'statement' => [
                [
                    'action' => $config['allowActions'],
                    'effect' => 'allow',
                    'resource' => $resource,
                    'condition' => json_encode($config['condition']),
                ],
            ],
        ];
        $params = [
            'SecretId' => $config['secretId'],
            'Timestamp' => time(),
            'Nonce' => rand(10000, 20000),
            'Action' => 'GetFederationToken',
            'DurationSeconds' => $config['durationSeconds'],
            'Version' => '2018-08-13',
            'Name' => 'cos',
            'Region' => $config['region'],
            'Policy' => urlencode(str_replace('\\/', '/', json_encode($policy))),
        ];
        $params['Signature'] = $this->getSignature($params, $config['secretKey'], 'POST', $config);
        $url = $config['url'];
        $response = Http::withBody($this->buildQuery($params, false))->post($url);

        if ($response->ok()) {
            return array_change_key_case($response['Response']);
        }

        throw new FailedException('获取临时密钥失败');
    }

    protected function getSignature(array $params, string $key, string $method, array $config): string
    {
        $host = $config['domain'];

        $formatString = $method.$host.'/?'.$this->buildQuery($params);
        $sign = hash_hmac('sha1', $formatString, $key);

        return base64_encode(pack('H'.strlen($sign), $sign));
    }

    protected function buildQuery(array $params, bool $encode = true): string
    {
        ksort($params);
        $arr = [];
        foreach ($params as $key => $val) {
            $arr[] = $key.'='.($encode ? $val : rawurlencode($val));
        }

        return implode('&', $arr);
    }

    protected function config(): array
    {
        return [
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            'proxy' => '',
            'secretId' => $this->secretId,
            'secretKey' => $this->secretKey,
            'bucket' => $this->bucket,
            'region' => $this->region,
            'durationSeconds' => 3600,
            'allowPrefix' => ['*'],
            'allowActions' => [
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload',
            ],
            'condition' => [
                'ip_equal' => [
                    'qcs:ip' => [
                        '10.217.182.3/24',
                        '111.21.33.72/24',
                    ],
                ],
            ],
        ];
    }
}
