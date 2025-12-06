<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Client Configuration
    |--------------------------------------------------------------------------
    |
    | MCP 客户端配置 - 用于连接 MCP Server 并获取 Tools、Prompts、Resources
    |
    */
    // MCP 客户端协议（http 或 stdio）
    'protocol' => env('MCP_CLIENT_PROTOCOL', 'http'),
    // MCP Server 地址
    'server_url' => env('MCP_SERVER_URL', 'http://127.0.0.1:30001/mcp'),
    'stdio_command' => env('MCP_CLIENT_STDIO_COMMAND', ["php", "artisan", "mcp:server"]),

    // 请求超时设置
    'timeout' => [
        // 工具调用超时（秒）
        'tools' => env('MCP_CLIENT_TIMEOUT_TOOLS', 30),
        // 获取列表超时（秒）
        'list' => env('MCP_CLIENT_TIMEOUT_LIST', 10),
        // 资源读取超时（秒）
        'resources' => env('MCP_CLIENT_TIMEOUT_RESOURCES', 20),
    ],

    // 功能开关
    'features' => [
        // 是否启用工具自动获取
        'auto_fetch_tools' => env('MCP_CLIENT_AUTO_FETCH_TOOLS', true),
        // 是否启用提示词自动获取
        'auto_fetch_prompts' => env('MCP_CLIENT_AUTO_FETCH_PROMPTS', false),
    ],

    // 缓存配置
    'cache' => [
        // 是否启用缓存
        'enabled' => env('MCP_CLIENT_CACHE_ENABLED', true),
        // 缓存驱动
        'driver' => env('MCP_CLIENT_CACHE_DRIVER', 'file'),
        // 缓存过期时间（分钟）
        'ttl' => [
            'tools' => env('MCP_CLIENT_CACHE_TTL_TOOLS', 60),
        ],
        // 缓存键前缀
        'prefix' => env('MCP_CLIENT_CACHE_PREFIX', 'mcp_client'),
    ],

    // 重试配置
    'retry' => [
        // 失败重试次数
        'max_attempts' => env('MCP_CLIENT_RETRY_ATTEMPTS', 3),
        // 重试延迟（毫秒）
        'delay' => env('MCP_CLIENT_RETRY_DELAY', 1000),
    ],

    // 日志配置
    'logging' => [
        // 是否记录工具调用日志
        'log_tool_calls' => env('MCP_CLIENT_LOG_TOOL_CALLS', true),
        // 是否记录提示词使用日志
        'log_prompt_usage' => env('MCP_CLIENT_LOG_PROMPT_USAGE', true),
        // 是否记录资源访问日志
        'log_resource_access' => env('MCP_CLIENT_LOG_RESOURCE_ACCESS', true),
    ],
];
