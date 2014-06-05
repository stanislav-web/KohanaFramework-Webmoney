<!-- Страница пополнения баланса -->
<?php echo $confirmation; ?>
<fieldset>
    <?php
    echo Form::open($config->url, array(
            'id'        =>  'pay',
            'method'    =>  'POST',
            'name'      =>  'pay',
        )
    );
    foreach($fields as $v)
    {
        echo Form::input($v['name'], $v['value'], array(
            'type'          =>  $v['type'],
            'required'      =>  $v['required'],
            'pattern'       =>  (isset($v['pattern'])) ? $v['pattern'] : '',
            'placeholder'   =>  (isset($v['placeholder'])) ? $v['placeholder'] : '',
            'onkeyup'       =>  (isset($v['onkeyup'])) ? $v['onkeyup'] : '',
            )
        );        
    }
    ?> 
    <span class="clearfix"></span>
    <?php echo Form::close(); ?> 
</fieldset>
<script type="text/javascript">
function numericFilter(txb) {
   txb.value = txb.value.replace(/[^\0-9.,]/ig, "");
}    
</script>
<!-- Страница пополнения баланса -->