<?php

namespace SalesRender\Plugin\Instance\Logistic\Components\Actions;

use Slim\Http\Response;
use Slim\Http\ServerRequest;

class RemoveOrdersAction extends \SalesRender\Plugin\Core\Logistic\Components\Actions\Shipping\RemoveOrdersAction
{

    protected function handle(array $body, ServerRequest $request, Response $response, array $args): Response
    {
        // TODO: Implement remover orders action
        return $response->withStatus(202);
    }
}