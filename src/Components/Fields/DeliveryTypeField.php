<?php
/**
 * Created for plugin-logistic-example
 * Date: 08.02.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Logistic\Components\Fields;


use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use SalesRender\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use SalesRender\Plugin\Components\Logistic\Waybill\DeliveryType;
use SalesRender\Plugin\Components\Translations\Translator;

class DeliveryTypeField extends ListOfEnumDefinition
{

    public function __construct()
    {
        parent::__construct(Translator::get('deliveryType', 'Способ доставки'),
            null,
            function ($value) {
                $errors = [];
                if (is_null($value)) {
                    return $errors;
                }

                $value = (string) $value[0] ?? null;

                if (is_null($value)) {
                    return $errors;
                }

                if (!DeliveryType::isValid($value)) {
                    $errors[] = Translator::get('deliveryType', 'Неизвестный способ доставки');
                }

                return $errors;
            },
            new StaticValues([
                DeliveryType::SELF_PICKUP => [
                    'title' => Translator::get('deliveryType', 'Самовывоз со склада'),
                    'group' => Translator::get('deliveryType', 'Самовывоз'),
                ],
                DeliveryType::PICKUP_POINT => [
                    'title' => Translator::get('deliveryType', 'Самовывоз с пункта выдачи заказов'),
                    'group' => Translator::get('deliveryType', 'Самовывоз'),
                ],
                DeliveryType::COURIER => [
                    'title' => Translator::get('deliveryType', 'Курьерская доставка'),
                    'group' => Translator::get('deliveryType', 'Курьер'),
                ],
            ]),
            new Limit(1, 1));
    }

}