 <?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Interface WebMerchantInterface
 * Интерфейс описывает методы общения через WebMoney Merchant
 * @package Kohana Framework 3.3.1
 * @since PHP >=5.3.xx
 * @author Stanislav WEB (stanisov@gmail.com)
 */
interface WebMerchantInterface
{
    /**
     * setConfig(Kohana_Config_File_Reader $config) Установка настроек работы модуля
     * @var Kohana_Config_File_Reader $config Настройки интерфейса
     * @access public
     * return null
     */
    public function setConfig(Kohana_Config_File_Reader $config);  
    
    /**
     * getConfig() Выгрузка настроек модуля
     * @access public
     * return object
     */
    public function getConfig();     
    
    /**
     * setPayFields() Обязательные поля для формы оплаты
     * @param object $values ключ -> значения для полей
     * @access public
     * return array
     */
    public function setPayFields();    
    
    /**
     * getPayFields() Выгрузка полей формы оплаты со значениями
     * @access public
     * return array
     */
    public function getPayFields(); 
    
    /**
     * checkPayFields(Validation $form) Валидация формы для оплаты Merchant'a
     * @param Validation $form POST данные для валидации
     * @access public
     * return array
     */
    public function checkPayFields(Validation $form);  
    
    /**
     * proccessResult(Request $request)  Проверка прохождения оплаты счета
     * @param Request $request объект с входящими параметрами (POST или GET)
     * @access public
     * return array
     */        
    public function proccessResult(Request $request);
}
