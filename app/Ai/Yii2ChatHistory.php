<?php

namespace common\ai;

use common\models\ChatSession;
use Exception;
use NeuronAI\Exceptions\ChatHistoryException;
use yii\db\Exception as DbException;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\ChatHistoryInterface;

class Yii2ChatHistory extends AbstractChatHistory
{
    protected string $sessionId;
    protected string $userId;

    /**
     * @throws ChatHistoryException
     */
    public function __construct(
        string $sessionId,
        string $userId,
        int $contextWindow = 50000
    ) {
        parent::__construct($contextWindow);

        $this->sessionId = $sessionId;
        $this->userId = $userId;

        $this->load();
    }

    /**
     * @throws ChatHistoryException
     * @throws DbException
     */
    protected function ensureChatSession(): ChatSession {
        $chatSession = ChatSession::findOne($this->sessionId);

        if ($chatSession === null) {
            $chatSession = new ChatSession();
            $chatSession->session_id = $this->sessionId;
            $chatSession->user_id = $this->userId;

            if (!$chatSession->save()) {
                throw new ChatHistoryException(
                    "Unable to create chat session: " . implode(', ', $chatSession->getFirstErrors())
                );
            }
        }

        return $chatSession;
    }

    /**
     *
     * @throws ChatHistoryException
     */
    protected function load(): void
    {
        try {
            $chatSession = $this->ensureChatSession();

            if (isset($chatSession->data['history']) && is_array($chatSession->data['history'])) {
                $this->history = $this->deserializeMessages($chatSession->data['history']);
            }

        } catch (DbException $e) {
            throw new ChatHistoryException("Database error while loading chat history: " . $e->getMessage());
        } catch (Exception $e) {
            throw new ChatHistoryException("Error loading chat history: " . $e->getMessage());
        }
    }

    /**
     * @throws ChatHistoryException
     */
    public function setMessages(array $messages): ChatHistoryInterface
    {
        $this->history = $messages;
        $this->updateDatabase();
        return $this;
    }

    /**
     * @throws ChatHistoryException
     */
    protected function clear(): ChatHistoryInterface
    {
        try {
            $chatSession = ChatSession::findOne($this->sessionId);

            if ($chatSession !== null) {
                $data = $chatSession->data;
                $data['history'] = [];
                $chatSession->data = $data;

                if (!$chatSession->save()) {
                    throw new ChatHistoryException(
                        "Unable to clear chat history: " . implode(', ', $chatSession->getFirstErrors())
                    );
                }
            }

            $this->history = [];
            return $this;

        } catch (DbException $e) {
            throw new ChatHistoryException("Database error while clearing chat history: " . $e->getMessage());
        } catch (Exception $e) {
            throw new ChatHistoryException("Error clearing chat history: " . $e->getMessage());
        }
    }

    /**
     * @throws ChatHistoryException
     */
    protected function updateDatabase(): void
    {
        try {
            $chatSession = $this->ensureChatSession();

            $data = $chatSession->data;
            $data['history'] = $this->jsonSerialize();
            $chatSession->data = $data;

            if (!$chatSession->save()) {
                throw new ChatHistoryException(
                    "Unable to update chat history: " . implode(', ', $chatSession->getFirstErrors())
                );
            }

        } catch (DbException $e) {
            throw new ChatHistoryException("Database error while updating chat history: " . $e->getMessage());
        } catch (Exception $e) {
            throw new ChatHistoryException("Error updating chat history: " . $e->getMessage());
        }
    }

    /**
     * Getter per session_id
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Getter per user_id
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    public function updateUsage(int $inputTokens, int $outputTokens): void
    {
        try {
            $chatSession = ChatSession::findOne($this->sessionId);

            if ($chatSession !== null) {
                $data = $chatSession->data;

                if (!isset($data['usage'])) {
                    $data['usage'] = ['inputTokens' => 0, 'outputTokens' => 0];
                }

                $data['usage']['inputTokens'] += $inputTokens;
                $data['usage']['outputTokens'] += $outputTokens;

                $chatSession->data = $data;
                $chatSession->save();
            }

        } catch (Exception $e) {
            error_log("Error updating usage data: " . $e->getMessage());
        }
    }

    public function getUsage(): array
    {
        try {
            $chatSession = ChatSession::findOne($this->sessionId);
            if ($chatSession !== null && isset($chatSession->data['usage'])) {
                return $chatSession->data['usage'];
            }

        } catch (Exception $e) {
            error_log("Error getting usage data: " . $e->getMessage());
        }

        return ['inputTokens' => 0, 'outputTokens' => 0];
    }
}
