<?php defined('SYSPATH') or die('No direct script access.');

/**
 * WmHelper
 * Помошник интеграции форм в страницу
 * @package Kohana Framework 3.3.1
 * @since PHP >=5.3.xx
 * @author Stanislav WEB (stanisov@gmail.com)
 */
class WmHelper extends WebmoneyPayment {
    
    /**
     * Модель с содержимым оплачиваемого счета
     * @access private
     * @var object $_model
     */
    private $_model = null;
    
    /**
     * __construct($order) Конструктор
     * @param Model $order модель с чеком или заказом
     * @access public
     * @throws Exception Если нет заказа
     */
    public function __construct($order) 
    {
        if(!$order) throw new Exception(Kohana::message('webmoney', 'empty_order'));
        parent::__construct($this->_model = $order);
    }
    
    /**
     * payWalletForm() Форма для полуавтоматической оплаты с проверкой оплаты счета
     * @access public
     * @return content
     */
    public function payWalletForm()
    {  
        $payfield = array(
            'cancel' => array(
                'type'  =>  'button',
                'name'  =>  'cancel',
                'class' =>  'btn btn-default',
                'value' =>  Kohana::message('webmoney', 'cancel'),
                'required'  =>  true,
                'onclick'   =>  'javaScript:request("'.URL::base().'user/webmoney/cancel/'.$this->_model->id.'"); return false;',
            ),
            'check' => array(
                'type'  =>  'button',
                'name'  =>  'check',
                'class' =>  'btn btn-default',
                'value' =>  Kohana::message('webmoney', 'check'),
                'required' =>  true,
                'onclick'   =>  'javaScript:request("'.URL::base().'user/webmoney/check/'.$this->_model->id.'"); return false;',
            )            
        );
        
        return   View::factory($this->getConfig()->paywallet_view , array(
            'config'                =>  $this->getConfig(),     // Настройки модуля
            'fields'                =>  $payfield,              // Поля для формы оплаты
            'checkoutinfo2'          =>  UTF8::str_ireplace(     // Информация о чеке оплаты
                    
                                            array(':amount', ':currency', ':wallet', ':code'), 
                                            array($this->_model->amount, $this->getConfig()->currency, $this->getConfig()->wallet, $this->_model->id), 
                                            Kohana::message('webmoney', 'checkoutinfo2')
                                        ),
            'confirmation2'          =>  Kohana::message('webmoney', 'confirmation2'),     // Информация для пользователя (option)
           )
        )->render();          
    }
    
    /**
     * payMerchantForm($submitBtn = false) Форма оплаты счета или заказа через Merchant
     * @param boolean $submitBtn Показывать кнопку sumbit формы
     * @access public
     * @return content
     */
    public function payMerchantForm($submitBtn = false)
    {
        // ID оплачиваемого счета
        // Должен включать id, и сумму платежа
        
        $payfield = $this->getPayFields();
        if($submitBtn) $payfield = array_merge($payfield,  array('submit'      =>  array(
                'type'  =>  'submit',
                'name'  =>  'submit',
                'value' =>  'OK',
                'required' =>  true,
            )));
        
        return   View::factory($this->getConfig()->paymerchant_view , array(
            'config'                =>  $this->getConfig(),     // Настройки модуля
            'fields'                =>  $payfield,  // Поля для формы оплаты
            'checkoutinfo'          =>  UTF8::str_ireplace(         // Информация о чеке оплаты
                                            array(':number', ':amount', ':currency'), 
                                            array($this->_model->id, $this->_model->amount, $this->getConfig()->currency), 
                                            Kohana::message('webmoney', 'checkoutinfo')
                                        ),  
            'confirmation'          =>  Kohana::message('webmoney', 'confirmation'),    // Информация для пользователя (option)
           )
        )->render();          
    }
    
    /**
     * refillBalanceMerchantForm($fields = array()) Форма пополнение баланса через Merchant
     * @param array $fields Дополнительные поля
     * @access public
     * @return content
     */
    public function refillBalanceMerchantForm($fields = array())
    {
        // Получаю стандартные поля
        $newFields = array();
        $payfield = $this->getPayFields();
        $newFields  =   $payfield;
        if(!empty($fields))
        {
            foreach($fields as $k => $v)
            {
                // Если нет ключа (поля) то добавляю его
                if(!isset($payfield[$k]))  $newFields[$k] =  $v; 
                
                // Меняю поле, если оно уже зарезервированно системой WM
                if(isset($payfield[$k]) && !empty($payfield[$k]))  $newFields[$k] = array_replace($payfield[$k], $fields[$k]);
            }
        }

        return  View::factory($this->getConfig()->refillbalance_view , array(
            'config'                =>  $this->getConfig(),     // Настройки модуля
            'fields'                =>  $newFields,  // Поля для формы оплаты
            'confirmation'          =>  Kohana::message('webmoney', 'confirmation'),    // Информация для пользователя (option)
           )
        )->render();          
    }    
}
