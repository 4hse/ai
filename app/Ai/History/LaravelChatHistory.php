<?php

namespace App\Ai\History;

use App\Models\ChatHistory;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\ChatHistoryInterface;

/**
 * Laravel Eloquent adapter for chat history.
 *
 * Uses Eloquent ORM to store chat history with user_id and thread_id.
 */
class LaravelChatHistory extends AbstractChatHistory
{
    protected ChatHistory $model;

    public function __construct(
        protected string $thread_id,
        protected string $user_id,
        int $contextWindow = 50000
    ) {
        parent::__construct($contextWindow);
        $this->load();
    }

    protected function load(): void
    {
        $this->model = ChatHistory::firstOrCreate(
            [
                'thread_id' => $this->thread_id,
                'user_id' => $this->user_id,
            ],
            [
                'messages' => [],
            ]
        );

        if ($this->model->messages && is_array($this->model->messages)) {
            $this->history = $this->deserializeMessages($this->model->messages);
        }
    }

    public function setMessages(array $messages): ChatHistoryInterface
    {
        $this->model->messages = $this->jsonSerialize();
        $this->model->save();

        return $this;
    }

    protected function clear(): ChatHistoryInterface
    {
        $this->model->delete();

        return $this;
    }
}
