<?php
/* Smarty version 4.5.5, created on 2026-02-09 17:38:36
  from '/var/www/u3409091/data/www/mircranov.ru/setup/templates/footer.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.5',
  'unifunc' => 'content_6989f16c1b6922_25541490',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f119b8799872c05c21a79ac95d06faeeec2f324d' => 
    array (
      0 => '/var/www/u3409091/data/www/mircranov.ru/setup/templates/footer.tpl',
      1 => 1770645905,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6989f16c1b6922_25541490 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/var/www/u3409091/data/www/mircranov.ru/core/vendor/smarty/smarty/libs/plugins/modifier.date_format.php','function'=>'smarty_modifier_date_format',),1=>array('file'=>'/var/www/u3409091/data/www/mircranov.ru/core/vendor/smarty/smarty/libs/plugins/modifier.replace.php','function'=>'smarty_modifier_replace',),));
?>
            </div>
        </div>
    </div>

    <!-- start footer -->
    <footer>
        <div class="wrapper">
            <div class="copyrite">
                <p><?php ob_start();
echo smarty_modifier_date_format(time(),"%Y");
$_prefixVariable1 = ob_get_clean();
echo smarty_modifier_replace($_smarty_tpl->tpl_vars['_lang']->value['modx_footer1'],'[[+current_year]]',$_prefixVariable1);?>
</p>
            </div>
            <div class="copyrite_info">
                <p><?php echo $_smarty_tpl->tpl_vars['_lang']->value['modx_footer2'];?>
</p>
            </div>
        </div>
    </footer>
    <!-- end footer -->
</body>

</html>
<?php }
}
