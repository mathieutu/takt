<?php

namespace App\Ssr;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Inertia\Ssr\HttpGateway;
use Inertia\Ssr\Response;

class SSGGateway extends HttpGateway
{
    public function dispatch(array $page, ?Request $request = null): ?Response
    {
        if (! $this->ssrIsEnabled($request ?? request())) {
            return null;
        }

        $isHot = Vite::isRunningHot();

        if ($isHot) {
            return parent::dispatch($page, $request);
        }

        $path = public_path("build/ssg/{$page['component']}.json");

        if (! file_exists($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);

        return new Response(
            implode("\n", $data['head'] ?? []),
            $data['body'] ?? '',
        );
    }
}
