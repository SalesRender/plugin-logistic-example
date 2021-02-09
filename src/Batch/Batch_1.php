<?php
/**
 * Created for plugin-logistic-example
 * Date: 08.02.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Batch;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\CallableValues;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Logistic\Components\Fields\DeliveryTypeField;

class Batch_1 extends Form
{

    public function __construct()
    {
        parent::__construct(
            Translator::get('batch', 'Выгрузка заказов'),
            Translator::get('batch', 'Выгрузка заказов в логистическую компанию'),
            [
                'sender' => new FieldGroup(
                    Translator::get('batch', 'Отправитель'),
                    null,
                    [
                        'company' => new ListOfEnumDefinition(
                            Translator::get('batch', 'Компания'),
                            Translator::get('batch', 'Компания, от имени которой будет осуществлена отправка'),
                            function ($value, ListOfEnumDefinition $definition) {
                                $errors = [];

                                if (is_null($value)) {
                                    $errors[] = Translator::get('batch', 'Необходимо выбрать компанию');
                                    return $errors;
                                }

                                if (!is_array($value)) {
                                    $errors[] = Translator::get('batch', 'Некорректное значение');
                                    return $errors;
                                }

                                $value = $value[0] ?? 0;

                                if (!isset($definition->getValues()->get()[$value])) {
                                    $errors[] = Translator::get('batch', 'Отправитель не включен или не настроен');
                                }

                                return $errors;
                            },
                            new CallableValues(function () {
                                Settings::guardIntegrity();
                                $data = Settings::find()->getData();
                                $senders = [];
                                for ($i = 1; $i <= 3; $i++) {
                                    if ($data->get("sender_{$i}.use", false)) {
                                        $senders[$i] = [
                                            'title' => $data->get("sender_{$i}.name"),
                                            'group' => Translator::get('batch', 'Отправитель'),
                                        ];
                                    }
                                }
                                return $senders;
                            }),
                            new Limit(1, 1)
                        ),
                    ],
                ),
                'override' => new FieldGroup(
                    Translator::get('batch', 'Переопределение накладных'),
                    Translator::get('batch', 'Позволяет переопределить некоторые параметры накладных при осуществлении выгрузки'),
                    [
                         'deliveryType' => new DeliveryTypeField(),
                    ],
                ),
            ],
            Translator::get('batch', 'Выгрузить')
        );
    }

}