<?php

namespace App\Ai\workflows;

use App\Ai\nodes\RouterNode;
use App\Ai\nodes\CallNode;
use App\Ai\History\LaravelChatHistory;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Chat\History\ChatHistoryInterface;

class RouterWorkflow extends Workflow
{

    protected ChatHistoryInterface $history;

    /**
     * @throws WorkflowException
     */
    public function __construct(string $query, string $thread_id, string $user_id, string $bearer = 'fake')
    {
        parent::__construct(new WorkflowState([
            'query' => $query,
            'userId' => $user_id,
            'bearer' => $bearer
        ]));

        $this->history = new LaravelChatHistory(
            thread_id: $thread_id,
            user_id: (int) $user_id,
            contextWindow: 50000
        );
    }

    protected function nodes(): array
    {
        return [
            new RouterNode($this->history),
            new CallNode($this->history),
        ];
    }
}
