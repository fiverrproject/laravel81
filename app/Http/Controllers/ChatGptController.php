<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class ChatGptController extends Controller
{
    public function getGptResult(Request $request)
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return response()->json([
                'status' => false,
                'data' => 'OPENAI_API_KEY is not configured.',
            ], 500);
        }

        $client = OpenAI::client($apiKey);

        // text-davinci-003 is deprecated; use chat completions with compatible settings
        $model = config('services.openai.model', 'gpt-3.5-turbo');

        if (str_contains($model, 'davinci') || str_starts_with($model, 'text-')) {
            $completion = $client->completions()->create([
                'model' => $model,
                'prompt' => (string) $request->input('text', ''),
                'temperature' => 0,
                'max_tokens' => 500,
                'top_p' => 1,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0,
            ]);

            $text = $completion->choices[0]->text ?? '';
        } else {
            $completion = $client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => (string) $request->input('text', '')],
                ],
                'temperature' => 0,
                'max_tokens' => 500,
            ]);

            $text = $completion->choices[0]->message->content ?? '';
        }

        return response()->json([
            'status' => true,
            'data' => $text,
        ]);
    }
}
