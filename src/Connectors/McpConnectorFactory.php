<?php


namespace JoseChan\McpClient\Connectors;


class McpConnectorFactory implements McpServerConnectorInterface
{
    private $connector;
    public function __construct()
    {
        $protocol = config('mcp-client.protocol');
        if($protocol === 'stdio'){
            $this->connector = new StdioConnector();
        } else {
            $this->connector = new McpClientConnector();
        }
    }

    public function request($data, $options = [])
    {
        return $this->connector->request($data, $options);
    }


}
