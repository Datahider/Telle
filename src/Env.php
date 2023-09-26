<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of Env
 *
 * @author drweb
 */
class Env {

    const UT_CALLBACK_QUERY = 'callback_query';
    const UT_CHANNEL_POST = 'channel_post';
    const UT_CHOSEN_INLINE_RELULT = 'chosen_inline_result';
    const UT_EDITED_CHANNEL_POST = 'edited_channel_post';
    const UT_EDITED_MESSAGE = 'edited_message';
    const UT_INLINE_QUERY = 'inline_query';
    const UT_MESSAGE = 'message';
    const UT_POLL = 'poll';
    const UT_POLL_ANSWER = 'poll_answer';
    const UT_PRE_CHECKOUT_QUERY = 'pre_checkout_query';
    const UT_SHIPPING_QUERY = 'shipping_query';
    const UT_MY_CHAT_MEMBER = 'my_chat_member';
    const UT_CHAT_MEMBER = 'chat_member';
    const UT_CHAT_JOIN_REQUEST = 'chat_join_request';
    

    public static DBUser $user;
    public static DBChat | null $chat;
    public static int | null $message_thread_id;
    public static DBSession $session;
    public static string $language_code;
    public static string $update_type;

    static protected function initLast() {
        self::$language_code = self::$user->language_code;
        self::$session = new DBSession(self::$user, self::$chat, self::$message_thread_id);
    }

    static function load(\TelegramBot\Api\Types\Update &$update) : bool {
        
        if ($callback_query = $update->getCallbackQuery()) {
            self::initByCallbackQuery($callback_query);
        } elseif ($chanel_post = $update->getChannelPost()) {
            self::initByChannelPost($chanel_post);
        } elseif ($chosen_inline_result = $update->getChosenInlineResult()) {
            self::initByChosenInlineResult($chosen_inline_result);
        } elseif ($edited_channel_post = $update->getEditedChannelPost()) {
            self::initByEditedChannelPost($edited_channel_post);
        } elseif ($edited_message = $update->getEditedMessage()) {
            self::initByEditedMessage($edited_message);
        } elseif ($inline_query = $update->getInlineQuery()) {
            self::initByInlineQuery($inline_query);
        } elseif ($message = $update->getMessage()) {
            self::initByMessage($message);
        } elseif ($poll = $update->getPoll()) {
            self::initByPoll($poll);
        } elseif ($poll_answer = $update->getPollAnswer()) {
            self::initByPollAnswer($poll_answer);
        } elseif ($pre_checkout_query = $update->getPreCheckoutQuery()) {
            self::initByPreCheckoutQuery($pre_checkout_query);
        } elseif ($shipping_query = $update->getShippingQuery()) {
            self::initByShippingQuery($shipping_query);
        } elseif ($my_chat_member = $update->getMyChatMember()) {
            self::initByMyChatMember($my_chat_member);
        } elseif ($chat_member = $update->getChatMember()) {
            self::initByChatMember($chat_member);
        } elseif ($chat_join_request = $update->getChatJoinRequest()) {
            self::initByMyChatMember($chat_join_request);
        } else {
            throw new \Exception("Can't load Env.");
        }
        
        self::initLast();
        
        return false;
    }
    
    static protected function initByCallbackQuery(\TelegramBot\Api\Types\CallbackQuery &$callback_query) {
        self::$update_type = self::UT_CALLBACK_QUERY;
        $from = $callback_query->getFrom();
        self::$language_code = $from->getLanguageCode();
        self::$user = new DBUser($from);
        
        $chat = $callback_query->getMessage()->getChat();
        self::$chat = new DBChat($chat);
        
        self::$message_thread_id = $callback_query->getMessage()->getMessageThreadId();
    }
    
    static protected function initByChannelPost(\TelegramBot\Api\Types\Message &$channel_post) {
        self::$update_type = self::UT_CHANNEL_POST;
        $this->initByMessage($channel_post);
    }
    
    static protected function initByChosenInlineResult(\TelegramBot\Api\Types\Inline\ChosenInlineResult &$chosen_inline_result) {
        self::$update_type = self::UT_CHOSEN_INLINE_RELULT;
        self::$user = null;
        self::$chat = null;
        self::$message_thread_id = null;
    }
    
    static protected function initByEditedChannelPost(\TelegramBot\Api\Types\Message &$edited_channel_post) {
        self::$update_type = self::UT_EDITED_CHANNEL_POST;
        $this->initByMessage($edited_channel_post);
    }
    
    static protected function initByEditedMessage(\TelegramBot\Api\Types\Message &$edited_message) {
        self::$update_type = self::UT_EDITED_MESSAGE;
        $this->initByMessage($edited_message);
    }
    
    static protected function initByInlineQuery(\TelegramBot\Api\Types\Inline\InlineQuery &$inline_query) {
        self::$update_type = self::UT_INLINE_QUERY;
        $from = $inline_query->getFrom();
        self::$language_code = $from->getLanguageCode();
        self::$user = new DBUser($from);
        self::$chat = null;
        self::$message_thread_id = null;
    }
    
    static protected function initByMessage(\TelegramBot\Api\Types\Message &$message) {
        self::$update_type = self::UT_MESSAGE;
        $from = $message->getFrom();
        self::$language_code = $from->getLanguageCode();
        self::$user = new DBUser($from);
        
        $chat = $message->getChat();
        self::$chat = new DBChat($chat);

        self::$message_thread_id = $message->getMessageThreadId();
    }
    
    static protected function initByPoll(\TelegramBot\Api\Types\Poll &$poll) {
        self::$update_type = self::UT_POLL;
        self::$user = null;
        self::$chat = null;
        self::$message_thread_id = null;
    }
    
    static protected function initByPollAnswer(\TelegramBot\Api\Types\PollAnswer &$poll_answer) {
        self::$update_type = self::UT_POLL_ANSWER;
        self::$user = null;
        self::$chat = null;
        self::$message_thread_id = null;
    }
    
    static protected function initByPreCheckoutQuery(\TelegramBot\Api\Types\Payments\Query\PreCheckoutQuery &$pre_checkout_query) {
        self::$update_type = self::UT_PRE_CHECKOUT_QUERY;
        self::$user = null;
        self::$chat = null;
        self::$message_thread_id = null;
    }
    
    static protected function initByShippingQuery(\TelegramBot\Api\Types\Payments\Query\ShippingQuery &$shipping_query) {
        self::$update_type = self::UT_SHIPPING_QUERY;
        self::$user = null;
        self::$chat = null;
        self::$message_thread_id = null;
    }
    
    static protected function initByMyChatMember(\TelegramBot\Api\Types\ChatMemberUpdated &$chat_member) {
        self::$update_type = self::UT_MY_CHAT_MEMBER;
        self::initByChatMember($chat_member);
    }
    
    static protected function initByChatMember(\TelegramBot\Api\Types\ChatMemberUpdated &$chat_member) {
        self::$update_type = self::UT_CHAT_MEMBER;
        $from = $chat_member->getFrom();
        self::$language_code = $from->getLanguageCode();
        self::$user = new DBUser($from);
        
        $chat = $chat_member->getChat();
        self::$chat = new DBChat($chat);

        self::$message_thread_id = null;
    }
    
    static protected function initByChatJoinRequest(\TelegramBot\Api\Types\ChatJoinRequest &$chat_join_request) {
        self::$update_type = self::UT_CHAT_JOIN_REQUEST;
        $from = $chat_join_request->getFrom();
        self::$language_code = $from->getLanguageCode();
        self::$user = new DBUser($from);

        $chat = $chat_join_request->getChat();
        self::$chat = new DBChat($chat);
        
        self::$message_thread_id = null;
    }

}

function __($string, $vars=[]) {
    global $lang;
    
    if (isset($lang[Env::$language_code]) && isset($lang[Env::$language_code][$string])) {
        $string = $lang[Env::$language_code][$string];
    }
    
    foreach ($vars as $key => $value) {
        $string = str_replace("%$key%", $value, $string);
    }
    
    return $string;
}
