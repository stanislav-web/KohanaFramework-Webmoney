<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * Controller_Webmoney
 * Контроллер запросов для WebMoney  Транзакций
 * @package Kohana Framework 3.3.1
 * @since PHP >=5.3.xx
 * @author Stanislav WEB (stanisov@gmail.com)
 */
class Controller_Webmoney extends Controller_Template
{
    /**
     * $template Указание базового шаблона
     * который будут наследовать шаблоны
     * @access public
     * @var string
     */
    public $template	=   'user';
    
    /**
     * $_wm
     * @access private
     * @var object 
     */
    private $_wm = null;
    
    /**
     * before() Конструктор
     * @access public
     * @return content
     */
    public function before()
    {
        parent::before();
        // Объявляю объект WM
        $this->_wm = new WebmoneyPayment();
    }    
    
    /**
     * action_fail() Ошибочный платеж
     * @access public
     * @return content
     */
    public function action_fail() 
    {
        $this->template->title      =   Kohana::message('webmoney', 'fail');
        $this->template->content    =  View::factory($this->_wm->getConfig()->fail, array(
            'title' =>  $this->template->title,
        ));
        $this->template->footer      =   '';
    }     
    
    /**
     * action_result() Завершение транзакций
     * @access public
     * @return content
     */
    public function action_result() 
    {
        $this->template->title      =   Kohana::message('webmoney', 'result');
        $this->template->content    =  View::factory($this->_wm->getConfig()->result, array(
            'title' =>  $this->template->title,
        ));
        $this->template->footer      =   '';
    }  
    
    
    /**
     * action_success() Успешный платеж
     * @access public
     * @return content
     */
    public function action_success() 
    {
        $this->template->title      =   Kohana::message('webmoney', 'success');
        $this->template->content    =   View::factory($this->_wm->getConfig()->success, array(
            'title' =>  $this->template->title,
        )); 
        $this->template->footer      =   '';
    }    
    
    /**
     * action_refil() Пополнение баланса
     * @access public
     * @return content
     */
    public function action_refil()
    {
        $user = Auth::instance()->get_user();

        $wm = new WmHelper($user);
        $refilForm = $wm->refillBalanceMerchantForm(
                array(
                    'LMI_PAYMENT_AMOUNT' => array(
                        'type' => 'text',
                        'name' => 'LMI_PAYMENT_AMOUNT',
                        'value' => '',
                        'pattern' => '[0-9]+([\.|,][0-9]+)?',
                        'required' => true,
                        'placeholder' => UTF8::str_ireplace(':currency', strtoupper($wm->getConfig()->currency), Kohana::message('webmoney', 'amount_placeholder')),
                    ),
                    'LMI_PAYMENT_DESC_BASE64' => array(
                        'type' => 'hidden',
                        'name' => 'LMI_PAYMENT_DESC_BASE64',
                        'value' => base64_encode($user->id),
                        'required' => true,
                    ),
                    'submit' => array(
                        'type' => 'submit',
                        'name' => 'submit',
                        'value' => 'Оплатить',
                        'required' => true,
                    )
                )
        );
        $this->template->content = $refilForm; 
    }
    
    
    /**
     * action_check() Проверка кошелька на наличие платежей
     * @access public
     * @return content
     */
    public function action_check() 
    {
        // Проверяю счет для оплаты
        $Invoice = ORM::factory('Invoice', $this->request->param('id'));

        if($Invoice->loaded()) 
        {
            // Если еще не оплачен
            if($Invoice->status_id == Model_Invoice_Status::NEWBIE)
            {
                try {
                    // Разрешаю оплачивать через WM

                    // Авторизовую, проверку выполнит Сервер WM
                    $this->_wm->signKeeperClassicAuth();

                    // Делаю проверку платежей по интерфейсу X3
                    $history = $this->_wm->getOperationHistory(date('Ymd H:i:s', strtotime('+1 day')));
                    foreach($history['operations'] as $operation) 
                     {
                        if(trim($operation['desc']) == $Invoice->status_id) 
                        {
                            // Отлично, оплата по коду найдена, проверяю точную сумму
                            if($Invoice->amount <= $operation['amount']) 
                            {
                                // можно теперь сделать что оплата прошла
                                $Invoice->status_id = Model_Invoice_Status::PAYED;
                                $Invoice->pay_type_id = Model_Pay_Type::WEBMONEY;
                                $Invoice->update();
                                echo Kohana::message('webmoney', 'success');
                                break;
                            } 
                            else WebmoneyMessenger::setMessage('ERROR_AMOUNT_NOT_ENOUGHT');
                        } else WebmoneyMessenger::setMessage('ERROR_PAY_ISNT_RECEIVED');
                    }
                } 
                catch (Exception $e) 
                {
                    echo $e->getMessage();
                }
            }
            elseif($Invoice->status_id == Model_Invoice_Status::CANCEL) echo Kohana::message('webmoney', 'canceled_already');
            elseif($Invoice->status_id == Model_Invoice_Status::PAYED) echo Kohana::message('webmoney', 'success');
        }
    }
    
    /**
     * action_cancel() Отмена оплаты
     * @access public
     * @return content
     */
    public function action_cancel() 
    {

        // Проверяю счет для отмены оплаты
        $Invoice = ORM::factory('Invoice', $this->request->param('id'));

        if($Invoice->loaded()) 
        {
            // Если еще не оплачен
            if($Invoice->status_id == Model_Invoice_Status::NEWBIE)
            {
                try {
                    // Разрешаю отмену
                    // подключаю WebMoney обработчик

                    // можно теперь сделать что оплата прошла
                    $Invoice->status_id = Model_Invoice_Status::CANCEL;
                    $Invoice->pay_type_id = Model_Pay_Type::WEBMONEY;
                    $Invoice->update();
                    echo Kohana::message('webmoney', 'canceled');
                } 
                catch (Exception $e) 
                {
                    echo $e->getMessage();
                }
            }
            elseif($Invoice->status_id == Model_Invoice_Status::CANCEL) echo Kohana::message('webmoney', 'canceled');
            elseif($Invoice->status_id == Model_Invoice_Status::PAYED) echo Kohana::message('webmoney', 'success');
        }
    }    
}
