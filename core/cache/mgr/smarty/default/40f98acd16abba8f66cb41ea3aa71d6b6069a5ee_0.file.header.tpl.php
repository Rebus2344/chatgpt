<?php
/* Smarty version 4.5.5, created on 2026-02-09 17:39:21
  from '/var/www/u3409091/data/www/mircranov.ru/manager/templates/default/header.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.5',
  'unifunc' => 'content_6989f1995e2c59_53939158',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '40f98acd16abba8f66cb41ea3aa71d6b6069a5ee' => 
    array (
      0 => '/var/www/u3409091/data/www/mircranov.ru/manager/templates/default/header.tpl',
      1 => 1770645912,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6989f1995e2c59_53939158 (Smarty_Internal_Template $_smarty_tpl) {
?><!doctype html>
<html dir="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_direction'];?>
" lang="<?php echo $_smarty_tpl->tpl_vars['_config']->value['cultureKey'];?>
" xml:lang="<?php echo $_smarty_tpl->tpl_vars['_config']->value['cultureKey'];?>
">
<head>
<title><?php if ($_smarty_tpl->tpl_vars['_pagetitle']->value) {
echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['_pagetitle']->value, ENT_QUOTES, 'UTF-8', true);?>
 | <?php }
echo htmlspecialchars((string)preg_replace('!<[^>]*?>!', ' ', (string) $_smarty_tpl->tpl_vars['_config']->value['site_name']), ENT_QUOTES, 'UTF-8', true);?>
</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $_smarty_tpl->tpl_vars['_config']->value['modx_charset'];?>
" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="google" content="notranslate" />

<?php if ($_smarty_tpl->tpl_vars['_config']->value['manager_favicon_url']) {?><link rel="shortcut icon" href="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_favicon_url'];?>
" /><?php }?>

<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/ext3/resources/css/ext-all-notheme-min.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['indexCss']->value;?>
?v=<?php echo $_smarty_tpl->tpl_vars['versionToken']->value;?>
" />

<?php if ((isset($_smarty_tpl->tpl_vars['_config']->value['ext_debug'])) && $_smarty_tpl->tpl_vars['_config']->value['ext_debug']) {
echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/ext3/adapter/ext/ext-base-debug.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/ext3/ext-all-debug.js"><?php echo '</script'; ?>
>
<?php } else {
echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/ext3/adapter/ext/ext-base.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/ext3/ext-all.js"><?php echo '</script'; ?>
>
<?php }
echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/modext/core/modx.js?mv=<?php echo $_smarty_tpl->tpl_vars['versionToken']->value;?>
"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
assets/lib/popper.min.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['connectors_url'];?>
lang.js.php?ctx=mgr&topic=topmenu,file,resource,<?php echo $_smarty_tpl->tpl_vars['_lang_topics']->value;?>
&action=<?php echo htmlspecialchars((($tmp = $_GET['a'] ?? null)===null||$tmp==='' ? '' ?? null : $tmp));?>
"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['connectors_url'];?>
modx.config.js.php?action=<?php echo htmlspecialchars((($tmp = $_GET['a'] ?? null)===null||$tmp==='' ? '' ?? null : $tmp));
if ($_smarty_tpl->tpl_vars['_ctx']->value) {?>&wctx=<?php echo $_smarty_tpl->tpl_vars['_ctx']->value;
}?>&HTTP_MODAUTH=<?php echo htmlspecialchars((($tmp = $_smarty_tpl->tpl_vars['_authToken']->value ?? null)===null||$tmp==='' ? '' ?? null : $tmp));?>
"><?php echo '</script'; ?>
>

<?php echo '<script'; ?>
>
    const tvPanelOverrides = [];
<?php echo '</script'; ?>
>

<?php echo $_smarty_tpl->tpl_vars['maincssjs']->value;?>

<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['cssjs']->value, 'scr');
$_smarty_tpl->tpl_vars['scr']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['scr']->value) {
$_smarty_tpl->tpl_vars['scr']->do_else = false;
echo $_smarty_tpl->tpl_vars['scr']->value;?>

<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>

<?php echo '<script'; ?>
>
    MODx.config.search_enabled = <?php echo $_smarty_tpl->tpl_vars['_search']->value;?>
;
    if (!Ext.isEmpty(tvPanelOverrides)) {
        let fn = {},
            vd = {},
            ld = {}
            ;
        tvPanelOverrides.forEach(obj => {
            if (obj.hasOwnProperty('fn')) {
                fn = Object.assign(fn, obj.fn);
            }
            if (obj.hasOwnProperty('validatorDefs')) {
                vd = Object.assign(vd, obj.validatorDefs);
            }
            if (obj.hasOwnProperty('listenerDefs')) {
                ld = Object.assign(ld, obj.listenerDefs);
            }
        });
        Ext.override(MODx.panel.TV, {
            initComponent: function() {
                tvPanelOverrides.forEach(obj => {
                    if (obj.hasOwnProperty('initComponent')) {
                        if (obj.initComponent.hasOwnProperty('sharedComponentOverrides')) {
                            Ext.applyIf(this.sharedComponentOverrides, obj.initComponent.sharedComponentOverrides);
                        }
                        if (obj.initComponent.hasOwnProperty('validatorRefMap')) {
                            Ext.applyIf(this.validatorRefMap, obj.initComponent.validatorRefMap);
                        }
                        if (obj.initComponent.hasOwnProperty('addNewLoaderType')) {
                            this.addNewLoaderType(...obj.initComponent.addNewLoaderType);
                        }
                    }
                });
                this.validatorCustomDefs = vd;
                this.listenerCustomDefs = ld;

                MODx.panel.TV.superclass.initComponent.call(this);
            },
            fn
        });
    }
<?php echo '</script'; ?>
>
</head>
<body id="modx-body-tag">

<section aria-label="skip links">
    <ul class="skiplinks">
        <li><a href="#modx-home-dashboard">skip to main navigation</a></li>
        <li><a href="#modx-leftbar">skip to sidebar</a></li>
        <li><a href="#modx-content">skip to main content</a></li>
    </ul>
</section>

<div id="modx-browser"></div>
<div id="modx-container">
    <section id="modx-header" aria-label="Navigation">
        <div id="modx-navbar">
            <ul id="modx-headnav">
                <li id="modx-home-dashboard">
                    <a href="?" title="<?php echo htmlspecialchars((string)preg_replace('!<[^>]*?>!', ' ', (string) $_smarty_tpl->tpl_vars['_config']->value['site_name']), ENT_QUOTES, 'UTF-8', true);?>
">
                        <img src="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
templates/<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_theme'];?>
/images/modx-icon-color.svg" title="<?php echo htmlspecialchars((string)preg_replace('!<[^>]*?>!', ' ', (string) $_smarty_tpl->tpl_vars['_config']->value['site_name']), ENT_QUOTES, 'UTF-8', true);?>
">
                    </a>
                </li>
                <li id="modx-site-info">
                    <div class="info-item full_appname"><?php echo htmlspecialchars((string)preg_replace('!<[^>]*?>!', ' ', (string) $_smarty_tpl->tpl_vars['_version']->value['full_version']), ENT_QUOTES, 'UTF-8', true);?>
</div>
                </li>
                <li id="modx-leftbar-trigger">
                    <a href="javascript:;">
                        <i class="icon"></i>
                    </a>
                </li>
                <?php if ($_smarty_tpl->tpl_vars['_search']->value) {?>
                    <li id="modx-manager-search-icon" class="top">
                        <a href="javascript:;" title="<?php echo $_smarty_tpl->tpl_vars['_lang']->value['search'];?>
" onclick="setTimeout(function(){ Ext.getCmp('modx-uberbar').selectText() },50)">
                            <i class="icon icon-search"></i>
                        </a>
                    </li>
                <?php }?>
            </ul>
            <?php if ($_smarty_tpl->tpl_vars['_search']->value) {?>
                <div class="modx-subnav" id="modx-manager-search-icon-submenu">
                    <div class="modx-subnav-arrow"></div>
                    <div id="modx-manager-search" role="search"></div>
                </div>
            <?php }?>
            <ul id="modx-topnav">
                <?php $_template = new Smarty_Internal_Template('eval:'.$_smarty_tpl->tpl_vars['navb']->value, $_smarty_tpl->smarty, $_smarty_tpl);echo $_template->fetch(); ?>
            </ul>
            <?php $_template = new Smarty_Internal_Template('eval:'.$_smarty_tpl->tpl_vars['navb_submenus']->value, $_smarty_tpl->smarty, $_smarty_tpl);echo $_template->fetch(); ?>
            <ul id="modx-user-menu">
                                <?php $_template = new Smarty_Internal_Template('eval:'.$_smarty_tpl->tpl_vars['userNav']->value, $_smarty_tpl->smarty, $_smarty_tpl);echo $_template->fetch(); ?>
            </ul>
            <?php $_template = new Smarty_Internal_Template('eval:'.$_smarty_tpl->tpl_vars['userNav_submenus']->value, $_smarty_tpl->smarty, $_smarty_tpl);echo $_template->fetch(); ?>
        </div>
    </section>
        <div id="modx-leftbar"></div>
    <div id="modx-action-buttons-container"></div>
    <div id="modx-content">
        <div id="modx-panel-holder"></div>
<?php }
}
