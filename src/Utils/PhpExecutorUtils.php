<?php

namespace JoseChan\McpClient\Utils;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PhpExecutorUtils
{
    /**
     * php可执行文件路径
     *
     * @var string|null
     */
    protected $phpPath;

    /**
     * php版本信息
     *
     * @var string|null
     */
    protected $phpVersion;

    /**
     * 默认超时时间(秒)
     *
     * @var int
     */
    protected $timeout = 60;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->detectPhp();
    }

    /**
     * 检测系统中可用的php版本
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function detectPhp(): void
    {
        // 尝试的php命令列表,优先使用php 3
        $phpCommands = ['php',];

        foreach ($phpCommands as $command) {
            try {
                $process = new Process([$command, '--version']);
                $process->run();

                if ($process->isSuccessful()) {
                    $this->phpPath = $command;
                    $this->phpVersion = trim($process->getOutput() ?: $process->getErrorOutput());

                    Log::info("php detected", [
                        'command' => $command,
                        'version' => $this->phpVersion
                    ]);

                    return;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \RuntimeException('php is not installed or not found in PATH');
    }

    /**
     * 执行php脚本
     *
     * @param string $scriptPath php脚本的绝对路径
     * @param array $params 传递给脚本的参数(将被转换为JSON)
     * @param int|null $timeout 超时时间(秒),null使用默认值
     * @return array 脚本返回的数据(JSON解码后)
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function execute(string $scriptPath, array $params = [], ?int $timeout = null): array
    {
        if (!file_exists($scriptPath)) {
            throw new \InvalidArgumentException("php script not found: {$scriptPath}");
        }

        // 将参数转换为JSON
        $jsonParams = json_encode($params);
        if ($jsonParams === false) {
            throw new \InvalidArgumentException('Failed to encode parameters to JSON: ' . json_last_error_msg());
        }

        // 创建进程
        $process = new Process([
            $this->phpPath,
            $scriptPath
        ]);

        // 通过stdin传递JSON参数
        $process->setInput($jsonParams);

        // 设置超时
        $process->setTimeout($timeout ?? $this->timeout);

        Log::info("Executing php script", [
            'script' => $scriptPath,
            'php' => $this->phpPath,
            'params' => $params,
            'timeout' => $timeout ?? $this->timeout
        ]);

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

            Log::info("php script executed successfully", [
                'script' => $scriptPath,
                'result' => $result
            ]);

            return $result ?? [];

        } catch (ProcessFailedException $e) {
            Log::error("php script execution failed", [
                'script' => $scriptPath,
                'error' => $e->getMessage(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput()
            ]);

            throw new \RuntimeException(
                "php script execution failed: {$scriptPath}\n" .
                "Error: " . $process->getErrorOutput(),
                0,
                $e
            );
        }
    }

    /**
     * 获取检测到的php路径
     *
     * @return string|null
     */
    public function getPhpPath(): ?string
    {
        return $this->phpPath;
    }

    /**
     * 获取检测到的php版本
     *
     * @return string|null
     */
    public function getPhpVersion(): ?string
    {
        return $this->phpVersion;
    }

    /**
     * 设置默认超时时间
     *
     * @param int $timeout 超时时间(秒)
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 手动设置php路径
     *
     * @param string $phpPath php可执行文件路径
     * @return self
     * @throws \InvalidArgumentException
     */
    public function setPhpPath(string $phpPath): self
    {
        $process = new Process([$phpPath, '--version']);

        try {
            $process->run();

            if ($process->isSuccessful()) {
                $this->phpPath = $phpPath;
                $this->phpVersion = trim($process->getOutput() ?: $process->getErrorOutput());
                return $this;
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid php path: {$phpPath}", 0, $e);
        }

        throw new \InvalidArgumentException("Invalid php path: {$phpPath}");
    }
}
