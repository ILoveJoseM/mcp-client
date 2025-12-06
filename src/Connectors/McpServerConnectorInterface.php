<?php

namespace JoseChan\McpClient\Connectors;

interface McpServerConnectorInterface
{
    public function request($data, $options = []);
}
