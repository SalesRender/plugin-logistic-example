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
use Leadvertex\Plugin\Components\Logistic\LogisticStatus;
use Leadvertex\Plugin\Components\Logistic\Waybill\DeliveryType;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Logistic\Components\OrderFetcherIterator;
use RuntimeException;

class BatchShippingHandler extends \Leadvertex\Plugin\Core\Logistic\Components\BatchShippingHandler
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
                    'logistic' => [
                        'plugin' => [
                            'id',
                        ],
                    ],
                ],
            ],
            $batch->getApiClient(),
            $batch->getFsp(),
            true,
            $batch->getArguments()['limit'] ?? null
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
                $data[$order['id']] = [
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

            try {
                $response = $this->addOrders($batch, $shippingId, $data);
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
            $isSuccessLocked = $this->lockOrder(60 * 60, $id, $batch);

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

        try {
            $response = $this->markAsExported($batch, $shippingId, $process->getHandledCount());
            if ($response->getStatusCode() !== 202) {
                throw new RuntimeException('Invalid shipping complete code', 9202);
            }

            $process->finish(true);
        } catch (Exception $exception) {
            $process->finish(false);
            try {
                $this->markAsFailed($batch, $shippingId);
            } catch (Exception $exception) {}
        } finally {
            $process->save();
        }
    }

}