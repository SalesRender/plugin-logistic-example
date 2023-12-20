<?php
/**
 * Created for plugin-logistic-example
 * Date: 29.12.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Logistic\Components\Validators;


use SalesRender\Plugin\Components\Form\Components\ValidatorInterface;
use SalesRender\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use SalesRender\Plugin\Components\Form\FormData;
use SalesRender\Plugin\Components\Translations\Translator;

class StringValidator implements ValidatorInterface
{

    private int $minLength;
    private ?int $maxLength;
    private bool $allowNull;

    public function __construct(int $minLength, ?int $maxLength, bool $allowNull = true)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->allowNull = $allowNull;
    }

    public function __invoke($value, FieldDefinition $definition, FormData $data): array
    {
        $errors = [];

        if (is_null($value) && !$this->allowNull) {
            $errors[] = Translator::get('string.validator', 'Поле не может быть пустым');
        }

        if (mb_strlen($value) < $this->minLength) {
            $errors[] = Translator::get(
                'string.validator',
                'Длина строки не должна быть меньше {min}',
                ['min' => $this->minLength],
            );
        }

        if (mb_strlen($value) < $this->minLength) {
            $errors[] = Translator::get(
                'string.validator',
                'Длина строки не должна быть больше {max}',
                ['max' => $this->maxLength],
            );
        }

        return $errors;
    }
}