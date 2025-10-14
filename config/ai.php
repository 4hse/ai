<?php

return [
    "providers" => [
        "gemini" => [
            "key" => env("GEMINI_API_KEY"),
        ],
        "bedrock" => [
            "region" => env("BEDROCK_REGION"),
            "key" => env("BEDROCK_KEY"),
            "secret" => env("BEDROCK_SECRET"),
        ],
        "claude" => [
            "key" => env("CLAUDE_API_KEY"),
        ],
    ],
];
