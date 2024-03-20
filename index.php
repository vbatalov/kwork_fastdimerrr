<?php

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

require "vendor/autoload.php"; // Загрузка пакетов
require "database.php"; // Конфигурация базы данных
$database = new Database(); // Инициализация базы данных


/**
 * Конфигурация и инициализация бота и клиента
 * @var BotApi $bot используется для отправки сообщений пользователю
 * @var Client $client используется для обработки входящих сообщений от пользователя
 */

$BOT_TOKEN = "5742517907:AAFwbcDu4-tcM5-LZXngMEp2I-PNOeyx2yI";
$bot = new BotApi($BOT_TOKEN);
$client = new Client($BOT_TOKEN);

/**
 * Если в адресной строке указать example.ru/?register_bot
 * Бот зарегистрирует URL
 */

if (isset($_GET['register_bot'])) {
    $url = $_GET['register_bot'];
    register_bot($bot, $url);

    print_r("<pre>");
    print_r($bot->getWebhookInfo());
    exit ("Адрес зарегистрирован: $url");
}

/** Используется для регистрации URL адреса бота */
function register_bot(BotApi $bot, $url): string
{
    try {
        $bot->deleteWebhook(true);
        $bot->setWebhook(url: $url);
        return $url;
    } catch (\TelegramBot\Api\Exception $e) {
        exit ($e->getMessage());
    }
}


/** Обработка команды /start */
$client->command('start', function (Message $message) use ($database, $bot) {
    $cid = $message->getChat()->getId();
    $first_name = $message->getChat()->getFirstName();
    $last_name = $message->getChat()->getLastName();


    // Добавить пользователя в базу данных
    $database->addUser(cid: $cid, first_name: $first_name, last_name: $last_name);

    $keyboard = new ReplyKeyboardMarkup(
        [
            [
                [
                    "text" => "Отправить телефон",
                    "request_contact" => true,
                ],
            ],
        ],
        true, true
    );

    $bot->sendMessage(chatId: $cid,
        text: 'Отправьте свой телефон',
        parseMode: "HTML",
        disablePreview: false,
        replyToMessageId: false,
        replyMarkup: $keyboard);
});

// Обработка всех сообщений
$client->on(function (Update $update) use ($bot, $database) {
    /** @var Message $message содержит всю информацию о сообщении */
    $message = $update->getMessage();
    /** @var string $cid содержит информацию о ID пользователя */
    $cid = $message->getChat()->getId();
    /** @var \TelegramBot\Api\Types\Contact $contact содержит информацию о отправленном контакте */
    $contact = $message->getContact();

    /** Обработка полученного контакта */
    if (!empty($contact)) {
        // Проверка: Пользователь должен отправить СВОЙ контакт, а не любой другой из записной книги
        if ($contact->getUserId() == $cid) {
            $database->addPhone(cid: $cid, phone: $contact->getPhoneNumber());
            $bot->sendMessage(chatId: $cid, text: "Номер сохранен.");
        } else {
            $bot->sendMessage(chatId: $cid, text: "Вы отправили чужой контактный номер.");
        }
    } else {
        $bot->sendMessage(chatId: $cid, text: "Вы не отправили контакт");
    }


}, function () {
    return true;
});

try {
    $client->run();
} catch (InvalidJsonException $e) {
    exit($e->getMessage());
}