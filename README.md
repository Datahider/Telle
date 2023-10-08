# Telle - a simple template for nice Telegram bots

This is a skeleton bot which can deal in web-server and standalone (cli) modes.
In cli mode it supports multiple worker-processes if it is needed for high-loaded sites.
Also it has its own cron sheduler which allows to start AbstractBackgroundProcess descendants as a part of cron process or in background (recomended for time consuming tasks)

## How to use

1. Create etc/bot_config.php. (the path is relative to your project root:
```
$token      = 'The_bot:token_received_from_BotFather';
$ca_cert    = 'Path to cacert.pem';
$db_host    = 'your.database.host';
$db_user    = 'db_username';
$db_pass    = 'Db-PAssWorD';
$db_name    = 'database_name';
$db_prefix  = 'table_prefix_';
```

2. Create your own handler(s)
```
use \losthost\telle\abst\AbstractHandlerMessage;

class HandlerDoNothing extends AbstractHandlerMessage {

    public function isFinal() : bool {return false;}
    
    protected function init() : void {}

    protected function check(\TelegramBot\Api\Types\Message &$message) : bool {
        if (!$message) {
            return false;
        }
        return (bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        // Do nothing
        return true;
    }
}
```

3. Create a starter file (ex. index.php) which contains:
```
use losthost\telle\Bot;

// Initialize bot
Bot::setup();

// Do your own initialization
// add some code here

// Add handler(s)
Bot::addHandler(HandlerDoNothing::class);

// Start processing updates
Bot::run();
```
(See src/samples folder for more examples. See src/abst for handler ancestors)

## TODO
Now it seems nothing to do