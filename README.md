# Laravel MCP Client

## 概述

Laravel MCP Client是一个用于连接和与MCP (Model Context Protocol)服务器交互的Laravel扩展包。它提供了一个简单而强大的接口，使你的Laravel应用程序能够调用MCP服务器提供的工具、资源和提示词。

MCP是一种标准协议，允许AI模型与外部工具和服务进行交互，大大扩展了AI的能力边界。

## 功能特性

- **工具调用**: 调用MCP服务器提供的各种工具
- **资源管理**: 访问和管理MCP服务器资源
- **提示词管理**: 使用MCP服务器提供的提示词模板
- **命令行交互**: 提供交互式命令行界面
- **缓存支持**: 工具列表缓存，提高性能
- **灵活配置**: 支持HTTP和STDIO两种连接方式
- **完整日志**: 详细的操作日志记录
- **错误处理**: 完善的异常处理和重试机制

## 版本要求

- PHP: 7.1.3+
- Laravel: 5.6+
- jose-chan/llm-connector: ^1.0
- ext-json: *

## 安装

1. 使用Composer安装扩展包：

```bash
composer require jose-chan/laravel-mcp-client
```

2. 发布配置文件：

```bash
php artisan vendor:publish --provider=JoseChan\\McpClient\\McpClientServiceProvider --tag=mcp-client
```

3. 在`.env`文件中添加配置：

```env
# MCP客户端协议 (http 或 stdio)
MCP_CLIENT_PROTOCOL=http

# MCP服务器地址
MCP_SERVER_URL=http://127.0.0.1:30001/mcp

# STDIO命令 (当使用stdio协议时)
MCP_CLIENT_STDIO_COMMAND=["php", "artisan", "mcp:server"]

# 超时设置
MCP_CLIENT_TIMEOUT_TOOLS=30
MCP_CLIENT_TIMEOUT_LIST=10
MCP_CLIENT_TIMEOUT_RESOURCES=20

# 功能开关
MCP_CLIENT_AUTO_FETCH_TOOLS=true
MCP_CLIENT_AUTO_FETCH_PROMPTS=false

# 缓存配置
MCP_CLIENT_CACHE_ENABLED=true
MCP_CLIENT_CACHE_DRIVER=file
MCP_CLIENT_CACHE_TTL_TOOLS=60
MCP_CLIENT_CACHE_PREFIX=mcp_client

# 重试配置
MCP_CLIENT_RETRY_ATTEMPTS=3
MCP_CLIENT_RETRY_DELAY=1000

# 日志配置
MCP_CLIENT_LOG_TOOL_CALLS=true
MCP_CLIENT_LOG_PROMPT_USAGE=true
MCP_CLIENT_LOG_RESOURCE_ACCESS=true
```

## 使用方法

### 命令行交互

使用命令行工具与MCP服务器交互：

```bash
php artisan mcp:client
```

## 配置选项

### 基础配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| protocol | 连接协议 (http/stdio) | http |
| server_url | MCP服务器地址 | http://127.0.0.1:30001/mcp |
| stdio_command | STDIO命令 | ["php", "artisan", "mcp:server"] |

### 超时配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| timeout.tools | 工具调用超时(秒) | 30 |
| timeout.list | 获取列表超时(秒) | 10 |
| timeout.resources | 资源读取超时(秒) | 20 |

### 功能开关

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| features.auto_fetch_tools | 自动获取工具 | true |
| features.auto_fetch_prompts | 自动获取提示词 | false |

### 缓存配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| cache.enabled | 是否启用缓存 | true |
| cache.driver | 缓存驱动 | file |
| cache.ttl.tools | 工具缓存时间(分钟) | 60 |
| cache.prefix | 缓存前缀 | mcp_client |

### 重试配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| retry.max_attempts | 最大重试次数 | 3 |
| retry.delay | 重试延迟(毫秒) | 1000 |

### 日志配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| logging.log_tool_calls | 记录工具调用 | true |
| logging.log_prompt_usage | 记录提示词使用 | true |
| logging.log_resource_access | 记录资源访问 | true |

## 故障排除

### 常见问题

1. **连接超时**
   - 检查MCP服务器是否运行
   - 确认服务器地址配置正确
   - 调整超时设置

2. **工具调用失败**
   - 检查工具名称和参数是否正确
   - 确认MCP服务器支持该工具
   - 查看错误日志获取详细信息

3. **缓存问题**
   - 清除缓存: `Mcp::clearToolsCache()`
   - 检查缓存配置是否正确
   - 确认缓存驱动已安装

### 调试技巧

1. 启用详细日志记录
2. 使用Laravel日志查看详细信息
3. 检查MCP服务器状态
4. 验证配置文件

## 更新日志

### v1.0.0
- 初始版本
- 基础MCP客户端功能
- 工具调用支持
- 缓存机制
- 命令行交互

## 许可证

MIT License

## 作者

JoseChan <524233828@qq.com>

## 贡献

欢迎提交Issue和Pull Request！

## 相关链接

- [MCP协议规范](https://modelcontextprotocol.io/)
- [jose-chan/llm-connector](https://github.com/jose-chan/laravel-llm-connector)

---

如有问题或建议，请联系作者或提交Issue。
