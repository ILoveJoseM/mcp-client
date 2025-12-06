<?php


namespace JoseChan\McpClient;


use Illuminate\Support\ServiceProvider;
use JoseChan\McpClient\Connectors\McpConnectorFactory;
use JoseChan\McpClient\Console\McpClientCommand;

/**
 * McpClientServiceProvider
 *
 * 注册 MCP Client 相关服务
 */
class McpClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(McpConnectorFactory::class, function () {
            return new McpConnectorFactory();
        });

        $this->commands([
            McpClientCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/mcp-client.php' => config_path('mcp-client.php'),
        ], 'mcp-client');
    }
}
