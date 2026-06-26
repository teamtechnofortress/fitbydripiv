<?php

namespace App\Http\Middleware;

use App\Services\DrNetwork\Webhooks\NetworkWebhookAdapterResolver;
use App\Services\DrNetwork\Webhooks\NetworkWebhookEndpointResolver;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class VerifyNetworkWebhook
{
    public function __construct(
        private NetworkWebhookEndpointResolver $endpointResolver,
        private NetworkWebhookAdapterResolver $adapterResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $endpointToken = (string) $request->route('endpointToken');

        try {
            $network = $this->endpointResolver->resolve($endpointToken);
            $this->adapterResolver->resolve($network)->verify($request, $network);
        } catch (RuntimeException $e) {
            abort(401, $e->getMessage());
        }

        $request->attributes->set('drNetwork', $network);

        return $next($request);
    }
}
