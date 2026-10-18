<?php

use App\Mcp\Servers\TaktServer;
use Laravel\Mcp\Facades\Mcp;

// Bearer-authenticated with the user's `api_token`, like routes/api.php. Swapping this middleware
// (e.g. for Passport OAuth) is all it takes to change how MCP clients authenticate.
Mcp::web('/mcp', TaktServer::class)->middleware('auth:api');
