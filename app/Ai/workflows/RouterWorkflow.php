<?php

namespace common\ai\workflows;

use common\ai\nodes\RouterNode;
use common\ai\nodes\CallNode;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
use common\ai\Yii2ChatHistory;
use NeuronAI\Chat\History\ChatHistoryInterface;

class RouterWorkflow extends Workflow
{

    protected ChatHistoryInterface $history;

    /**
     * @throws WorkflowException
     */
    public function __construct(string $query, string $sessionId, string $userId, string $bearer)
    {
        parent::__construct(new WorkflowState([
            'query' => $query,
            'userId' => $userId,
            'bearer' => $bearer
        ]));

        $this->history = new Yii2ChatHistory(
            sessionId: $sessionId,
            userId: $userId,
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
