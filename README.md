# Telle - простой фреймворк для клёвых Telegram-ботов

Этот фреймворк позволяет создавать ботов Telegram, которые могут работать как в режиме веб-сервера через веб-хук, так и в автономном (cli) режиме, получая обновления через getUpdates().
В автономном режиме поддерживается многопоточность, через запуск worker-процессов если это необходимо для высоконагруженных проектов.
Также фреймворк имеет свой собственный планировщик заданий, который позволяет стартовать классы-потомки AbstractBackgroundProcess внутри потока планировщика или в отдельном потоке (рекомендуется для процессов потребляющих много времени)

## Быстрый старт

1. Создайте проект и подключите Telle через composer: 
```
    "require": {
        "losthost/telle": "^4",
    },
```

2. Создайте etc/bot_config.php:
```
$token      = 'bot:token_полученный_от_BotFather';
$ca_cert    = 'Путь/к/cacert.pem';
$timezone   = 'Default/Timezone';       // ex. Europe/Moscow
$db_host    = 'your.database.host';
$db_user    = 'db_username';
$db_pass    = 'Db-PAssWorD';
$db_name    = 'database_name';
$db_prefix  = 'table_prefix_';
```

3. Создайте обработчик
```
use losthost\telle\abst\AbstractHandlerCommand;
use losthost\telle\Bot;

class CommandStart extends AbstractHandlerCommand {

    const COMMAND = 'start';

    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        Bot::$api->sendMessage(Bot::$chat->id, 'Hello World!');
        return true;
    }
}
```

4. Создайте файл запуска бота (например index.php) содержащий следующие строки:
```
use losthost\telle\Bot;

// Инициализация бота
Bot::setup();

// Ваша собственная инициализация если нужна
// (добавьте сюда какой-нибудь код)

// Добавьте обработчик(и)
Bot::addHandler(CommandStart::class);

// Запустите бота
Bot::run();
```
(Посмотрите папку src/samples, там есть другие примеры обработчиков. В папке src/abst находятся классы-родители обработчиков)

## TODO
Создать репозиторий с примером бота и сделать ссылку на него