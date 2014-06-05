<!-- Страница проверки платежа с кошелька -->
<fieldset class="col-md-12">
    <div class="alert alert-info">
        <strong><?php echo $checkoutinfo2; ?></strong>
        <span class="clearfix"></span>
        <?php echo $confirmation2; ?>
    </div>
    <div id="result" class="alert none"></div>
    <div class="btn-group col-md-offset-2">
    <?php
    foreach($fields as $v)
    {
        echo Form::input($v['name'], $v['value'], array(
            'type'      =>  $v['type'],
            'required'  =>  $v['required'],
            'class'     =>  $v['class'],
            'onclick'   =>  $v['onclick'],
            )
        );        
    }
    ?> 
    </div>
</fieldset>
<!-- Страница проверки платежа с кошелька -->
<script type="text/javascript">
function request(strURL, number)
{
    var xmlHttpReq = false;
    var self = this;
    if (window.XMLHttpRequest)
    {
        // Код для IE7+, Firefox, Chrome, Opera, Safari
        self.xmlHttpReq = new XMLHttpRequest();
    }
    else if(window.ActiveXObject)
    {
        // Код для IE6, IE5
        self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
    }
    // Открываю URL
    self.xmlHttpReq.open('POST', strURL, true);
    
    // Даю понять серверу что идет Ajax запрос
    self.xmlHttpReq.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    self.xmlHttpReq.onreadystatechange = function() 
    {
        if(self.xmlHttpReq.readyState == 4) 
        {
            // Если все ок, получаю тут ответ от PHP и вывожу на страницу
            var elem    =   document.getElementById("result");
            elem.classList.remove('none');
            elem.innerHTML = self.xmlHttpReq.responseText;
            elem.classList.add('alert-warning');
        }
    }
    self.xmlHttpReq.send();
}
</script>
