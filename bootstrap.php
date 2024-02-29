<?php
/**
 * Created for plugin-core-logistic
 * Date: 30.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

use SalesRender\Plugin\Components\Batch\BatchContainer;
use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Info\Developer;
use SalesRender\Plugin\Components\Info\Info;
use SalesRender\Plugin\Components\Info\PluginType;
use SalesRender\Plugin\Components\Purpose\LogisticPluginClass;
use SalesRender\Plugin\Components\Purpose\PluginEntity;
use SalesRender\Plugin\Components\Settings\Settings;
use SalesRender\Plugin\Components\Translations\Translator;
use SalesRender\Plugin\Core\Actions\Upload\LocalUploadAction;
use SalesRender\Plugin\Core\Actions\Upload\UploadersContainer;
use SalesRender\Plugin\Core\Logistic\Components\Actions\Shipping\ShippingContainer;
use SalesRender\Plugin\Core\Logistic\Components\Waybill\WaybillContainer;
use SalesRender\Plugin\Instance\Logistic\Batch\Batch_1;
use SalesRender\Plugin\Instance\Logistic\Batch\BatchShippingHandler;
use SalesRender\Plugin\Instance\Logistic\Components\Actions\CancelAction;
use SalesRender\Plugin\Instance\Logistic\Components\Actions\RemoveOrdersAction;
use SalesRender\Plugin\Instance\Logistic\Settings\SettingsForm;
use SalesRender\Plugin\Instance\Logistic\Waybill\WaybillForm;
use SalesRender\Plugin\Instance\Logistic\Waybill\WaybillHandler;
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
UploadersContainer::addDefaultUploader(new LocalUploadAction([]));

# 4. Configure info about plugin
Info::config(
    new PluginType(PluginType::LOGISTIC),
    fn() => Translator::get('info', 'Example logistic'),
    fn() => Translator::get('info', 'Example **logistic** description'),
    [
        "class" => LogisticPluginClass::CLASS_DELIVERY,
        "entity" => PluginEntity::ENTITY_ORDER,
        "currency" => ["RUB"],
        "codename" => "SR_LOGISTIC_EXAMPLE",
    ],
    new Developer(
        'Example company',
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
BatchContainer::config(
    function (int $number) {
        switch ($number) {
            case 1: return new Batch_1();
            default: return null;
        }
    },
    new BatchShippingHandler()
);

# 8. Configure waybill form and handler
WaybillContainer::config(
    fn() => new WaybillForm(),
    new WaybillHandler()
);

# Configure shipping cancel action (optional)
ShippingContainer::config(
    new CancelAction(),
    new RemoveOrdersAction(),
);