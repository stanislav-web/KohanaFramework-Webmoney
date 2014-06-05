<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Interface WebMerchantMessageInterface
 * Интерфейс описывает конфигурацию Flash Messenger
 * @package Kohana Framework 3.3.1
 * @since PHP >=5.3.xx
 * @author Stanislav WEB (stanisov@gmail.com)
 */
interface WebMerchantMessageInterface
{
    /**
     * setMessage(array $message) Контейнер Flash Messenger
     * @var string $code Константа с кодом сообщения
     * @var string $status Статус сообщения
     * @var array $additionalData Дополнительные параметры об информации платежей
     * @access static
     * return null
     */
    public static function setMessage($code, $status, $additionalData);  
}
