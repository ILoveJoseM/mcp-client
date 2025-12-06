<?php

namespace JoseChan\McpClient\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HasPrompts Trait
 *
 * 为 Connector 添加 MCP Prompts 支持
 */
trait HasPrompts
{
    /**
     * 已注册的提示词列表
     *
     * @var array
     */
    protected $prompts = [];

    /**
     * 是否启用提示词
     *
     * @var bool
     */
    protected $promptsEnabled = false;

    /**
     * 从 MCP Server 获取所有可用提示词
     *
     * @param bool $useCache 是否使用缓存
     * @return $this
     */
    public function fetchPrompts(bool $useCache = true)
    {
        $cacheKey = config('mcp-client.cache.prefix') . ':prompts';

        // 尝试从缓存获取
        if ($useCache && config('mcp-client.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->prompts = $cached;
                $this->promptsEnabled = true;
                Log::debug('Loaded prompts from cache', ['count' => count($this->prompts)]);
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
                    'method' => 'prompts/list',
                    'params' => [],
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['result']['prompts'])) {
                $this->prompts = $result['result']['prompts'];
                $this->promptsEnabled = true;

                // 缓存提示词列表
                if ($useCache && config('mcp-client.cache.enabled')) {
                    $ttl = config('mcp-client.cache.ttl.prompts', 60);
                    Cache::put($cacheKey, $this->prompts, now()->addMinutes($ttl));
                }

                if (config('mcp-client.logging.log_prompt_usage')) {
                    Log::info('Fetched prompts from MCP Server', [
                        'count' => count($this->prompts),
                        'prompts' => array_column($this->prompts, 'name'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch prompts from MCP Server', [
                'error' => $e->getMessage(),
                'server_url' => config('mcp-client.server_url'),
            ]);
        }

        return $this;
    }

    /**
     * 获取特定提示词的详细信息
     *
     * @param string $promptName
     * @param bool $useCache
     * @return array|null
     */
    public function getPrompt(string $promptName, bool $useCache = true): ?array
    {
        $cacheKey = config('mcp-client.cache.prefix') . ':prompt:' . $promptName;

        // 尝试从缓存获取
        if ($useCache && config('mcp-client.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::debug('Loaded prompt from cache', ['name' => $promptName]);
                return $cached;
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
                    'method' => 'prompts/get',
                    'params' => [
                        'name' => $promptName,
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['result'])) {
                $prompt = $result['result'];

                // 缓存提示词详情
                if ($useCache && config('mcp-client.cache.enabled')) {
                    $ttl = config('mcp-client.cache.ttl.prompts', 60);
                    Cache::put($cacheKey, $prompt, now()->addMinutes($ttl));
                }

                if (config('mcp-client.logging.log_prompt_usage')) {
                    Log::info('Fetched prompt detail from MCP Server', [
                        'name' => $promptName,
                    ]);
                }

                return $prompt;
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch prompt from MCP Server', [
                'name' => $promptName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * 使用提示词模板
     *
     * @param string $promptName
     * @param array $arguments 填充模板的参数
     * @return string|null
     */
    public function usePrompt(string $promptName, array $arguments = []): ?string
    {
        $prompt = $this->getPrompt($promptName);

        if (!$prompt || !isset($prompt['messages'][0]['content']['text'])) {
            return null;
        }

        $template = $prompt['messages'][0]['content']['text'];

        // 简单的模板变量替换
        foreach ($arguments as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * 手动注册提示词
     *
     * @param array $prompts
     * @return $this
     */
    public function registerPrompts(array $prompts)
    {
        $this->prompts = array_merge($this->prompts, $prompts);
        $this->promptsEnabled = true;
        return $this;
    }

    /**
     * 启用提示词
     *
     * @return $this
     */
    public function enablePrompts()
    {
        if (empty($this->prompts) && config('mcp-client.features.auto_fetch_prompts')) {
            $this->fetchPrompts();
        }
        $this->promptsEnabled = true;
        return $this;
    }

    /**
     * 禁用提示词
     *
     * @return $this
     */
    public function disablePrompts()
    {
        $this->promptsEnabled = false;
        return $this;
    }

    /**
     * 获取所有提示词
     *
     * @return array
     */
    public function getPrompts(): array
    {
        return $this->prompts;
    }

    /**
     * 检查是否启用提示词
     *
     * @return bool
     */
    public function isPromptsEnabled(): bool
    {
        return $this->promptsEnabled && !empty($this->prompts);
    }

    /**
     * 清除提示词缓存
     *
     * @param string|null $promptName 指定清除某个提示词，null 清除全部
     * @return void
     */
    public function clearPromptsCache(?string $promptName = null): void
    {
        if ($promptName) {
            $cacheKey = config('mcp-client.cache.prefix') . ':prompt:' . $promptName;
            Cache::forget($cacheKey);
        } else {
            $cacheKey = config('mcp-client.cache.prefix') . ':prompts';
            Cache::forget($cacheKey);
        }
    }
}
