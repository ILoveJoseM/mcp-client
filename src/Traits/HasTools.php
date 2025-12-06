<?php

namespace JoseChan\McpClient\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JoseChan\McpClient\Connectors\Mcp;

/**
 * HasTools Trait
 *
 * 为 Connector 添加 MCP Tools 支持
 */
trait HasTools
{
    /**
     * 已注册的工具列表
     *
     * @var array
     */
    protected $tools = [];

    /**
     * 是否启用工具
     *
     * @var bool
     */
    protected $toolsEnabled = false;

    /**
     * 从 MCP Server 获取所有可用工具
     *
     * @param bool $useCache 是否使用缓存
     * @return $this
     */
    public function fetchTools(bool $useCache = true)
    {
        $cacheKey = config('mcp-client.cache.prefix') . ':tools';

        // 尝试从缓存获取
        if ($useCache && config('mcp-client.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->tools = $cached;
                $this->toolsEnabled = true;
                Log::debug('Loaded tools from cache', ['count' => count($this->tools)]);
                return $this;
            }
        }

        try {
            $timeout = config('mcp-client.timeout.list', 10);

            $response = Mcp::request([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
                'params' => [],
            ], ['timeout' => $timeout]);


            $result = json_decode($response, true);

            if (isset($result['result']['tools'])) {
                $this->tools = $result['result']['tools'];
                $this->toolsEnabled = true;

                // 缓存工具列表
                if ($useCache && config('mcp-client.cache.enabled')) {
                    $ttl = config('mcp-client.cache.ttl.tools', 60);
                    Cache::put($cacheKey, $this->tools, now()->addMinutes($ttl));
                }

                if (config('mcp-client.logging.log_tool_calls')) {
                    Log::info('Fetched tools from MCP Server', [
                        'count' => count($this->tools),
                        'tools' => array_column($this->tools, 'name'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch tools from MCP Server', [
                'error' => $e->getMessage(),
                'server_url' => config('mcp-client.server_url'),
            ]);
        }

        return $this;
    }

    /**
     * 手动注册工具
     *
     * @param array $tools
     * @return $this
     */
    public function registerTools(array $tools)
    {
        $this->tools = array_merge($this->tools, $tools);
        $this->toolsEnabled = true;
        return $this;
    }

    /**
     * 启用工具
     *
     * @return $this
     */
    public function enableTools()
    {
        if (empty($this->tools) && config('mcp-client.features.auto_fetch_tools')) {
            $this->fetchTools();
        }
        $this->toolsEnabled = true;
        return $this;
    }

    /**
     * 禁用工具
     *
     * @return $this
     */
    public function disableTools()
    {
        $this->toolsEnabled = false;
        return $this;
    }

    /**
     * 获取所有工具
     *
     * @return array
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * 检查是否启用工具
     *
     * @return bool
     */
    public function isToolsEnabled(): bool
    {
        return $this->toolsEnabled && !empty($this->tools);
    }

    /**
     * 调用 MCP Tool
     *
     * @param string $toolName
     * @param array $arguments
     * @return mixed
     */
    public function callTool(string $toolName, array $arguments = [])
    {
        try {
            $serverUrl = config('mcp-client.server_url');
            $timeout = config('mcp-client.timeout.tools', 30);

            $response = Mcp::request([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => $arguments,
                ],
            ], ['timeout' => $timeout]);
//            $client = new Client(['timeout' => $timeout]);
//
//            $response = $client->post($serverUrl, [
//                'json' => [
//                    'jsonrpc' => '2.0',
//                    'id' => 1,
//                    'method' => 'tools/call',
//                    'params' => [
//                        'name' => $toolName,
//                        'arguments' => $arguments,
//                    ],
//                ],
//            ]);

            $result = json_decode($response->getBody(), true);

            if (config('mcp-client.logging.log_tool_calls')) {
                Log::info('Called MCP tool', [
                    'tool' => $toolName,
                    'arguments' => $arguments,
                ]);
            }

            if (isset($result['result']['content'])) {
                return $result['result']['content'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to call MCP tool', [
                'tool' => $toolName,
                'arguments' => $arguments,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 转换工具为 OpenAI 函数格式
     *
     * @return array
     */
    protected function convertToolsToOpenAIFormat(): array
    {
        return array_map(function ($tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'] ?? '',
                    'parameters' => $tool['inputSchema'] ?? [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ];
        }, $this->tools);
    }

    /**
     * 清除工具缓存
     *
     * @return void
     */
    public function clearToolsCache(): void
    {
        $cacheKey = config('mcp-client.cache.prefix') . ':tools';
        Cache::forget($cacheKey);
    }
}
