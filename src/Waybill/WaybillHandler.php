<?php
/**
 * Created for plugin-logistic-example
 * Date: 28.12.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Logistic\Waybill;


use SalesRender\Components\Address\Address;
use SalesRender\Components\Address\Location;
use SalesRender\Components\MoneyValue\MoneyValue;
use SalesRender\Plugin\Components\Form\Form;
use SalesRender\Plugin\Components\Form\FormData;
use SalesRender\Plugin\Components\Logistic\Exceptions\LogisticDataTooBigException;
use SalesRender\Plugin\Components\Logistic\Exceptions\NegativePriceException;
use SalesRender\Plugin\Components\Logistic\Logistic;
use SalesRender\Plugin\Components\Logistic\LogisticStatus;
use SalesRender\Plugin\Components\Logistic\Waybill\DeliveryTerms;
use SalesRender\Plugin\Components\Logistic\Waybill\DeliveryType;
use SalesRender\Plugin\Components\Logistic\Waybill\Track;
use SalesRender\Plugin\Components\Logistic\Waybill\Waybill;
use SalesRender\Plugin\Core\Logistic\Components\Waybill\Response\WaybillAddress;
use SalesRender\Plugin\Core\Logistic\Components\Waybill\Response\WaybillResponse;
use SalesRender\Plugin\Core\Logistic\Components\Waybill\WaybillHandlerInterface;
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
        $track = null;
        if ($data->get('waybill.track') !== null) {
            $track = new Track($data->get('waybill.track'));
        }

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
            $track,
            $price,
            $terms,
            VOB::build(DeliveryType::class, $data->get('waybill.deliveryType.0')),
            $data->get('waybill.cod')
        );

        $logistic = new Logistic(
            $waybill,
            new LogisticStatus(LogisticStatus::CREATED)
        );

        $location = null;
        if ($data->get('address.location_latitude') !== null && $data->get('address.location_longitude') !== null) {
            $location = new Location(
                $data->get('address.location_latitude'),
                $data->get('address.location_longitude'),
            );
        }

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
                    (string) (is_array($data->get('address.countryCode'))) ? $data->get('address.countryCode.0') : $data->get('address.countryCode'),
                    $location
                )
            )
        );
    }
}