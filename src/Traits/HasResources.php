<?php

namespace JoseChan\McpClient\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HasResources Trait
 *
 * 为 Connector 添加 MCP Resources 支持
 */
trait HasResources
{
    /**
     * 已注册的资源列表
     *
     * @var array
     */
    protected $resources = [];

    /**
     * 是否启用资源
     *
     * @var bool
     */
    protected $resourcesEnabled = false;

    /**
     * 从 MCP Server 获取所有可用资源
     *
     * @param bool $useCache 是否使用缓存
     * @return $this
     */
    public function fetchResources(bool $useCache = true)
    {
        $cacheKey = config('mcp-client.cache.prefix') . ':resources';

        // 尝试从缓存获取
        if ($useCache && config('mcp-client.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->resources = $cached;
                $this->resourcesEnabled = true;
                Log::debug('Loaded resources from cache', ['count' => count($this->resources)]);
                return $this;
            }
        }

        try {
            $serverUrl = config('mcp-client.server_url');
            $timeout = config('mcp-client.timeout.list', 10);

            $client = new Client(['timeout' => $timeout]);

            $response = $client->post($serverUrl, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'resources/list',
                    'params' => [],
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['result']['resources'])) {
                $this->resources = $result['result']['resources'];
                $this->resourcesEnabled = true;

                // 缓存资源列表
                if ($useCache && config('mcp-client.cache.enabled')) {
                    $ttl = config('mcp-client.cache.ttl.resources', 30);
                    Cache::put($cacheKey, $this->resources, now()->addMinutes($ttl));
                }

                if (config('mcp-client.logging.log_resource_access')) {
                    Log::info('Fetched resources from MCP Server', [
                        'count' => count($this->resources),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch resources from MCP Server', [
                'error' => $e->getMessage(),
                'server_url' => config('mcp-client.server_url'),
            ]);
        }

        return $this;
    }

    /**
     * 读取特定资源的内容
     *
     * @param string $uri 资源 URI (如 file:///path/to/file)
     * @param bool $useCache
     * @return array|null
     */
    public function readResource(string $uri, bool $useCache = true): ?array
    {
        $cacheKey = config('mcp-client.cache.prefix') . ':resource:' . md5($uri);

        // 尝试从缓存获取
        if ($useCache && config('mcp-client.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::debug('Loaded resource from cache', ['uri' => $uri]);
                return $cached;
            }
        }

        try {
            $serverUrl = config('mcp-client.server_url');
            $timeout = config('mcp-client.timeout.resources', 20);

            $client = new Client(['timeout' => $timeout]);

            $response = $client->post($serverUrl, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'resources/read',
                    'params' => [
                        'uri' => $uri,
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['result']['contents'])) {
                $contents = $result['result']['contents'];

                // 缓存资源内容
                if ($useCache && config('mcp-client.cache.enabled')) {
                    $ttl = config('mcp-client.cache.ttl.resources', 30);
                    Cache::put($cacheKey, $contents, now()->addMinutes($ttl));
                }

                if (config('mcp-client.logging.log_resource_access')) {
                    Log::info('Read resource from MCP Server', [
                        'uri' => $uri,
                    ]);
                }

                return $contents;
            }
        } catch (\Exception $e) {
            Log::error('Failed to read resource from MCP Server', [
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * 获取资源的文本内容
     *
     * @param string $uri
     * @param bool $useCache
     * @return string|null
     */
    public function getResourceText(string $uri, bool $useCache = true): ?string
    {
        $contents = $this->readResource($uri, $useCache);

        if ($contents && isset($contents[0]['text'])) {
            return $contents[0]['text'];
        }

        return null;
    }

    /**
     * 获取资源的二进制内容（base64 解码）
     *
     * @param string $uri
     * @param bool $useCache
     * @return string|null
     */
    public function getResourceBlob(string $uri, bool $useCache = true): ?string
    {
        $contents = $this->readResource($uri, $useCache);

        if ($contents && isset($contents[0]['blob'])) {
            return base64_decode($contents[0]['blob']);
        }

        return null;
    }

    /**
     * 批量读取资源
     *
     * @param array $uris
     * @param bool $useCache
     * @return array
     */
    public function readMultipleResources(array $uris, bool $useCache = true): array
    {
        $results = [];

        foreach ($uris as $uri) {
            $results[$uri] = $this->readResource($uri, $useCache);
        }

        return $results;
    }

    /**
     * 手动注册资源
     *
     * @param array $resources
     * @return $this
     */
    public function registerResources(array $resources)
    {
        $this->resources = array_merge($this->resources, $resources);
        $this->resourcesEnabled = true;
        return $this;
    }

    /**
     * 启用资源
     *
     * @return $this
     */
    public function enableResources()
    {
        if (empty($this->resources) && config('mcp-client.features.auto_fetch_resources')) {
            $this->fetchResources();
        }
        $this->resourcesEnabled = true;
        return $this;
    }

    /**
     * 禁用资源
     *
     * @return $this
     */
    public function disableResources()
    {
        $this->resourcesEnabled = false;
        return $this;
    }

    /**
     * 获取所有资源
     *
     * @return array
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * 检查是否启用资源
     *
     * @return bool
     */
    public function isResourcesEnabled(): bool
    {
        return $this->resourcesEnabled && !empty($this->resources);
    }

    /**
     * 查找资源
     *
     * @param string $name 资源名称（模糊匹配）
     * @return array
     */
    public function findResources(string $name): array
    {
        return array_filter($this->resources, function ($resource) use ($name) {
            return stripos($resource['name'] ?? '', $name) !== false ||
                   stripos($resource['uri'] ?? '', $name) !== false;
        });
    }

    /**
     * 清除资源缓存
     *
     * @param string|null $uri 指定清除某个资源，null 清除全部
     * @return void
     */
    public function clearResourcesCache(?string $uri = null): void
    {
        if ($uri) {
            $cacheKey = config('mcp-client.cache.prefix') . ':resource:' . md5($uri);
            Cache::forget($cacheKey);
        } else {
            $cacheKey = config('mcp-client.cache.prefix') . ':resources';
            Cache::forget($cacheKey);
        }
    }
}
