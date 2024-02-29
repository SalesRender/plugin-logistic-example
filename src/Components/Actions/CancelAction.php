<?php

namespace SalesRender\Plugin\Instance\Logistic\Components\Actions;

use SalesRender\Plugin\Core\Logistic\Components\Actions\Shipping\ShippingCancelAction;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class CancelAction extends ShippingCancelAction
{

    protected function handle(array $body, ServerRequest $request, Response $response, array $args): Response
    {
        // TODO: implement cancel action
        return $response->withStatus(202);
    }
}