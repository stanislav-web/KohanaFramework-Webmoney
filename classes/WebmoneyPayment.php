<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Class WebmoneyPayment API оплаты через WebMoney Merchant и проверки статусов
 * @package Kohana Framework 3.3.1
 * @since PHP >=5.3.xx
 * @author Stanislav WEB (stanisov@gmail.com)
 */
class WebmoneyPayment implements WebMerchantInterface {

    /**
     * $_config Конфигурации WM интерфейса
     * Будут выгружены из настроек методом Kohana_Config_File_Reader
     * @access private
     * @var string
     */
    private $_config     =   null;  
    
    /**
     * $_encoding Кодировка соединений
     * @access protected
     * @var string
     */
    protected $_encoding     =   'UTF-8';     
    
    /**
     * $_order Данные о заказе, накладной или чеке
     * @access private
     * @var object
     */
    private $_order     =   false;     
 
    /**
     * $_payfields Поля для формы оплаты
     * @access private
     * @var array
     */
    private $_payfields     =   array(); 
    
    /**
     * $_secrethash Контрольная сумма (HASH) ответа от сервера WM Merchant
     * @access private
     * @var string
     */
    private $_secrethash     =   array(); 
    
    /**
     * $_wmxiauth Флаг авторизации с сервером WebMoney (Keeper/Light)
     * @access public
     * @var boolean
     */
    public $_wmxiauth     =   false;      
    
    
    /**
     * __construct($order = null) Конструктор (инициализация настроек интерфейса)
     * @param object $order Допускается объект счета или заказа или пользователя
     * @access public
     */
    public function __construct($order = null)
    {
        // Всегда дропаю авторизацию при инициализации
        $this->_wmxiauth    =   false;
        
        $this->setConfig(new Kohana_Config_File_Reader());
        if(!$this->_order) $this->_order = $order;
        
        // Логирую запуск
        if($this->_config->debug) $this->__setLogMessage(array(
            'code'      =>  'STARTUP',
            'message'   =>  'Объект WM успешно запущен'
        ));        
    }
    
    /**
     * setConfig(Kohana_Config_File_Reader $config, $group = 'webmoney') Установка настроек интерфейса
     * @param Kohana_Config_File_Reader $config объект конфигуратора
     * @param string $group файл с выборкой настроек
     * @access public
     * @return null
     */
    public function setConfig(Kohana_Config_File_Reader $config, $group = 'webmoney') 
    {
        $this->_config  =   (object)$config->load($group);
    }
    
    /**
     * getConfig() Выборка настроек интерфейса
     * @access public
     * @return object Kohana_Config_File_Reader
     */
    public function getConfig() 
    {
        if(empty($this->_config)) WebmoneyMessenger::setMessage('ERROR_EMPTY_CONGIF_FILE');
        return $this->_config;
    }
    
    /**
     * setPayFields() Установка полей для формы оплаты
     * @access public
     * @return array
     */
    public function setPayFields()
    {
        $this->_payfields  =   array(
            
            // Сумма заказа
            'LMI_PAYMENT_AMOUNT'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_PAYMENT_AMOUNT',
                'value' =>  (isset($this->_config->amount)) ? $this->_order->{$this->_config->amount} : '',
                'pattern'   =>  '[0-9]+([\.|,][0-9]+)?',
                'required'  =>  true,
                'onkeyup'   =>  false,
            ),
            
            // Описание заказа
            'LMI_PAYMENT_DESC_BASE64'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_PAYMENT_DESC_BASE64',
                'value' =>  (isset($this->_config->amount) && isset($this->_order->id)) ? base64_encode('№'.$this->_order->id.' '.$this->_order->{$this->_config->amount}) : '',
                'required'  =>  true,
            ),
            
            //  Номер заказа
            'LMI_PAYMENT_NO'      =>  array(                 
                'type'  =>  'hidden',
                'name'  =>  'LMI_PAYMENT_NO',
                'value' =>  $this->_order->id,
                'required'  =>  true,
            ),
            
            // Кошелек
            'LMI_PAYEE_PURSE'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_PAYEE_PURSE',
                'value' =>  $this->_config->wallet,
                'required'  =>  true,
            ),
            
            // Режим тестирования
            'LMI_SIM_MODE'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_SIM_MODE',
                'value' =>  $this->_config->debug,
                'required'  =>  true,
            ),
            
            // URL c результатом оплаты
            'LMI_RESULT_URL'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_RESULT_URL',
                'value' =>  URL::base(true).$this->_config->result,
                'required'  =>  true,
            ),       
            
            // URL успешной транзакции
            'LMI_SUCCESS_URL'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_SUCCESS_URL',
                'value' =>  URL::base(true).$this->_config->success,
                'required'  =>  true,
            ),
            // Метод передачи на успешную транзакцию
            'LMI_SUCCESS_METHOD'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_SUCCESS_METHOD',
                'value' =>  $this->_config->method,
                'required'  =>  true,
            ),            
            
            // URL выброса ошибки
            'LMI_FAIL_URL'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_FAIL_URL',
                'value' =>  URL::base(true).$this->_config->fail,
                'required'  =>  true,
            ),   
            // Метод передачи на ошибку
            'LMI_FAIL_METHOD'      =>  array(
                'type'  =>  'hidden',
                'name'  =>  'LMI_FAIL_METHOD',
                'value' =>  $this->_config->method,
                'required'  =>  true,
            ),     
        );
    }
    
    /**
     * getPayFields() Достаю поля для формы оплаты
     * @access public
     * @return array 
     */
    public function getPayFields() 
    {
        if(empty($this->_order)) WebmoneyMessenger::setMessage('ERROR_EMPTY_ORDER');
        // Строю структуру включаемых полей
        $this->setPayFields();
        return $this->_payfields;
    }
    
    /**
     * checkPayFields() Валидация полей
     * @access public
     * @todo НЕ РЕАЛИЗОВАН ИЗ ЗА ОТСУТСВИЯ ВОЗМОЖНОСТИ ПРОТЕСТИРОВАТЬ В MERCHANT
     * @return array 
     */
    public function checkPayFields(Validation $form) 
    {
        if(empty($this->_order)) WebmoneyMessenger::setMessage('ERROR_EMPTY_ORDER');
        $this->setPayFields();
        return $this->_payfields;
    }    
    
    /**
     * _getSecretHash(array $param) Установка хэшированного ключа для сравнения
     * @param array $param Параметры ответа от сервера WM Merchant
     * @access private
     * @return string HASH ключ
     */
    private function _getSecretHash(array $param)
    {
        if(empty($param)) WebmoneyMessenger::setMessage('ERROR_EMPTY_RESPONSE_PERAMS');
         // Склеиваем строку параметров
        $hash  = $param['LMI_PAYEE_PURSE'].$param['LMI_PAYMENT_AMOUNT'].$param['LMI_PAYMENT_NO'].$param['LMI_MODE'].$param['LMI_SYS_INVS_NO'].$param['LMI_SYS_TRANS_NO'].$param['LMI_SYS_TRANS_DATE'].$this->_config->secret_key.$param['LMI_PAYER_PURSE'].$param['LMI_PAYER_WM'];
        
        //Переводим эту строку в верхний регистр и шифруем её в MD5
        $this->_secrethash = strtoupper(md5($hash));
    }
    
    /**
     * proccessResult() Проверка прохождения оплаты счета
     * @access public
     * @todo НЕ РЕАЛИЗОВАН ИЗ ЗА ОТСУТСВИЯ ВОЗМОЖНОСТИ ПРОТЕСТИРОВАТЬ В MERCHANT
     * @return 
     */    
    public function proccessResult(Request $request)
    {
        // Смотрю через что прошли данные и подключаю обработчик
        if($request->post()) $params = $request->post();    // POST Processor
        else $params = $request->query();                   // GET Processor
        
        if($params['LMI_PREREQUEST'] == 1)
        {
            // это предварительный запрос
            if(!empty($params['LMI_PAYMENT_NO']) && is_numeric($params['LMI_PAYMENT_NO']))
            {
                // Создаю ключ операции и сравниваю их
                $hash = $this->_getSecretHash($params);
                if($params['LMI_HASH'] !== $hash) WebmoneyMessenger::setMessage('ERROR_WRONG_HASH');
                
                // Платеж успешно завершен!
                WebmoneyMessenger::setMessage('SUCCESS_TRANSACTION', '<b>Успешно:</b>',array(
                    'amount'    =>  $params['LMI_PAYMENT_AMOUNT'],
                    'wallet'    =>  $this->_config->wallet
                    ));
            }
            else WebmoneyMessenger::setMessage('ERROR_WRONG_PAYMENT_ID');
        }
        else WebmoneyMessenger::setMessage('ERROR_UNKNOW_RESPONSE');
    }
    
    /**
     * signKeeperClassicAuth() Инициализация с помощью резервной копии файла ключей
     * Данные для авторизации WebMoney Кошельков через WM Keeper Classic
     * @package Kohana Framework 3.3.1
     * @access public
     * @since PHP >=5.3.xx
     */    
    public function signKeeperClassicAuth()
    {
        if($this->_config->debug) define('WMXI_LOG', $this->_config->log);

	# Создаём объект класса. Передаваемые параметры:
	# - путь к сертификату, используемому для защиты от атаки с подменой ДНС
	# - кодировка, используемая на сайте. По умолчанию используется UTF-8
	$wmxi = new WMXI(realpath('../vendor/webmoney/cert/WMXI.crt'), $this->_encoding);
	# от Webmoney Keeper Classic. Передаваемые параметры:
	# - WMID - идентификатор пользователя
	# - пароль пользователя от резервной копии файла ключей
	# - путь к резервной копии файла ключей (обычно размером 164 байта)
	# - бинарное содержимое файла ключей
	# - мантисса и экспонента      
        
	# Параметры инициализации ключем Webmoney Keeper Classic

        if(!$this->_config->wmid) WebmoneyMessenger::setMessage('ERROR_NOT_DEFINE_WMID');
        
        $wmxi->Classic($this->_config->wmid, array(
            'pass' => $this->_config->keypass, 
            'file' => __DIR__.'../../vendor/webmoney/keys/'.$this->_config->wmid.'.kwm')
        );
        return $this->_wmxiauth = $wmxi;
    }
    
    /**
     * getOperationHistory($date_end, $transaction_number = 0, $number_of_transfer = 0, $account_number = 0, $account_number2 = 0)
     * Получение истории операций по кошельку
     * @param datetime $date_end Финальная дата проверки транзакций (текущая)
     * @param int $transaction_number номер операции (в системе WebMoney)
     * @param int $number_of_transfer номер перевода
     * @param int $account_number номер счета (в системе WebMoney) по которому выполнялась операция
     * @param int $account_number2 номер счета
     * @access public
     * @return xml
     */
    public function getOperationHistory($date_end, $transaction_number = 0, $number_of_transfer = 0, $account_number = 0, $account_number2 = 0)
    {
        if(!preg_match('/^\d{4}\d{2}\d{2} \d{2}:\d{2}:\d{2}$/', $date_end)) WebmoneyMessenger::setMessage('ERROR_WRONG_DATE_FORMAT');
        
    	$resXML = $this->_wmxiauth->X3(
		$this->_config->wallet,   
		$transaction_number,    
		$number_of_transfer,     
		$account_number,         
		$account_number2,        
		date('Ymd H:i:s', strtotime($this->_config->date_interval)),            
		$date_end               
	);
        if(!$resXML) WebmoneyMessenger::setMessage('ERROR_NOT_RESPONSE_FROM_XML');
        
        $xmlres = $resXML->toObject();
        $result['retval']   =   strval($xmlres->retval);
	$result['retdesc']  =   $xmlres->retdesc;
	$result['cnt']      =   count($xmlres->operations);
        if($result['cnt'] > 0) 
        {
            // Платежи уже были, начинаю поиск
            foreach($xmlres->operations->operation as $operation) 
            {
                // определяем тип операции (входящая, исходящая) и кошелёк корреспондента
		$pursesrc   =   $operation->pursesrc;
		$pursedest  =   strval($operation->pursedest);
		if($pursesrc == $this->_config->wallet) 
                {
                    // исходящий платеж
                    $type       =   'out'; 
                    $corrpurse  =   $pursedest;
		} 
                elseif($pursedest == $this->_config->wallet) 
                {
                    // входящий платеж
                    $type       =   'in'; 
                    $corrpurse  =   $pursesrc;
		}
		$result['operations'][] = 
                array(
                        'operation_id'  =>  strval($operation->attributes()->id),
                        'tranid'        =>  strval($operation->tranid),
			'wminvid'       =>  strval($operation->wminvid),
			'orderid'       =>  strval($operation->orderid),
			'type'          =>  $type,
			'corrpurse'     =>  strval($corrpurse),
			'corrwmid'      =>  strval($operation->corrwm),
			'amount'        =>  floatval($operation->amount),
			'comiss'        =>  floatval($operation->comiss),
			'rest'          =>  floatval($operation->rest),
			'protection'    =>  strval($operation->opertype),
			'desc'          =>  strval($operation->desc),
			'datecrt'       =>  strval($operation->datecrt)
                );
            }            
        }
        else  WebmoneyMessenger::setMessage('ERROR_HISTORY_DOESNT_LOADED');
	return $result;        
    }
    
    /**
     * receivePaymentNotLeavingSite($step, Request $param) Прием WM не покидая сайт
     * @param int $step Шаг проведения оплаты (1 или 2)
     * @param Request $param передаваемые POST параметры
     * @return array
     */
    public function receivePaymentNotLeavingSite($step, Request $param) 
    {
        // Стартую сессию для записи счета с первого запроса
        $Session = Session::instance();
        
	$step   =   intval($step);
	if($step == 1) 
        {
            // Первый шаг оплаты, Создаю Хэш
            $this->_secrethash = strtoupper(md5($this->_config->wmid.$param['LMI_PAYEE_PURSE'].$param['LMI_PAYMENT_NO'].$param['LMI_CLIENTNUMBER'].$param['LMI_CLIENTNUMBER_TYPE'].$this->_config->secret_key));
            
            // Создаю XML для отправки
            $xml="
                <merchant.request>
                    <wmid>{$this->_config->wmid}</wmid>
                    <lmi_payee_purse>{$param['LMI_PAYEE_PURSE']}</lmi_payee_purse>
                    <lmi_payment_no>{$param['LMI_PAYMENT_NO']}</lmi_payment_no>
                    <lmi_payment_amount>{$param['LMI_PAYMENT_AMOUNT']}</lmi_payment_amount>
                    <lmi_payment_desc>{$param['LMI_PAYMENT_DESC']}</lmi_payment_desc>
                    <lmi_clientnumber>{$param['LMI_CLIENTNUMBER']}</lmi_clientnumber>
                    <lmi_clientnumber_type>{$param['LMI_CLIENTNUMBER_TYPE']}</lmi_clientnumber_type>
                    <lmi_sms_type>{$param['LMI_SMS_TYPE']}</lmi_sms_type>
                    <secret_key></secret_key>
                    <sign></sign>
                    <md5>{$this->_secrethash}</md5>
		</merchant.request>";
            
            // Параметры для проведения запроса через CURL
            $options = array(
                    'options' => array(
                        CURLOPT_CAINFO => realpath('../vendor/webmoney/cert/WMunited.crt'),
                        CURLOPT_SSL_VERIFYPEER  =>  TRUE
                    )
                );

            // Отправляю на сервер
            $response = Request::factory($this->_config->url_xml_request)
                ->method(Request::POST)
                ->post($xml)
                ->client(new Request_Client_Curl($options))
                ->execute()
                ->body();                        
	}
        elseif($step == 2)
        {
            // Второй шаг оплаты, Создаю Хэш
            $this->_secrethash = strtoupper(md5($this->_config->wmid.$param['LMI_PAYEE_PURSE'].$Session->get('LMI_WMINVOICEID').$param['LMI_CLIENTNUMBER_CODE'].$this->_config->secret_key));
            
            // Создаю XML для отправки
            $xml="
		<merchant.request>
                    <wmid>{$this->_config->wmid}</wmid>
                    <lmi_payee_purse>{$param['LMI_PAYEE_PURSE']}</lmi_payee_purse>
                    <lmi_clientnumber_code>{$param['LMI_CLIENTNUMBER_CODE']}</lmi_clientnumber_code>
                    <lmi_wminvoiceid>{$Session->get('LMI_WMINVOICEID')}</lmi_wminvoiceid> 
                    <secret_key></secret_key>
                    <sign></sign>
                    <md5>{$this->_secrethash}</md5>
		</merchant.request>";
                    
            // Параметры для проведения запроса через CURL
            $options = array(
                    'options' => array(
                        CURLOPT_CAINFO => realpath('../vendor/webmoney/cert/WMunited.crt'),
                        CURLOPT_SSL_VERIFYPEER  =>  TRUE
                    )
                );

            // Отправляю на сервер
            $response = Request::factory($this->_config->url_xml_confirm)
                ->method(Request::POST)
                ->post($xml)
                ->client(new Request_Client_Curl($options))
                ->execute()
                ->body();                    
	}
        
        // Делаю парсиг ответа XML
	$xmlres = simplexml_load_string($response);
	if(!$xmlres) WebmoneyMessenger::setMessage('ERROR_NOT_RESPONSE_FROM_XML');
        
        // Формирую ответ на выдачу
        $result = array(
            'retval'        =>  strval($xmlres->retval),
            'retdesc'       =>  strval($xmlres->retdesc),
            'userdesc'      =>  strval($xmlres->userdesc),
            'wminvoiceid'   =>  strval($xmlres->operation->attributes()->wminvoiceid),
            'realsmstype'   =>  $xmlres->operation->realsmstype,
            'wmtransid'     =>  strval($xmlres->operation->attributes()->wmtransid),
            'operdate'      =>  strval($xmlres->operation->operdate),
            'pursefrom'     =>  strval($xmlres->operation->pursefrom),
        );        
	return $result;
    }    
    
    /**
     * __setLogMessage(array $arrMsg) Логирование операций WM
     * @param array $arrMsg
     * @access private
     * @return null 
     */
    private function __setLogMessage(array $arrMsg)
    {
        foreach(array('message', 'code') as $k) 
        {
            if(!isset($arrMsg[$k])) WebmoneyMessenger::setMessage('ERROR_UNDEFINED_KEY_FOR_LOG');
        }        
        $arrMsg = array_merge(array('date' => date('d.m.Y H:i:s')), $arrMsg);
        file_put_contents($this->_config->log,  implode("\t", $arrMsg));
    }    
}
?>
