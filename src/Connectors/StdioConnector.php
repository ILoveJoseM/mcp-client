<?php


namespace JoseChan\McpClient\Connectors;


use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class StdioConnector implements McpServerConnectorInterface
{
    public function request($data, $options = [])
    {
        $jsonParams = json_decode($data);
        $stdioCommand = config('mcp-client.stdio_command');
        $process = new Process($stdioCommand);
        $process->setTimeout($options['timeout'] ?? config('mcp-client.timeout'));
        // 通过stdin传递JSON参数
        $process->setInput($jsonParams);

        try {
            $process->mustRun();

            $output = $process->getOutput();

            // 解析JSON输出
            $result = json_decode($output, true);

            if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    "Failed to decode php script output as JSON: " . json_last_error_msg() . "\nOutput: " . $output
                );
            }

            return $result ?? [];

        } catch (ProcessFailedException $e) {
            Log::error("php script execution failed", [
                'error' => $e->getMessage(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput()
            ]);

            throw new \RuntimeException(
                "script execution failed" .
                "Error: " . $process->getErrorOutput(),
                0,
                $e
            );
        }
    }

}
