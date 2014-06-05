<!-- Страница оплаты услуги -->
<fieldset>
    <div class="alert alert-info">
        <strong><?php echo $checkoutinfo; ?></strong>
        <span class="clearfix"></span>
        <?php echo $confirmation; ?>
    </div>
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
            'type'      =>  $v['type'],
            'required'  =>  $v['required'],
            'pattern'   =>  (isset($v['pattern'])) ? $v['pattern'] : '',
            )
        );        
    }
    ?> 
    <span class="clearfix"></span>
    <?php echo Form::close(); ?> 
</fieldset>
<!-- Страница оплаты услуги -->