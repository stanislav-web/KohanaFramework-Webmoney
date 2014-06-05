<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * class WebmoneyMessenger
 * Flash Messenger WebMoney
 * @package Kohana Framework 3.3.1
 * @since PHP >=5.3.xx
 * @author Stanislav WEB (stanisov@gmail.com)
 */
class WebmoneyMessenger implements WebMerchantMessageInterface
{
    private static $_message = array(
        'ERROR_EMPTY_CONGIF_FILE'       =>  'Конфигурационный файл пустой! Процесс оплаты приостановлен',
        'ERROR_EMPTY_ORDER'             =>  'Отсутствуют параметры заказа',
        'ERROR_EMPTY_RESPONSE_PERAMS'   =>  'Не получен ответ о сервера Webmoney Merchant',
        'ERROR_NON_AJAX_REQUEST'        =>  'Только для Ajax запросов',
        'ERROR_NOT_DEFINE_WMID'         =>  'Не задан WMID!',
        'ERROR_HISTORY_DOESNT_LOADED'   =>  'Не возможно загрузить историю операций',
        'ERROR_PAYMENT_NOT_FOUND'       =>  'Счет еще не был оплачен',
        'ERROR_NOT_RESPONSE_FROM_XML'   =>  'Не получен ответ XML',
        'ERROR_NOT_VALID_AUTH'          =>  'Не верные параметры авторизации',
        'ERROR_INVOICE_NOT_FOUND'       =>  'Не найден оплачиваемый счет',
        'ERROR_WRONG_DATE_FORMAT'       =>  'Не верный формат диапазона даты платежа',        
        'ERROR_WRONG_PAY_NUMBER'        =>  'Не верный номер оплачиваемого счета',   
        'ERROR_AMOUNT_NOT_ENOUGHT'      =>  'Оплаченной суммы не достаточно для оплаты этого счета',
        'ERROR_PAY_ISNT_RECEIVED'       =>  'Оплата еще не поступила',
        'ERROR_UNDEFINED_KEY_FOR_LOG'   =>  'Не известный ключ при записи в Лог файл',
        'ERROR_WRONG_PAYMENT_ID'        =>  'Не верный формат номера оплаты',
        'ERROR_UNKNOW_RESPONSE'         =>  'Не известный ответ сервера WM Merchant',
        'ERROR_WRONG_HASH'              =>  'Ошибка транзакции! Не верный ключ операции',
        'SUCCESS_TRANSACTION'           =>  'Перевод на сумму amount wallet завершен!'
    );

    /**
     * setMessage($code, $message) Установка Flash Messenger
     * @param string $code Код сообщения
     * @param string $status Статус сообщения
     * @access static
     * @return array
     */
    public static function setMessage($code, $status = '<b>ERROR:</b> ', $additionalData = array())
    {
        if(!empty($additionalData))  throw new Exception($status.UTF8::str_ireplace(array_flip($additionalData), $additionalData, self::$_message[$code]));
            else throw new Exception($status.self::$_message[$code]);
    }    
}