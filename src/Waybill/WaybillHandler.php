<?php
/**
 * Created for plugin-logistic-example
 * Date: 28.12.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Waybill;


use Leadvertex\Components\Address\Address;
use Leadvertex\Components\MoneyValue\MoneyValue;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Logistic\Exceptions\LogisticDataTooBigException;
use Leadvertex\Plugin\Components\Logistic\Exceptions\NegativePriceException;
use Leadvertex\Plugin\Components\Logistic\Logistic;
use Leadvertex\Plugin\Components\Logistic\LogisticStatus;
use Leadvertex\Plugin\Components\Logistic\Waybill\DeliveryTerms;
use Leadvertex\Plugin\Components\Logistic\Waybill\DeliveryType;
use Leadvertex\Plugin\Components\Logistic\Waybill\Waybill;
use Leadvertex\Plugin\Core\Logistic\Components\Waybill\Response\WaybillAddress;
use Leadvertex\Plugin\Core\Logistic\Components\Waybill\Response\WaybillResponse;
use Leadvertex\Plugin\Core\Logistic\Components\Waybill\WaybillHandlerInterface;
use XAKEPEHOK\ValueObjectBuilder\VOB;

class WaybillHandler implements WaybillHandlerInterface
{

    /**
     * @param WaybillForm|Form $form
     * @param FormData $data
     * @return WaybillResponse
     * @throws LogisticDataTooBigException
     * @throws NegativePriceException
     */
    public function __invoke(Form $form, FormData $data): WaybillResponse
    {
        $price = null;
        if ($data->get('waybill.price') !== null) {
            $price = round($data->get('waybill.price', 0), 2) * 100;
            $price = new MoneyValue($price);
        }

        $terms = VOB::buildFromValues(
            DeliveryTerms::class,
            [$data->get('waybill.deliveryTerms_min.0'), $data->get('waybill.deliveryTerms_max.0')]
        );

        $waybill = new Waybill(
            null,
            $price,
            $terms,
            VOB::build(DeliveryType::class, $data->get('waybill.deliveryType.0')),
            $data->get('waybill.cod')
        );

        $logistic = new Logistic(
            $waybill,
            new LogisticStatus(LogisticStatus::CREATED)
        );

        return new WaybillResponse(
            $logistic,
            new WaybillAddress(
                $data->get('address.field.0'),
                new Address(
                    (string) $data->get('address.region'),
                    (string) $data->get('address.city'),
                    (string) $data->get('address.address_1'),
                    (string) $data->get('address.address_2'),
                    (string) $data->get('address.postcode'),
                )
            )
        );
    }
}