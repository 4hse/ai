<?php

namespace App\Http\Controllers;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\ChatHistory;
use common\ai\workflows\RouterWorkflow;
use Exception;

class ChatController extends Controller
{

    public function stream(string $thread_id, array $messages)
    {
        $message = $messages[0];
        $user_id = 'adriano.foschi@4hse.com';

        $chatHistory = ChatHistory::firstOrCreate(
            ['thread_id' => $thread_id],
            [
                'user_id' => $user_id,
                'messages' => []
            ]
        );

        // Set the appropriate headers for SSE
        $response = new StreamedResponse(function () use ($message, $thread_id, $user_id, $chatHistory) {
            try {
                if (!$chatHistory) {
                    throw new Exception("Unable to retreive/create thread");
                }

                while (true) {
                    $workflow = new RouterWorkflow($message, $thread_id, $user_id);
                    $handler = $workflow->start();

                    foreach ($handler->streamEvents() as $event) {
                        /*if ($event instanceof ProgressEvent) {
                            echo $event->message . "\n";
                        }*/
                        if ($event instanceof GenerationProgressEvent) {
                            $this->sendMessage($thread_id, $event->text);
                        }
                    }
                }
    
            }  catch (Exception $e) {
                $this->sendMessage($thread_id, "service", "error");
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    protected function sendMessage($thread_id, $text, $type = "text"): void
    {
        $data = [
            $type => $text,
            "time" => date("H:i:s"),
            "thread_id" => $thread_id,
        ];

        echo "data: " . json_encode($data) . "\n\n";

        //if (ob_get_level()) {
        ob_flush();
        //}
        flush();

        sleep(1);
    }
}
