<?php


namespace JoseChan\McpClient\Connectors;


use GuzzleHttp\Client;

class McpClientConnector implements McpServerConnectorInterface
{
    public function request($data, $options = [])
    {
        $serverUrl = $options['server_url'] ?? config('mcp-client.server_url');
        $timeout = $options['timeout'] ?? config('mcp-client.timeout');
        $client = new Client(['timeout' => $timeout]);
        return $client->post($serverUrl, [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
                'params' => [],
            ],
        ])->getBody();
    }


}
