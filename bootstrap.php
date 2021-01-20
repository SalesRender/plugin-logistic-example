<?php
/**
 * Created for plugin-core-logistic
 * Date: 30.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

use Leadvertex\Plugin\Components\Db\Components\Connector;
use Leadvertex\Plugin\Components\Info\Developer;
use Leadvertex\Plugin\Components\Info\Info;
use Leadvertex\Plugin\Components\Info\PluginType;
use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Actions\UploadAction;
use Leadvertex\Plugin\Core\Logistic\Components\Waybill\WaybillContainer;
use Leadvertex\Plugin\Instance\Logistic\Settings\SettingsForm;
use Leadvertex\Plugin\Instance\Logistic\Waybill\WaybillForm;
use Leadvertex\Plugin\Instance\Logistic\Waybill\WaybillHandler;
use Medoo\Medoo;
use XAKEPEHOK\Path\Path;

# 1. Configure DB (for SQLite *.db file and parent directory should be writable)
Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => Path::root()->down('db/database.db')
]));

# 2. Set plugin default language
Translator::config('ru_RU');

# 3. Set permitted file extensions (* for any ext) and max sizes (in bytes). Pass empty array for disable file uploading
UploadAction::config([]);

# 4. Configure info about plugin
Info::config(
    new PluginType(PluginType::LOGISTIC),
    fn() => Translator::get('info', 'Plugin name'),
    fn() => Translator::get('info', 'Plugin markdown description'),
    ["country" => "RU"],
    new Developer(
        'Your (company) name',
        'support.for.plugin@example.com',
        'example.com',
    )
);

# 5. Configure settings form
Settings::setForm(fn() => new SettingsForm());

# 6. Configure form autocompletes (or remove this block if dont used)
//AutocompleteRegistry::config(function (string $name) {
//    switch ($name) {
//        case 'status': return new StatusAutocomplete();
//        case 'user': return new UserAutocomplete();
//        default: return null;
//    }
//});

# 7. Configure batch forms and handler (or remove this block if dont used)
//BatchContainer::config(
//    function (int $number) {
//    switch ($number) {
//        case 1: return new Form();
//        case 2: return new Form();
//        case 3: return new Form();
//        default: return null;
//    }
//    },
//    new BatchHandlerInterface()
//);

# 8. Configure waybill form and handler
WaybillContainer::config(
    fn() => new WaybillForm(),
    new WaybillHandler()
);