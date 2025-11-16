<?php

use App\Mcp\Servers\MercuryAppServer;
use Laravel\Mcp\Facades\Mcp;

// Register MercuryApp MCP Server as a web server
Mcp::web('/mcp/mercuryapp', MercuryAppServer::class);
