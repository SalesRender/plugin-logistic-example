<?php
/**
 * Created for plugin-logistic-example
 * Date: 28.12.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Waybill;


use Leadvertex\Plugin\Addon\EnumFields\FieldsValidator;
use Leadvertex\Plugin\Addon\EnumFields\FieldsValues;
use Leadvertex\Plugin\Addon\EnumFields\FieldTypesRegistry;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\BooleanDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\FloatDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\StringDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Logistic\Components\Fields\DeliveryTypeField;
use Leadvertex\Plugin\Instance\Logistic\Components\Validators\StringValidator;
use League\ISO3166\ISO3166;
use Respect\Validation\Rules\CountryCode;

class WaybillForm extends Form
{

    public function __construct()
    {
        parent::__construct(
            Translator::get('waybill', 'Накладная'),
            Translator::get('waybill', 'Накладная для подготовки заказа к отправке'),
            [
                'waybill' => new FieldGroup(
                    Translator::get('waybill', 'Доставка'),
                    null,
                    [
                        'track' => new StringDefinition(
                            Translator::get('waybill', 'Трек номер отправления'),
                            null,
                            function ($value, FieldDefinition $definition, FormData $data) {
                                $errors = [];

                                if ($value === null) {
                                    return $errors;
                                }

                                if (!preg_match('~^[a-z\d\-_]{6,25}$~ui', $value)) {
                                    $errors[] = "Трек может содержать только символы A-Z, 0-9, тире и подчеркивание";
                                }

                                $stringValidator = new StringValidator(6, 25);
                                return array_merge($errors, $stringValidator($value, $definition, $data));
                            },
                        ),
                        'price' => new FloatDefinition(
                            Translator::get('waybill', 'Стоимость доставки'),
                            null,
                            function ($value) {
                                $errors = [];
                                if ($value < 0) {
                                    $errors[] = Translator::get('waybill', 'Стоимость доставки не может быть ниже нуля');
                                }
                                return $errors;
                            }
                        ),
                        'deliveryTerms_min' => new ListOfEnumDefinition(
                            'Срок доставки (минимальный)',
                            null,
                            function ($value, ListOfEnumDefinition $definition, FormData $data) {
                                $value = $value[0] ?? null;
                                $errors = [];

                                if (is_null($data->get('waybill.deliveryTerms_min.0')) || is_null($data->get('waybill.deliveryTerms_min.0'))) {
                                    $errors[] = 'Должны быть указаны оба срока доставки или ни одного';
                                    return $errors;
                                }

                                if ($value < 0) {
                                    $errors[] = Translator::get('waybill', 'Срок доставки не может быть меньше часа');
                                }

                                if ($value > 8760) {
                                    $errors[] = Translator::get('waybill', 'Срок доставки не может быть больше 8760 часов');
                                }

                                if ($value > $data->get('waybill.deliveryTerms_max.0')) {
                                    $errors[] = Translator::get('waybill', 'Минимальный срок доставки не может превышать максимальный');
                                }

                                return $errors;
                            },
                            new StaticValues([
                                1 => [
                                    'title' => Translator::get('waybill', '1 час'),
                                    'group' => Translator::get('waybill', 'В часах'),
                                ],
                                6 => [
                                    'title' => Translator::get('waybill', '6 часов'),
                                    'group' => Translator::get('waybill', 'В часах'),
                                ],
                                12 => [
                                    'title' => Translator::get('waybill', '12 часов'),
                                    'group' => Translator::get('waybill', 'В часах'),
                                ],
                                24 => [
                                    'title' => Translator::get('waybill', '1 день'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 3 => [
                                    'title' => Translator::get('waybill', '3 дня'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 7 => [
                                    'title' => Translator::get('waybill', '7 дней'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 14 => [
                                    'title' => Translator::get('waybill', '14 дней'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 30 => [
                                    'title' => Translator::get('waybill', '30 дней'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                            ]),
                            new Limit(1, 1)
                        ),
                        'deliveryTerms_max' => new ListOfEnumDefinition(
                            'Срок доставки (максимальный)',
                            null,
                            function ($value, ListOfEnumDefinition $definition, FormData $data) {
                                $value = $value[0] ?? null;
                                $errors = [];

                                if (is_null($data->get('waybill.deliveryTerms_min.0')) || is_null($data->get('waybill.deliveryTerms_min.0'))) {
                                    $errors[] = 'Должны быть указаны оба срока доставки или ни одного';
                                    return $errors;
                                }

                                if ($value < 0) {
                                    $errors[] = Translator::get('waybill', 'Срок доставки не может быть меньше часа');
                                }

                                if ($value > 8760) {
                                    $errors[] = Translator::get('waybill', 'Срок доставки не может быть больше 8760 часов');
                                }

                                if ($value < $data->get('waybill.deliveryTerms_min.0')) {
                                    $errors[] = Translator::get('waybill', 'Минимальный срок доставки не может превышать максимальный');
                                }

                                return $errors;
                            },
                            new StaticValues([
                                1 => [
                                    'title' => Translator::get('waybill', '1 час'),
                                    'group' => Translator::get('waybill', 'В часах'),
                                ],
                                6 => [
                                    'title' => Translator::get('waybill', '6 часов'),
                                    'group' => Translator::get('waybill', 'В часах'),
                                ],
                                12 => [
                                    'title' => Translator::get('waybill', '12 часов'),
                                    'group' => Translator::get('waybill', 'В часах'),
                                ],
                                24 => [
                                    'title' => Translator::get('waybill', '1 день'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 3 => [
                                    'title' => Translator::get('waybill', '3 дня'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 7 => [
                                    'title' => Translator::get('waybill', '7 дней'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 14 => [
                                    'title' => Translator::get('waybill', '14 дней'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                                24 * 30 => [
                                    'title' => Translator::get('waybill', '30 дней'),
                                    'group' => Translator::get('waybill', 'В днях'),
                                ],
                            ]),
                            new Limit(1, 1)
                        ),
                        'deliveryType' => new DeliveryTypeField(),
                        'cod' => new BooleanDefinition(
                            'Оплата при получении',
                            null,
                            function ($value) {
                                if (!is_bool($value) && !is_null($value)) {
                                    return [Translator::get('waybill', 'Некорректное значение')];
                                }
                                return [];
                            },
                            false,
                        ),
                    ]
                ),
                'address' => new FieldGroup(
                    Translator::get('waybill', 'Адрес'),
                    null,
                    [
                        'field' => new ListOfEnumDefinition(
                            Translator::get('waybill', 'Адрес'),
                            null,
                            new FieldsValidator([FieldTypesRegistry::ADDRESS], true),
                            new FieldsValues([FieldTypesRegistry::ADDRESS]),
                            new Limit(1, 1),
                        ),
                        'postcode' => new StringDefinition(
                            Translator::get('address', 'Почтовый индекс'),
                            null,
                            new StringValidator(5, 8, true),
                        ),
                        'region' => new StringDefinition(
                            Translator::get('address', 'Регион'),
                            null,
                            new StringValidator(1, 200, true),
                        ),
                        'city' => new StringDefinition(
                            Translator::get('address', 'Город'),
                            null,
                            new StringValidator(1, 200, true),
                        ),
                        'address_1' => new StringDefinition(
                            Translator::get('address', 'Адрес 1'),
                            null,
                            new StringValidator(1, 200, true),
                        ),
                        'address_2' => new StringDefinition(
                            Translator::get('address', 'Адрес 1'),
                            null,
                            new StringValidator(0, 200, true),
                        ),
                        'country' => new ListOfEnumDefinition(
                            Translator::get('address', 'Код страны'),
                            null,
                            function ($code) {
                                $errors = [];

                                if (!(new CountryCode())->validate($code)) {
                                    $errors[] = Translator::get('code.validator', 'Код страны не верный');
                                }

                                return $errors;
                            },
                            new StaticValues($this->getCountriesList()),
                            new Limit(1, 1),
                        ),
                        'location.latitude' => new FloatDefinition(
                            Translator::get('location.latitude', 'Широта'),
                            null,
                            function ($value) {
                                $errors = [];
                                if ($value < -90 || $value > 90) {
                                    $errors[] = Translator::get('location.latitude', 'Широта может быть от -90 до 90');
                                }
                                return $errors;
                            }
                        ),
                        'location.longitude' => new FloatDefinition(
                            Translator::get('location.longitude', 'Долгота'),
                            null,
                            function ($value) {
                                $errors = [];
                                if ($value < -180 || $value > 180) {
                                    $errors[] = Translator::get('location.longitude', 'Долгота может быть от -180 до 180');
                                }
                                return $errors;
                            }
                        ),
                    ]
                ),
            ],
            Translator::get('waybill', 'Применить'),
        );
    }

    private function getCountriesList(): array {
        $iso = new ISO3166();
        $values = [];
        foreach ($iso->all() as $data) {
            $values[$data['alpha2']] = [
                'title' => Translator::get($data['alpha2'], $data['name']),
                'group' => Translator::get('countries', 'Страны'),
            ];
        }
        return $values;
    }
}