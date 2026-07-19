<?php
/**
 * 阿里云 OSS 服务类
 * 使用 cURL 直接调用 REST API，V4 签名鉴权
 *
 * 依赖：PHP cURL 扩展、openssl 扩展
 * 参考：https://help.aliyun.com/zh/oss/developer-reference/recommend-to-use-signature-version-4
 */

class OssService {
    private string $accessKeyId;
    private string $accessKeySecret;
    private string $bucket;
    private string $endpoint;
    private string $region;
    private string $cdnDomain;

    /**
     * @param array $config 解密后的 OSS 配置（由 getOssConfig() 返回）
     */
    public function __construct(array $config) {
        $this->accessKeyId     = $config['access_key_id']     ?? '';
        $this->accessKeySecret = $config['access_key_secret'] ?? '';
        $this->bucket          = $config['bucket']            ?? '';
        $this->endpoint        = $config['endpoint']          ?? '';
        $this->region          = $config['region']            ?? '';
        $this->cdnDomain       = $config['cdn_domain']        ?? '';
    }

    /**
     * 检查 OSS 配置是否完整
     */
    public function isConfigured(): bool {
        return $this->accessKeyId !== ''
            && $this->accessKeySecret !== ''
            && $this->bucket !== ''
            && $this->endpoint !== ''
            && $this->region !== '';
    }

    // =====================================================
    // 公共业务方法
    // =====================================================

    /**
     * 上传文件到 OSS（PUT Object）
     *
     * @param string $objectKey   OSS 对象路径（如 media/2024/01/abc.png）
     * @param string $content     文件二进制内容
     * @param string $contentType MIME 类型
     * @return array ['success' => bool, 'url' => string, 'error' => string]
     */
    public function putObject(string $objectKey, string $content, string $contentType): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'url' => '', 'error' => 'OSS 未配置完整'];
        }

        $host = $this->bucket . '.' . $this->endpoint;
        $url  = 'https://' . $host . '/' . ltrim($objectKey, '/');

        $dateTime = gmdate('Ymd\THis\Z');
        $dateShort = substr($dateTime, 0, 8);

        $headers = [
            'content-type'       => $contentType,
            'host'               => $host,
            'x-oss-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-oss-date'         => $dateTime,
        ];

        // V4 虚拟主机风格：CanonicalURI 必须包含 /{bucket} 前缀
        $authorization = $this->buildAuthorization('PUT', '/' . $this->bucket . '/' . ltrim($objectKey, '/'), [], $headers, 'UNSIGNED-PAYLOAD');

        $requestHeaders = [
            'Authorization: ' . $authorization,
            'Content-Type: ' . $contentType,
            'Host: ' . $host,
            'x-oss-content-sha256: UNSIGNED-PAYLOAD',
            'x-oss-date: ' . $dateTime,
        ];

        $response = $this->sendRequest('PUT', $url, $requestHeaders, $content);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $fileUrl = $this->getObjectUrl($objectKey);
            return ['success' => true, 'url' => $fileUrl, 'error' => ''];
        }

        $errorMsg = $this->parseOssError($response['body'], $response['http_code']);
        error_log('OSS putObject 失败: HTTP ' . $response['http_code'] . ' - ' . $errorMsg);

        // 403 时携带 debug 信息（含服务端 CanonicalRequest）便于对比签名差异
        $debug = null;
        if ($response['http_code'] === 403 && $response['body'] !== '') {
            $debug = [
                'http_code' => $response['http_code'],
                'oss_error' => $errorMsg,
                'raw_body'  => substr($response['body'], 0, 2000),
                'object_key'=> $objectKey,
                'content_type' => $contentType,
                'host'      => $host,
            ];
            // 从 OSS 错误 XML 中提取服务端 CanonicalRequest
            $xml = @simplexml_load_string($response['body']);
            if ($xml && isset($xml->CanonicalRequest)) {
                $debug['server_canonical_request'] = (string) $xml->CanonicalRequest;
            }
            // 生成客户端 CanonicalRequest 供对比
            $cr = $this->buildCanonicalRequest('PUT', '/' . $this->bucket . '/' . ltrim($objectKey, '/'), [], $headers, 'UNSIGNED-PAYLOAD');
            $debug['client_canonical_request'] = $cr;
        }

        return ['success' => false, 'url' => '', 'error' => $errorMsg, 'debug' => $debug];
    }

    /**
     * 删除 OSS 对象（DELETE Object）
     *
     * @param string $objectKey OSS 对象路径
     * @return bool 是否删除成功
     */
    public function deleteObject(string $objectKey): bool {
        if (!$this->isConfigured()) return false;

        $host = $this->bucket . '.' . $this->endpoint;
        $url  = 'https://' . $host . '/' . ltrim($objectKey, '/');

        $dateTime = gmdate('Ymd\THis\Z');

        $headers = [
            'host'               => $host,
            'x-oss-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-oss-date'         => $dateTime,
        ];

        $authorization = $this->buildAuthorization('DELETE', '/' . $this->bucket . '/' . ltrim($objectKey, '/'), [], $headers, 'UNSIGNED-PAYLOAD');

        $requestHeaders = [
            'Authorization: ' . $authorization,
            'Host: ' . $host,
            'x-oss-content-sha256: UNSIGNED-PAYLOAD',
            'x-oss-date: ' . $dateTime,
        ];

        $response = $this->sendRequest('DELETE', $url, $requestHeaders);
        return $response['http_code'] >= 200 && $response['http_code'] < 300;
    }

    /**
     * 检查 OSS 对象是否存在（HEAD Object）
     *
     * @param string $objectKey OSS 对象路径
     * @return bool 是否存在
     */
    public function objectExists(string $objectKey): bool {
        if (!$this->isConfigured()) return false;

        $host = $this->bucket . '.' . $this->endpoint;
        $url  = 'https://' . $host . '/' . ltrim($objectKey, '/');

        $dateTime = gmdate('Ymd\THis\Z');

        $headers = [
            'host'               => $host,
            'x-oss-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-oss-date'         => $dateTime,
        ];

        $authorization = $this->buildAuthorization('HEAD', '/' . $this->bucket . '/' . ltrim($objectKey, '/'), [], $headers, 'UNSIGNED-PAYLOAD');

        $requestHeaders = [
            'Authorization: ' . $authorization,
            'Host: ' . $host,
            'x-oss-content-sha256: UNSIGNED-PAYLOAD',
            'x-oss-date: ' . $dateTime,
        ];

        $response = $this->sendRequest('HEAD', $url, $requestHeaders);
        return $response['http_code'] === 200;
    }

    /**
     * 连接测试（HEAD Bucket）
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'OSS 配置不完整，请检查 AK/SK/Bucket/Endpoint/Region'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP cURL 扩展未启用'];
        }

        $host = $this->bucket . '.' . $this->endpoint;
        // 用 GET + max-keys=1 代替 HEAD，确保 OSS 返回完整错误响应体
        // （max-keys 最小值为 1，0 会被拒绝；1 开销最小且能验证连接）
        $url  = 'https://' . $host . '/?max-keys=1';

        $dateTime = gmdate('Ymd\THis\Z');

        $headers = [
            'host'               => $host,
            'x-oss-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-oss-date'         => $dateTime,
        ];

        $queryParams = ['max-keys' => '1'];
        $authorization = $this->buildAuthorization('GET', '/' . $this->bucket . '/', $queryParams, $headers, 'UNSIGNED-PAYLOAD');

        $requestHeaders = [
            'Authorization: ' . $authorization,
            'Host: ' . $host,
            'x-oss-content-sha256: UNSIGNED-PAYLOAD',
            'x-oss-date: ' . $dateTime,
        ];

        $response = $this->sendRequest('GET', $url, $requestHeaders);
        $code = $response['http_code'];

        // 构建调试上下文（帮助定位问题）
        $debug = [
            'endpoint'  => $this->endpoint,
            'bucket'    => $this->bucket,
            'region'    => $this->region,
            'ak_mask'   => substr($this->accessKeyId, 0, 4) . '****' . substr($this->accessKeyId, -4),
            'host'      => $host,
            'http_code' => $code,
        ];

        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'message' => '连接成功，Bucket 可访问', 'debug' => $debug];
        }

        // 解析 OSS 返回的详细错误信息
        $ossError = $this->parseOssError($response['body'], $code);
        $debug['oss_error'] = $ossError;
        $debug['raw_body']  = $response['body'] !== '' ? substr($response['body'], 0, 500) : '(empty)';

        // 根据具体错误码给出针对性提示
        $message = $this->buildErrorMessage($code, $ossError, $debug);

        // 诊断日志：记录客户端生成的签名信息，用于与服务端对比
        if ($code === 403) {
            $dateTime  = $headers['x-oss-date'] ?? '';
            $dateShort = substr($dateTime, 0, 8);
            $scope     = $dateShort . '/' . $this->region . '/oss/aliyun_v4_request';
            $cr        = $this->buildCanonicalRequest('GET', '/' . $this->bucket . '/', ['max-keys' => '1'], $headers, 'UNSIGNED-PAYLOAD');
            $sts       = "OSS4-HMAC-SHA256\n" . $dateTime . "\n" . $scope . "\n" . hash('sha256', $cr);
            $signKey   = $this->deriveSigningKey($dateShort);
            $sig       = hash_hmac('sha256', $sts, $signKey);
            error_log("[OSS V4 签名诊断] CanonicalURI=/{" . $this->bucket . "/} CanonicalRequest:\n" . $cr);
            error_log("[OSS V4 签名诊断] StringToSign:\n" . $sts);
            error_log("[OSS V4 签名诊断] Signature=" . $sig);
            $debug['client_canonical_request'] = $cr;
            $debug['client_signature'] = $sig;
        }

        return ['success' => false, 'message' => $message, 'debug' => $debug];
    }

    /**
     * 根据 HTTP 状态码和 OSS 错误码构建友好提示
     */
    private function buildErrorMessage(int $code, string $ossError, array $debug): string {
        // 尝试从 ossError 中提取 ErrorCode（如 SignatureDoesNotMatch）
        $errorCode = '';
        if (strpos($ossError, ':') !== false) {
            $errorCode = trim(explode(':', $ossError, 2)[0]);
        }

        if ($code === 403) {
            if ($errorCode === 'SignatureDoesNotMatch') {
                return '签名不匹配（SignatureDoesNotMatch）。SK 可能不正确，或 AK/SK 在保存时加密/解密异常。'
                     . ' 请尝试重新填写 AK/SK 并保存后再测试。';
            }
            if ($errorCode === 'AccessDenied') {
                return '访问被拒绝（AccessDenied）。AK 对应的账号没有该 Bucket 的操作权限，'
                     . '或 Bucket 策略拒绝了当前请求。';
            }
            if ($errorCode === 'InvalidAccessKeyId') {
                return 'AccessKey ID 无效（InvalidAccessKeyId）。请检查 AK 是否正确，是否已启用。';
            }
            return '权限拒绝（403）。OSS 返回：' . $ossError;
        }
        if ($code === 404) {
            return 'Bucket 不存在（404）。请检查 Bucket 名称（' . $debug['bucket'] . '）和 Endpoint（' . $debug['endpoint'] . '）是否匹配。';
        }
        if ($code === 0) {
            return '网络不通或 DNS 解析失败。请检查 Endpoint 是否正确，Host: ' . $debug['host'];
        }
        return 'HTTP ' . $code . '，OSS 返回：' . $ossError;
    }

    /**
     * 生成对象的访问 URL（CDN 优先，回退到 OSS 直访）
     *
     * @param string $objectKey OSS 对象路径
     * @return string 完整 URL
     */
    public function getObjectUrl(string $objectKey): string {
        $path = ltrim($objectKey, '/');
        if ($this->cdnDomain !== '') {
            return 'https://' . rtrim($this->cdnDomain, '/') . '/' . $path;
        }
        return 'https://' . $this->bucket . '.' . $this->endpoint . '/' . $path;
    }

    // =====================================================
    // V4 签名核心
    // =====================================================

    /**
     * 构造 V4 Authorization Header
     *
     * @param string $method      HTTP 方法
     * @param string $path        请求路径（如 /media/2024/01/abc.png）
     * @param array  $queryParams 查询参数（关联数组，已排序）
     * @param array  $headers     参与签名的 headers（小写 key => value）
     * @param string $payloadHash payload 哈希或 "UNSIGNED-PAYLOAD"
     * @return string Authorization header 值
     */
    private function buildAuthorization(string $method, string $path, array $queryParams, array $headers, string $payloadHash): string {
        $dateTime  = $headers['x-oss-date'] ?? gmdate('Ymd\THis\Z');
        $dateShort = substr($dateTime, 0, 8);

        // 1. CanonicalRequest
        $canonicalRequest = $this->buildCanonicalRequest($method, $path, $queryParams, $headers, $payloadHash);

        // 2. StringToSign
        $scope = $dateShort . '/' . $this->region . '/oss/aliyun_v4_request';
        $stringToSign = "OSS4-HMAC-SHA256\n" . $dateTime . "\n" . $scope . "\n" . hash('sha256', $canonicalRequest);

        // 3. SigningKey
        $signingKey = $this->deriveSigningKey($dateShort);

        // 4. Signature
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        // 5. AdditionalHeaders（参与签名的 header 名列表，排除 host 和标准 OSS header）
        $additionalHeaders = [];
        foreach (array_keys($headers) as $h) {
            $lh = strtolower($h);
            if ($lh !== 'host' && $lh !== 'content-type' && strncmp($lh, 'x-oss-', 6) !== 0) {
                $additionalHeaders[] = $lh;
            }
        }
        sort($additionalHeaders);

        $credential = $this->accessKeyId . '/' . $scope;
        $authHeader = 'OSS4-HMAC-SHA256 Credential=' . $credential;
        if (!empty($additionalHeaders)) {
            $authHeader .= ', AdditionalHeaders=' . implode(';', $additionalHeaders);
        }
        $authHeader .= ', Signature=' . $signature;

        return $authHeader;
    }

    /**
     * 构造规范化请求字符串
     */
    private function buildCanonicalRequest(string $method, string $path, array $queryParams, array $headers, string $payloadHash): string {
        // CanonicalURI（URL 编码路径中的每一段，保留原始尾部斜杠）
        $canonicalUri = '/';
        if ($path !== '/' && $path !== '') {
            $trimmed  = trim($path, '/');
            $segments = explode('/', $trimmed);
            $encoded  = array_map(function ($s) {
                return rawurlencode($s);
            }, $segments);
            $canonicalUri = '/' . implode('/', $encoded);
            // 保留路径末尾的斜杠（OSS 签名对此敏感：/bucket/ ≠ /bucket）
            if (substr($path, -1) === '/') {
                $canonicalUri .= '/';
            }
        }

        // CanonicalQueryString（按 key 排序，URL 编码）
        $canonicalQuery = '';
        if (!empty($queryParams)) {
            ksort($queryParams);
            $parts = [];
            foreach ($queryParams as $k => $v) {
                $parts[] = rawurlencode($k) . '=' . rawurlencode($v);
            }
            $canonicalQuery = implode('&', $parts);
        }

        // CanonicalHeaders —— OSS V4 虚拟主机风格：content-type（如有）+ x-oss-* 头
        // host 不参与签名（GET 无 content-type 时仅含 x-oss-*；PUT 含 content-type）
        $signedOnly = [];
        foreach ($headers as $k => $v) {
            $lk = strtolower($k);
            if ($lk === 'content-type' || strncmp($lk, 'x-oss-', 6) === 0) {
                $signedOnly[$lk] = trim($v);
            }
        }
        ksort($signedOnly);
        $canonicalHeaders = '';
        foreach ($signedOnly as $k => $v) {
            $canonicalHeaders .= $k . ':' . $v . "\n";
        }

        // AdditionalHeaders（排除 host、content-type、x-oss-* 的自定义头）
        $additionalHeaders = [];
        foreach (array_keys($headers) as $h) {
            $lh = strtolower($h);
            if ($lh !== 'host' && $lh !== 'content-type' && strncmp($lh, 'x-oss-', 6) !== 0) {
                $additionalHeaders[] = $lh;
            }
        }
        sort($additionalHeaders);
        $additionalHeadersStr = implode(';', $additionalHeaders);

        return implode("\n", [
            $method,
            $canonicalUri,
            $canonicalQuery,
            $canonicalHeaders,
            $additionalHeadersStr,
            $payloadHash,
        ]);
    }

    /**
     * 派生签名密钥
     * 链路：aliyun_v4+SK -> date -> region -> oss -> aliyun_v4_request
     */
    private function deriveSigningKey(string $dateShort): string {
        $dateKey             = hash_hmac('sha256', $dateShort,        'aliyun_v4' . $this->accessKeySecret, true);
        $dateRegionKey       = hash_hmac('sha256', $this->region,     $dateKey, true);
        $dateRegionServiceKey= hash_hmac('sha256', 'oss',             $dateRegionKey, true);
        $signingKey          = hash_hmac('sha256', 'aliyun_v4_request', $dateRegionServiceKey, true);
        return $signingKey;
    }

    // =====================================================
    // cURL 辅助
    // =====================================================

    /**
     * 发送签名后的 HTTP 请求
     *
     * @param string $method  HTTP 方法
     * @param string $url     完整请求 URL
     * @param array  $headers 请求头数组（已格式化为 "Key: Value"）
     * @param string $body    请求体
     * @return array ['http_code' => int, 'body' => string, 'headers' => array]
     */
    private function sendRequest(string $method, string $url, array $headers, string $body = ''): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        switch ($method) {
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($ch, CURLOPT_NOBODY, true);
                break;
        }

        $responseBody = curl_exec($ch);
        $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            return ['http_code' => 0, 'body' => '', 'headers' => [], 'curl_error' => $curlError];
        }

        return ['http_code' => $httpCode, 'body' => (string) $responseBody, 'headers' => []];
    }

    /**
     * 解析 OSS 错误响应
     */
    private function parseOssError(string $body, int $httpCode): string {
        if ($body === '') {
            $codeMsg = [
                400 => '请求参数错误',
                403 => '权限拒绝',
                404 => '资源不存在',
                409 => '资源冲突',
                413 => '文件过大',
                500 => 'OSS 服务端错误',
                503 => 'OSS 服务暂不可用',
            ];
            return $codeMsg[$httpCode] ?? 'HTTP ' . $httpCode;
        }
        // 尝试解析 XML 错误响应
        $xml = @simplexml_load_string($body);
        if ($xml && isset($xml->Message)) {
            return (string) $xml->Code . ': ' . (string) $xml->Message;
        }
        return 'HTTP ' . $httpCode;
    }
}
