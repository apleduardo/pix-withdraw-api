<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Di\Annotation\Inject;

class AuthTokenMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected HttpResponse $response;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $expected = 'Bearer ' . getenv('API_AUTH_TOKEN') ?: 'changeme';
        if ($authHeader !== $expected) {
            return $this->response->json(['error' => 'Unauthorized'])->withStatus(401);
        }
        return $handler->handle($request);
    }
}
