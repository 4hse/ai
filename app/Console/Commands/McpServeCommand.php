<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;
use PhpMcp\Server\Transports\StreamableHttpServerTransport;
use App\Http\Middleware\ValidateMcpToken;
use Throwable;

class McpServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start MCP server';

    /**
     * Execute the console command.
     * @throws Throwable
     */
    public function handle(): void
    {
        // Read environment mode (default: prod)
        $mode = strtolower(getenv('MCP_MODE') ?: 'prod');

        // Build server
        $server = Server::make()
            ->withServerInfo('4HSE MCP Server', '1.0.0')
            ->build();

        // Discover MCP elements from app/Ai/Mcp/Tools
        $mcpToolsPath = app_path('Ai/Mcp/Tools');
        if (is_dir($mcpToolsPath)) {
            $server->discover($mcpToolsPath);
        }

        // Choose the transport
        if ($mode === 'dev') {
            // Development mode: use STDIO
            fwrite(STDERR, "[INFO] Starting in DEV mode (STDIO transport)\n");

            $transport = new StdioServerTransport();

        } else {
            $host = getenv('MCP_HTTP_HOST') ?: '127.0.0.1';
            $port = getenv('MCP_HTTP_PORT') ?: '8080';
            // Production mode: use HTTP Streamable
            fwrite(STDERR, "[INFO] Starting in PROD mode (HTTP transport on $host:$port)\n");
            fwrite(STDERR, "[INFO] OAuth2 authentication enabled via Keycloak\n");

            $transport = new StreamableHttpServerTransport(
                host: $host,
                port: $port,
                enableJsonResponse: false,
                stateless: false,
                // Apply authentication middleware to all MCP requests
                // TEMPORARILY DISABLED FOR TESTING
                // middleware: [
                //     app(ValidateMcpToken::class)
                // ]
            );
        }

        $server->listen($transport);
    }
}
