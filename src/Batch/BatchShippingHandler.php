<?php
/**
 * Created for plugin-logistic-example
 * Date: 08.02.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Batch;


use Adbar\Dot;
use Exception;
use Leadvertex\Plugin\Components\Access\Registration\Registration;
use Leadvertex\Plugin\Components\Batch\Batch;
use Leadvertex\Plugin\Components\Batch\BatchHandlerInterface;
use Leadvertex\Plugin\Components\Guzzle\Guzzle;
use Leadvertex\Plugin\Components\Logistic\LogisticStatus;
use Leadvertex\Plugin\Components\Logistic\Waybill\DeliveryType;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Logistic\Components\OrderFetcherIterator;
use RuntimeException;
use XAKEPEHOK\Path\Path;

class BatchShippingHandler implements BatchHandlerInterface
{
    private array $handled;

    public function __invoke(Process $process, Batch $batch)
    {
        $orderIterator = new OrderFetcherIterator(
            [
                'orders' => [
                    'id',
                    'shipping' => [
                        'id',
                    ],
                    'plugin' => [
                        'id',
                    ],
                ],
            ],
            $batch->getApiClient(),
            $batch->getFsp()
        );

        $process->initialize(count($orderIterator));

        try {
            $shippingId = $this->createShipping($batch);
        } catch (Exception $exception) {
            $process->terminate(new Error(Translator::get('batch', 'Не удалось создать накладную')));
            $process->save();
            throw $exception;
        }

        $orderIterator->setOnBeforeBatch(function () {
            $this->handled = [];
        });

        $orderIterator->setOnAfterBatch(function (array $orders) use ($batch, $shippingId, $process) {
            $data = [];
            foreach ($orders as $id => $order) {
                $data[$id] = [
                    'waybill' => [
                        'price' => rand(100, 350) * 100,
                        'track' => "TN" . rand(10000000, 99999999) . substr(strtoupper(md5(random_bytes(16))), 0, 2),
                        'deliveryTerms' => [
                            'minHours' => rand(1, 6),
                            'maxHours' => rand(6, 72),
                        ],
                        'deliveryType' => DeliveryType::values()[rand(0,count(DeliveryType::values()) - 1)],
                        'cod' => (bool) rand(0, 1)
                    ],
                    'status' => (function () {
                        $code = LogisticStatus::values()[rand(0,count(LogisticStatus::values()) - 1)];
                        return [
                            'code' => $code,
                            'text' => ucfirst(strtolower(LogisticStatus::code2strings()[$code])) . ' status',
                            'timestamp' => time() - rand(100, 60 * 60 * 24 * 7)
                        ];
                    })(),
                ];
            }

            $signed = Registration::find()->getOutputToken([
                'shippingId' => $shippingId,
                'orders' => $data
            ]);

            $inputToken = $batch->getToken()->getInputToken();
            $uri = (new Path($inputToken->getClaim('iss')))
                ->down('companies')
                ->down($inputToken->getClaim('cid'))
                ->down('CRM/plugin/logistic/shipping')
                ->down($shippingId)
                ->down('orders');

            try {
                $response = Guzzle::getInstance()->post(
                    (string) $uri,
                    ['json' => ['request' => (string) $signed]],
                );

                if ($response->getStatusCode() !== 200) {
                    throw new RuntimeException('Invalid response code', 9200);
                }
            } catch (Exception $exception) {
                foreach ($orders as $id => $order) {
                    $process->addError(new Error(
                        Translator::get('batch', 'Не удалось обработать заказ'),
                        $id
                    ));
                }
                $process->save();
                return;
            }

            $exported = json_decode($response->getBody()->getContents(), true);
            foreach ($exported as $id) {
                $process->handle();
            }

            $process->save();
        });

        foreach ($orderIterator as $id => $orderData) {
            $order = new Dot($orderData);
            $isSuccessLocked = $this->lockOrder(60 * 60, $order, $batch);

            if (!$isSuccessLocked) {
                $process->skip();
                $process->save();
                continue;
            }

            if ($order->get('shipping.id')) {
                $process->addError(new Error(
                    Translator::get(
                        'batch',
                        'Заказ уже присвоен к выгрузке {shipping}',
                        ['shipping' => $order->get('shipping.id')]
                    ),
                    $id
                ));
                $process->save();
                continue;
            }

            $pluginId = $order->get('plugin.id');
            if (!is_null($pluginId) && $pluginId !== Registration::find()->getId()) {
                $process->addError(new Error(
                    Translator::get(
                        'batch',
                        'Заказ привязан к другому плагину логистики с id {pluginId}',
                        ['pluginId' => $order->get('plugin.id')]
                    ),
                    $id
                ));
                $process->save();
                continue;
            }

            $this->handled[$id] = $order;
        }

        $inputToken = $batch->getToken()->getInputToken();
        $uri = (new Path($inputToken->getClaim('iss')))
            ->down('companies')
            ->down($inputToken->getClaim('cid'))
            ->down('CRM/plugin/logistic/shipping')
            ->down($shippingId);

        $signed = Registration::find()->getOutputToken([
            'shippingId' => $shippingId,
            'status' => "completed",
            'orders' => $process->getHandledCount()
        ]);

        try {
            $response = Guzzle::getInstance()->post(
                (string) $uri,
                ['json' => ['request' => (string) $signed]],
            );

            if ($response->getStatusCode() !== 202) {
                throw new RuntimeException('Invalid shipping complete code', 9202);
            }

            $process->finish(true);
        } catch (Exception $exception) {
            $process->finish(false);
        } finally {
            $process->save();
        }
    }


    private function createShipping(Batch $batch): int
    {
        $token = $batch->getToken()->getInputToken();

        $uri = (new Path($token->getClaim('iss')))
            ->down('companies')
            ->down($token->getClaim('cid'))
            ->down('CRM/plugin/logistic/shipping');

        $response = Guzzle::getInstance()->post(
            (string) $uri,
            [
                'headers' => [
                    'X-PLUGIN-TOKEN' => (string) $token,
                ],
                'json' => [],
            ],
        );

        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException('Invalid response code', 100);
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['shippingId'])) {
            throw new RuntimeException('Invalid response', 200);
        }

        return $data['shippingId'];
    }


    private function lockOrder(int $timeout, Dot $order, Batch $batch): bool
    {
        $client = $batch->getApiClient();

        $query = '
            mutation($id: ID!, $timeout: Int!) {
              orderMutation {
                lockOrder(input: {id: $id, timeout: $timeout})
              }
            }
        ';

        $response = new Dot($client->query($query, [
            'id' => $order['id'],
            'timeout' => $timeout,
        ])->getData());

        return $response->get('orderMutation.lockOrder', false);
    }
}