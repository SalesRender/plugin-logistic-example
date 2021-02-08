<?php
/**
 * Created for plugin-logistic-example
 * Date: 28.12.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Settings;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\PasswordDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\StringDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Logistic\Components\FieldGroups\SenderFieldGroup;

class SettingsForm extends Form
{

    public function __construct()
    {
        parent::__construct(
            Translator::get('settings', 'Настройки'),
            null,
            [
                'main' => new FieldGroup(
                    Translator::get('settings', 'Основные настройки'),
                    null,
                    [
                        'login' => new StringDefinition(
                            Translator::get('settings', 'Логин'),
                            null,
                            function () {
                                return [];
                            }
                        ),
                        'password' => new PasswordDefinition(
                            Translator::get('settings', 'Пароль'),
                            null,
                            function () {
                                return [];
                            }
                        ),
                    ]
                ),
                'sender_1' => new SenderFieldGroup(1),
                'sender_2' => new SenderFieldGroup(2),
                'sender_3' => new SenderFieldGroup(3),
            ],
            Translator::get('settings', 'Сохранить'),
        );
    }

}