<?php
/* Smarty version 4.5.5, created on 2026-02-09 17:39:21
  from '/var/www/u3409091/data/www/mircranov.ru/manager/templates/default/dashboard/recentlyeditedresources.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.5',
  'unifunc' => 'content_6989f199414f90_70280603',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd434a023f7a828b48992e39bbd26a4e0d4a4c327' => 
    array (
      0 => '/var/www/u3409091/data/www/mircranov.ru/manager/templates/default/dashboard/recentlyeditedresources.tpl',
      1 => 1770645912,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6989f199414f90_70280603 (Smarty_Internal_Template $_smarty_tpl) {
?><p><?php echo $_smarty_tpl->tpl_vars['_lang']->value['activity_message'];?>
</p>
<br/>
<div id="modx-grid-user-recent-resource">
    <?php if ($_smarty_tpl->tpl_vars['data']->value['total'] > 0) {?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                <tr>
                    <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['id'];?>
</th>
                    <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['pagetitle'];?>
</th>
                    <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['editedon'];?>
</th>
                    <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['user'];?>
</th>
                    <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['actions'];?>
</th>
                </tr>
                </thead>
                <tbody>
                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['data']->value['results'], 'record');
$_smarty_tpl->tpl_vars['record']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['record']->value) {
$_smarty_tpl->tpl_vars['record']->do_else = false;
?>
                    <tr>
                        <td><?php echo $_smarty_tpl->tpl_vars['record']->value['id'];?>
</td>
                        <td class="resource">
                            <div class="<?php if (!$_smarty_tpl->tpl_vars['record']->value['published']) {?>unpublished<?php } elseif ($_smarty_tpl->tpl_vars['record']->value['deleted']) {?>deleted<?php } else { ?>title<?php }?>"><?php echo $_smarty_tpl->tpl_vars['record']->value['pagetitle'];?>
</div>
                            <?php if (!empty($_smarty_tpl->tpl_vars['record']->value['action'])) {?>
                                <div>
                                    <small><?php echo $_smarty_tpl->tpl_vars['record']->value['action'];?>
</small>
                                </div>
                            <?php }?>
                        </td>
                        <td class="occurred">
                            <?php if ($_smarty_tpl->tpl_vars['record']->value['editedon']) {?>
                                <div class="occurred-date"><?php echo $_smarty_tpl->tpl_vars['record']->value['editedon_date'];?>
</div>
                                <div class="occurred-time"><?php echo $_smarty_tpl->tpl_vars['record']->value['editedon_time'];?>
</div>
                            <?php } elseif ($_smarty_tpl->tpl_vars['record']->value['createdon']) {?>
                                <div class="occurred-date"><?php echo $_smarty_tpl->tpl_vars['record']->value['createdon_date'];?>
</div>
                                <div class="occurred-time"><?php echo $_smarty_tpl->tpl_vars['record']->value['createdon_time'];?>
</div>
                            <?php }?>
                        </td>
                        <td class="user-with-avatar">
                            <div class="user-avatar">
                                <?php if ($_smarty_tpl->tpl_vars['record']->value['photo']) {?>
                                    <img src="<?php echo $_smarty_tpl->tpl_vars['record']->value['photo'];?>
">
                                <?php } else { ?>
                                    <i class="icon icon-user icon-2x"></i>
                                <?php }?>
                            </div>
                            <div class="user-data">
                                <div class="user-name"><?php echo $_smarty_tpl->tpl_vars['record']->value['fullname'];?>
</div>
                                <div class="user-group"><?php echo $_smarty_tpl->tpl_vars['record']->value['group'];?>
</div>
                            </div>
                        </td>
                        <td class="widget-actions">
                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['record']->value['menu'], 'menu');
$_smarty_tpl->tpl_vars['menu']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['menu']->value) {
$_smarty_tpl->tpl_vars['menu']->do_else = false;
?>
                                <?php if (empty($_smarty_tpl->tpl_vars['menu']->value['text']) || $_smarty_tpl->tpl_vars['menu']->value['text'] == '-' || !(isset($_smarty_tpl->tpl_vars['menu']->value['params']))) {?>
                                    <?php continue 1;?>
                                <?php }?>

                                <?php if ($_smarty_tpl->tpl_vars['menu']->value['params']['type'] == 'view') {?>
                                    <?php $_smarty_tpl->_assignInScope('icon', 'icon icon-eye');?>
                                <?php } elseif ($_smarty_tpl->tpl_vars['menu']->value['params']['type'] == 'edit') {?>
                                    <?php $_smarty_tpl->_assignInScope('icon', 'icon icon-edit');?>
                                <?php } elseif ($_smarty_tpl->tpl_vars['menu']->value['params']['type'] == 'open') {?>
                                    <?php $_smarty_tpl->_assignInScope('icon', 'icon icon-external-link');?>
                                <?php } else { ?>
                                    <?php $_smarty_tpl->_assignInScope('icon', null);?>
                                <?php }?>

                                <?php if (!empty($_smarty_tpl->tpl_vars['menu']->value['params']['a']) && !empty($_smarty_tpl->tpl_vars['menu']->value['params']['id'])) {?>
                                    <a href="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
?a=<?php echo $_smarty_tpl->tpl_vars['menu']->value['params']['a'];?>
&id=<?php echo $_smarty_tpl->tpl_vars['menu']->value['params']['id'];?>
"
                                       title="<?php echo $_smarty_tpl->tpl_vars['menu']->value['text'];?>
">
                                        <?php if ($_smarty_tpl->tpl_vars['icon']->value) {?><i class="<?php echo $_smarty_tpl->tpl_vars['icon']->value;?>
"></i><?php } else {
echo $_smarty_tpl->tpl_vars['menu']->value['text'];
}?>
                                    </a>
                                <?php } elseif ($_smarty_tpl->tpl_vars['menu']->value['params']['url']) {?>
                                    <a href="<?php echo $_smarty_tpl->tpl_vars['menu']->value['params']['url'];?>
" title="<?php echo $_smarty_tpl->tpl_vars['menu']->value['text'];?>
" target="_blank">
                                        <?php if ($_smarty_tpl->tpl_vars['icon']->value) {?><i class="<?php echo $_smarty_tpl->tpl_vars['icon']->value;?>
"></i><?php } else {
echo $_smarty_tpl->tpl_vars['menu']->value['text'];
}?>
                                    </a>
                                <?php }?>
                            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                        </td>
                    </tr>
                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="no-results"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['w_no_data'];?>
</div>
    <?php }?>
    <?php if ($_smarty_tpl->tpl_vars['can_view_logs']->value) {?>
        <div class="widget-footer">
            <a href="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
?a=system/logs"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['w_view_all'];?>
 &rarr;</a>
        </div>
    <?php }?>
</div>
<?php }
}
