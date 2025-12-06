<?php

namespace JoseChan\McpClient\Console;

use JoseChan\LlmConnector\Facade\LLM;
use JoseChan\McpClient\Traits\HasPrompts;
use JoseChan\McpClient\Traits\HasResources;
use JoseChan\McpClient\Traits\HasTools;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class McpClientCommand extends Command
{
    use HasTools;

    protected $name = "mcp:client";

    protected $description = "MCP客户端";

    protected $llmService;

    public function handle()
    {
        $this->output->writeln('MCP Client initializing LLM service...');
        $this->initLLM();
        $this->output->writeln('MCP Client Command executed successfully.');
        while (true){
            $ask = $this->ask("any question：");
            if($ask == 'exit' || $ask == 'quit'){
                $this->output->writeln('Exiting MCP Client. Goodbye!');
                break;
            }
            $this->output->writeln($ask);
            $message = $this->normalizeMessages($ask);
            $this->askLLM($message);
        }
    }

    protected function initLLM()
    {
        if(empty($this->llmService)){
            $this->llmService = LLM::application();
        }

        // 根据配置自动获取 MCP 资源
        if (config('mcp-client.features.auto_fetch_tools')) {
            $this->fetchTools();
        }

        if($this->enableTools() && !empty($this->tools)){
            $this->llmService->withTools($this->convertToolsToOpenAIFormat());
        }
    }

    protected function askLLM($messages)
    {
        do{
            $response = LLM::application()->completions($messages);
            // 处理工具调用
            $result = json_decode($response->getBody(), true);
            $reason = $result['choices'][0]['finish_reason'] ?? null;
            switch ($reason){
                case 'stop':
                    $this->output->writeln($result['choices'][0]['message']['content'] ?? '');
                    break;
                case 'tool_calls':
                    $this->output->writeln("LLM requested tool calls..." . $result['choices'][0]['message']['tool_calls'][0]['function']['name'] ?? '');
                    break;
                default:
                    $this->output->writeln("LLM finished with reason: " . $reason);
                    break;
            }
            if ($this->isToolsEnabled() && $this->hasToolCalls($result)) {
                $toolCallContext = $this->handleToolCalls($result);
                // 递归调用 completions，让 LLM 基于工具结果生成最终回复
                $messages = array_merge($messages, $toolCallContext);
            }
//            sleep(1);
        } while($result["choices"][0]['finish_reason'] === 'tool_calls');
    }

    /**
     * 检查响应是否包含工具调用
     *
     * @param array $result
     * @return bool
     */
    protected function hasToolCalls(array $result): bool
    {
        return isset($result['choices'][0]['message']['tool_calls']) &&
            !empty($result['choices'][0]['message']['tool_calls']);
    }


    /**
     * 处理工具调用
     *
     * @param array $result
     * @return array 工具调用上下文
     */
    protected function handleToolCalls(array $result)
    {
        $toolCalls = $result['choices'][0]['message']['tool_calls'];
        $assistantMessage = $result['choices'][0]['message'];
        $toolCallContext[] = $assistantMessage;

        // 执行所有工具调用
        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['function']['name'];
            $toolArgs = json_decode($toolCall['function']['arguments'], true);

            Log::info('Executing tool call', [
                'tool' => $toolName,
                'arguments' => $toolArgs,
            ]);

            // 调用 MCP Tool
            $toolResult = $this->callTool($toolName, $toolArgs);

            // 将工具执行结果添加到消息历史
            $toolCallContext[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCall['id'],
                'content' => json_encode($toolResult),
            ];
        }

        // 递归调用 completions，让 LLM 基于工具结果生成最终回复
        return $toolCallContext;
    }

    /**
     * 标准化消息格式
     *
     * @param string|array $messages
     * @return array
     */
    protected function normalizeMessages($messages): array
    {
        if (is_string($messages)) {
            return [
                [
                    'role' => 'user',
                    'content' => $messages,
                ],
            ];
        }

        // 如果是数组，检查是否已经是标准格式
        if (is_array($messages)) {
            // 如果第一个元素有 role 和 content，认为是标准格式
            if (isset($messages[0]['role']) && isset($messages[0]['content'])) {
                return $messages;
            }

            // 否则，将整个数组作为单条 user 消息
            return [
                [
                    'role' => 'user',
                    'content' => json_encode($messages),
                ],
            ];
        }

        return [];
    }
}
