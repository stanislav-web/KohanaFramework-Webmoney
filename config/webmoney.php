<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Конфигурация аккаунта Webmoney
 * для осуществления платежей через Merchant
 * @author Stanislav WEB (stanisov@gmail.com)
 */

return array(
    
    // Обработчик WM запросов на платежи в Merchant
    'url'       =>  'https://merchant.webmoney.ru/lmi/payment.asp',
    
    // Обработчики WM XML запросов для осуществления платежей не покидая сайт
    'url_xml_request'       =>  'https://merchant.webmoney.ru/conf/xml/XMLTransRequest.asp',
    'url_xml_confirm'       =>  'https://merchant.webmoney.ru/conf/xml/XMLTransConfirm.asp',
    
    // Валюта системы
    'currency'  =>  'WMR',
    
    // Поле в системе , отвечающее за сумму платежа
    'amount'    =>  'amount',    

    // WMID
    'wmid'      =>  '000000000000', 
    
    // Пароль от файла ключей
    'keypass'   =>  '00000', 
    
    // Рублевый кошелек
    'wallet'    =>  'R000000000000',
    
    // SecretKey из настроек нашего торгового кошелька
    'secret_key'    =>  '0123456789ABC',
    
    // Интервал , за который делать проверку транзакций на кошельке
    'date_interval'    =>  '-1 week',    
    
    // Метод общения client -> server 
    'method'    =>  'POST',      
    
    // View с формой Пополнения баланса через Merchant
    'refillbalance_view'    =>  'webmoney/refill', 
    
    // View с формой оплаты Merchant
    'paymerchant_view'      =>  'webmoney/paymerchant',
    
    // View с формой оплаты в полуавтоматиеском режиме (с проверкой счета)
    'paywallet_view'        =>  'webmoney/paywallet',
    
    // View с успешной транзакцией (для WM Merchant)
    'success'               =>  'webmoney/success',
    
    // View с ошибкой (для WM Merchant)
    'fail'                  =>  'webmoney/fail',
    
    // View c завершением оплаты (для WM Merchant)
    'result'                =>  'webmoney/result',    

    // Режим тестирования (отладки и логирования)
    'debug'                 =>  1,
    
    // Путь к файлу логов, если включен режим отладки
    'log'      =>  MODPATH.'webmoney/wm.log'
);

