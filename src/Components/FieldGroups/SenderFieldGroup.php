<?php
/**
 * Created for plugin-logistic-example
 * Date: 08.02.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Components\FieldGroups;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\BooleanDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\IntegerDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\StringDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Translations\Translator;

class SenderFieldGroup extends FieldGroup
{

    private int $number;
    private string $prefix;
    private int $count;

    public function __construct(int $number, string $prefix = 'sender_', int $count = 3)
    {
        $this->number = $number;
        $this->prefix = $prefix;
        $this->count = $count;

        parent::__construct(
            Translator::get('sender', 'Отправитель #{number}', ['number' => $this->number]),
            null,
            [
                'use' => new BooleanDefinition(
                    Translator::get('sender', 'Включить'),
                    null,
                    function ($value, BooleanDefinition $definition, FormData $data) {
                        $errors = [];

                        $exists = false;
                        for ($i = 1; $i <= $this->count; $i++) {
                            $exists = $exists || (bool) $data->get("{$this->prefix}{$i}.use", false);
                        }

                        if (!$exists) {
                            $errors[] = Translator::get('sender', 'Необходимо включить как минимум одного отправителя');
                        }

                        if (is_null($value)) {
                            return $errors;
                        }

                        if (!is_bool($value)) {
                            $errors[] = Translator::get('sender', 'Некорректное значение');
                        }

                        return $errors;
                    },
                    false
                ),
                'name' => new StringDefinition(
                    Translator::get('sender', 'Название компании'),
                    Translator::get('sender', 'Например "ООО Логистика"'),
                    function ($value, StringDefinition $definition, FormData $data) {
                        $errors = [];

                        if ((bool) $data->get("{$this->prefix}{$this->number}.use") === false) {
                            return $errors;
                        }

                        if (!is_string($value)) {
                            $errors[] = Translator::get('sender', 'Значение должно быть строкой');
                            return $errors;
                        }

                        if (empty(trim($value))) {
                            $errors[] = Translator::get('sender', 'Значение не может быть пустым');
                        }

                        return $errors;
                    }
                ),
                'INN' => new IntegerDefinition(
                    Translator::get('sender', 'ИНН'),
                    null,
                    function ($value, IntegerDefinition $definition, FormData $data) {
                        $errors = [];

                        if ((bool) $data->get("{$this->prefix}{$this->number}.use") === false) {
                            return $errors;
                        }

                        if (!is_integer($value)) {
                            $errors[] = Translator::get('sender', 'Значение должно состоять из цифр');
                            return $errors;
                        }

                        if (empty($value)) {
                            $errors[] = Translator::get('sender', 'Значение не может быть пустым');
                        }

                        return $errors;
                    }
                ),
            ]
        );
    }

}