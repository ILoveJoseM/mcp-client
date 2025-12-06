<?php


namespace JoseChan\McpClient\Connectors;


use Illuminate\Support\Facades\Facade;

/**
 * Mcp 客户端连接器
 * 用于连接 MCP Server
 *
 * @package JoseChan\McpClient\Connectors
 *
 * @method static array request(array $data, array $options = []) 发送请求到 MCP Server
 * @see McpServerConnectorInterface
 */
class Mcp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return McpConnectorFactory::class;
    }
}
