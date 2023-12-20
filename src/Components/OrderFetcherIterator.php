<?php
/**
 * Created for plugin-logistic-example
 * Date: 08.02.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Logistic\Components;


use SalesRender\Plugin\Components\ApiClient\ApiFetcherIterator;
use XAKEPEHOK\ArrayGraphQL\ArrayGraphQL;

class OrderFetcherIterator extends ApiFetcherIterator
{

    protected function getQuery(array $fields): string
    {
        return '
            query($pagination: Pagination!, $filters: OrderSearchFilter, $sort: OrderSort) {
                ordersFetcher(pagination: $pagination, filters: $filters, sort: $sort) ' . ArrayGraphQL::convert($fields) . '
            }
        ';
    }

    /**
     * Dot-notation string to query body
     * @return string
     */
    protected function getQueryPath(): string
    {
        return 'ordersFetcher';
    }

    protected function getIdentity(array $array): string
    {
        return $array['id'];
    }
}