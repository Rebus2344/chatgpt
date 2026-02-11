<?php  return array (
  0 => 
  array (
    'text' => 'Пользователь',
    'parent' => 'usernav',
    'action' => '',
    'description' => '',
    'icon' => '<span id="user-avatar" title="{$username}">{$userImage}</span> <span id="user-username">{$username}</span>',
    'menuindex' => 0,
    'params' => '',
    'handler' => '',
    'permissions' => 'menu_user',
    'namespace' => 'core',
    'id' => 'user',
    'children' => 
    array (
      0 => 
      array (
        'text' => '{$username}',
        'parent' => 'user',
        'action' => 'security/profile',
        'description' => 'Обновить ваш профиль',
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => 'change_profile',
        'namespace' => 'core',
        'id' => '{$username}',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      1 => 
      array (
        'text' => 'Сообщения',
        'parent' => 'user',
        'action' => 'security/message',
        'description' => 'Просмотр ваших сообщений и отправка новых сообщений пользователям',
        'icon' => '',
        'menuindex' => 1,
        'params' => '',
        'handler' => '',
        'permissions' => 'messages',
        'namespace' => 'core',
        'id' => 'messages',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      2 => 
      array (
        'text' => 'Выйти',
        'parent' => 'user',
        'action' => 'security/logout',
        'description' => 'Выйти из системы управления',
        'icon' => '',
        'menuindex' => 2,
        'params' => '',
        'handler' => 'MODx.logout(); return false;',
        'permissions' => 'logout',
        'namespace' => 'core',
        'id' => 'logout',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
    ),
    'controller' => '',
  ),
  1 => 
  array (
    'text' => 'Доступ',
    'parent' => 'usernav',
    'action' => '',
    'description' => '',
    'icon' => '<i class="icon-user-lock icon"></i>',
    'menuindex' => 1,
    'params' => '',
    'handler' => '',
    'permissions' => 'access_permissions',
    'namespace' => 'core',
    'id' => 'access',
    'children' => 
    array (
      0 => 
      array (
        'text' => 'Пользователи',
        'parent' => 'access',
        'action' => 'security/user',
        'description' => 'Добавление, обновление, и назначение прав пользователям',
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => 'view_user',
        'namespace' => 'core',
        'id' => 'users',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      1 => 
      array (
        'text' => 'Группы ресурсов',
        'parent' => 'access',
        'action' => 'security/resourcegroup',
        'description' => 'Управление принадлежностью ресурсов к группам ресурсов',
        'icon' => '',
        'menuindex' => 1,
        'params' => '',
        'handler' => '',
        'permissions' => 'access_permissions',
        'namespace' => 'core',
        'id' => 'resource_groups',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      2 => 
      array (
        'text' => 'Контроль доступа',
        'parent' => 'access',
        'action' => 'security/permission',
        'description' => 'Управление привилегиями через группы, роли и политики доступа',
        'icon' => '',
        'menuindex' => 2,
        'params' => '',
        'handler' => '',
        'permissions' => 'access_permissions',
        'namespace' => 'core',
        'id' => 'acls',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      3 => 
      array (
        'text' => 'Перезагрузить права доступа',
        'parent' => 'access',
        'action' => '',
        'description' => 'Перезагрузить все права доступа и очистить кэш',
        'icon' => '',
        'menuindex' => 3,
        'params' => '',
        'handler' => 'MODx.msg.confirm({
                            title: _(\'flush_access\')
                            ,text: _(\'flush_access_confirm\')
                            ,url: MODx.config.connector_url
                            ,params: {
                                action: \'security/access/flush\'
                            }
                            ,listeners: {
                                \'success\': {fn:function() { location.href = \'./\'; },scope:this},
                                \'failure\': {fn:function(response) { Ext.MessageBox.alert(\'failure\', response.responseText); },scope:this},
                            }
                        });',
        'permissions' => 'access_permissions',
        'namespace' => 'core',
        'id' => 'flush_access',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      4 => 
      array (
        'text' => 'Завершить все сеансы',
        'parent' => 'access',
        'action' => '',
        'description' => 'Завершить все сеансы работы пользователей и произвести принудительный выход из системы всех пользователей',
        'icon' => '',
        'menuindex' => 4,
        'params' => '',
        'handler' => 'MODx.msg.confirm({
                            title: _(\'flush_sessions\')
                            ,text: _(\'flush_sessions_confirm\')
                            ,url: MODx.config.connector_url
                            ,params: {
                                action: \'security/flush\'
                            }
                            ,listeners: {
                                \'success\': {fn:function() { location.href = \'./\'; },scope:this}
                            }
                        });',
        'permissions' => 'flush_sessions',
        'namespace' => 'core',
        'id' => 'flush_sessions',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
    ),
    'controller' => '',
  ),
  2 => 
  array (
    'text' => 'Админ',
    'parent' => 'usernav',
    'action' => '',
    'description' => '',
    'icon' => '<i class="icon-gear icon"></i>',
    'menuindex' => 2,
    'params' => '',
    'handler' => '',
    'permissions' => 'settings',
    'namespace' => 'core',
    'id' => 'admin',
    'children' => 
    array (
      0 => 
      array (
        'text' => 'Системные настройки',
        'parent' => 'admin',
        'action' => 'system/settings',
        'description' => 'Изменение и создание системных настроек',
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => 'settings',
        'namespace' => 'core',
        'id' => 'system_settings',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      1 => 
      array (
        'text' => 'Настройка форм',
        'parent' => 'admin',
        'action' => 'security/forms',
        'description' => 'Настройка внешнего вида системы управления MODX',
        'icon' => '',
        'menuindex' => 1,
        'params' => '',
        'handler' => '',
        'permissions' => 'customize_forms',
        'namespace' => 'core',
        'id' => 'form_customization',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      2 => 
      array (
        'text' => 'Наборы параметров',
        'parent' => 'admin',
        'action' => 'element/propertyset',
        'description' => 'Управление наборами параметров и элементами, которым эти наборы параметров назначены',
        'icon' => '',
        'menuindex' => 2,
        'params' => '',
        'handler' => '',
        'permissions' => 'property_sets',
        'namespace' => 'core',
        'id' => 'propertysets',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      3 => 
      array (
        'text' => 'Меню',
        'parent' => 'admin',
        'action' => 'system/action',
        'description' => 'Управление структурой главного меню системы управления',
        'icon' => '',
        'menuindex' => 3,
        'params' => '',
        'handler' => '',
        'permissions' => 'actions',
        'namespace' => 'core',
        'id' => 'edit_menu',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      4 => 
      array (
        'text' => 'Контексты',
        'parent' => 'admin',
        'action' => 'context',
        'description' => 'Управление контекстами сайта и их настройками',
        'icon' => '',
        'menuindex' => 4,
        'params' => '',
        'handler' => '',
        'permissions' => 'view_context',
        'namespace' => 'core',
        'id' => 'contexts',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      5 => 
      array (
        'text' => 'Панели управления',
        'parent' => 'admin',
        'action' => 'system/dashboards',
        'description' => 'Управление панелями и виджетами',
        'icon' => '',
        'menuindex' => 5,
        'params' => '',
        'handler' => '',
        'permissions' => 'dashboards',
        'namespace' => 'core',
        'id' => 'dashboards',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      6 => 
      array (
        'text' => 'Пространства имён',
        'parent' => 'admin',
        'action' => 'workspaces/namespace',
        'description' => 'Управление пространствами имён. Пространства имён служат отличительным признаком для разных пакетов, добавляемых пользователями',
        'icon' => '',
        'menuindex' => 6,
        'params' => '',
        'handler' => '',
        'permissions' => 'namespaces',
        'namespace' => 'core',
        'id' => 'namespaces',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      7 => 
      array (
        'text' => 'Управление словарями',
        'parent' => 'admin',
        'action' => 'workspaces/lexicon',
        'description' => 'Изменение языковых строк в системе управления',
        'icon' => '',
        'menuindex' => 7,
        'params' => '',
        'handler' => '',
        'permissions' => 'lexicons',
        'namespace' => 'core',
        'id' => 'lexicon_management',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      8 => 
      array (
        'text' => 'Переключить язык',
        'parent' => 'admin',
        'action' => '',
        'description' => 'Выберите подходящий язык системы управления',
        'icon' => '',
        'menuindex' => 8,
        'params' => '',
        'handler' => '',
        'permissions' => 'language',
        'namespace' => 'core',
        'id' => 'language',
        'children' => 
        array (
          'ar' => 
          array (
            'id' => 'ar',
            'text' => 'Arabic',
            'description' => 'Арабский <b>AR</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("ar"); return false;',
            'permissions' => '',
          ),
          'az' => 
          array (
            'id' => 'az',
            'text' => 'language_az',
            'description' => 'language_az <b>AZ</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("az"); return false;',
            'permissions' => '',
          ),
          'be' => 
          array (
            'id' => 'be',
            'text' => 'Беларуская',
            'description' => 'Белорусский <b>BE</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("be"); return false;',
            'permissions' => '',
          ),
          'bg' => 
          array (
            'id' => 'bg',
            'text' => 'Bulgarian',
            'description' => 'Болгарский <b>BG</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("bg"); return false;',
            'permissions' => '',
          ),
          'cs' => 
          array (
            'id' => 'cs',
            'text' => 'Čeština',
            'description' => 'Чешский <b>CS</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("cs"); return false;',
            'permissions' => '',
          ),
          'da' => 
          array (
            'id' => 'da',
            'text' => 'Danish',
            'description' => 'Датский <b>DA</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("da"); return false;',
            'permissions' => '',
          ),
          'de' => 
          array (
            'id' => 'de',
            'text' => 'Deutsch',
            'description' => 'Немецкий <b>DE</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("de"); return false;',
            'permissions' => '',
          ),
          'el' => 
          array (
            'id' => 'el',
            'text' => 'Greek',
            'description' => 'Греческий <b>EL</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("el"); return false;',
            'permissions' => '',
          ),
          'en' => 
          array (
            'id' => 'en',
            'text' => 'English',
            'description' => 'Английский <b>EN</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("en"); return false;',
            'permissions' => '',
          ),
          'es' => 
          array (
            'id' => 'es',
            'text' => 'Spanish',
            'description' => 'Испанский <b>ES</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("es"); return false;',
            'permissions' => '',
          ),
          'et' => 
          array (
            'id' => 'et',
            'text' => 'Estonian',
            'description' => 'Эстонский <b>ET</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("et"); return false;',
            'permissions' => '',
          ),
          'fa' => 
          array (
            'id' => 'fa',
            'text' => 'Persian',
            'description' => 'Персидский <b>FA</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("fa"); return false;',
            'permissions' => '',
          ),
          'fi' => 
          array (
            'id' => 'fi',
            'text' => 'Finnish',
            'description' => 'Финский <b>FI</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("fi"); return false;',
            'permissions' => '',
          ),
          'fr' => 
          array (
            'id' => 'fr',
            'text' => 'Français',
            'description' => 'Французский <b>FR</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("fr"); return false;',
            'permissions' => '',
          ),
          'he' => 
          array (
            'id' => 'he',
            'text' => 'Hebrew',
            'description' => 'Иврит <b>HE</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("he"); return false;',
            'permissions' => '',
          ),
          'hi' => 
          array (
            'id' => 'hi',
            'text' => 'हिंदी',
            'description' => 'Хинди <b>HI</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("hi"); return false;',
            'permissions' => '',
          ),
          'hu' => 
          array (
            'id' => 'hu',
            'text' => 'magyar',
            'description' => 'Венгерский <b>HU</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("hu"); return false;',
            'permissions' => '',
          ),
          'id' => 
          array (
            'id' => 'id',
            'text' => 'Indonesian',
            'description' => 'Индонезийский <b>ID</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("id"); return false;',
            'permissions' => '',
          ),
          'it' => 
          array (
            'id' => 'it',
            'text' => 'Italiano',
            'description' => 'Итальянский <b>IT</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("it"); return false;',
            'permissions' => '',
          ),
          'ja' => 
          array (
            'id' => 'ja',
            'text' => 'Japanese',
            'description' => 'Японский <b>JA</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("ja"); return false;',
            'permissions' => '',
          ),
          'nl' => 
          array (
            'id' => 'nl',
            'text' => 'Nederlands',
            'description' => 'Голландский <b>NL</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("nl"); return false;',
            'permissions' => '',
          ),
          'pl' => 
          array (
            'id' => 'pl',
            'text' => 'Polish',
            'description' => 'Польский <b>PL</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("pl"); return false;',
            'permissions' => '',
          ),
          'pt' => 
          array (
            'id' => 'pt',
            'text' => 'Portuguese',
            'description' => 'Португальский <b>PT</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("pt"); return false;',
            'permissions' => '',
          ),
          'ro' => 
          array (
            'id' => 'ro',
            'text' => 'Romanian',
            'description' => 'Румынский <b>RO</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("ro"); return false;',
            'permissions' => '',
          ),
          'ru' => 
          array (
            'id' => 'ru',
            'text' => 'Русский',
            'description' => 'Русский <b>RU</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("ru"); return false;',
            'permissions' => '',
          ),
          'sl' => 
          array (
            'id' => 'sl',
            'text' => 'language_sl',
            'description' => 'language_sl <b>SL</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("sl"); return false;',
            'permissions' => '',
          ),
          'sv' => 
          array (
            'id' => 'sv',
            'text' => 'Svenska',
            'description' => 'Шведский <b>SV</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("sv"); return false;',
            'permissions' => '',
          ),
          'th' => 
          array (
            'id' => 'th',
            'text' => 'Thai',
            'description' => 'Тайский <b>TH</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("th"); return false;',
            'permissions' => '',
          ),
          'tr' => 
          array (
            'id' => 'tr',
            'text' => 'Turkish',
            'description' => 'Турецкий <b>TR</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("tr"); return false;',
            'permissions' => '',
          ),
          'uk' => 
          array (
            'id' => 'uk',
            'text' => 'Українська',
            'description' => 'Украинский <b>UK</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("uk"); return false;',
            'permissions' => '',
          ),
          'yo' => 
          array (
            'id' => 'yo',
            'text' => 'Yoruba',
            'description' => 'Йоруба <b>YO</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("yo"); return false;',
            'permissions' => '',
          ),
          'zh' => 
          array (
            'id' => 'zh',
            'text' => '简体中文',
            'description' => 'Китайский (упрощенный) <b>ZH</b>',
            'parent' => 'language',
            'handler' => 'MODx.switchLanguage("zh"); return false;',
            'permissions' => '',
          ),
        ),
        'controller' => '',
      ),
      9 => 
      array (
        'text' => 'Отчёты',
        'parent' => 'admin',
        'action' => '',
        'description' => 'Различные отчеты MODX для администратора',
        'icon' => '',
        'menuindex' => 9,
        'params' => '',
        'handler' => '',
        'permissions' => 'menu_reports',
        'namespace' => 'core',
        'id' => 'reports',
        'children' => 
        array (
          0 => 
          array (
            'text' => 'Журнал панели управления',
            'parent' => 'reports',
            'action' => 'system/logs',
            'description' => 'Просмотр последних действий менеджеров сайта',
            'icon' => '',
            'menuindex' => 0,
            'params' => '',
            'handler' => '',
            'permissions' => 'mgr_log_view',
            'namespace' => 'core',
            'id' => 'view_logging',
            'children' => 
            array (
            ),
            'controller' => '',
          ),
          1 => 
          array (
            'text' => 'Журнал ошибок',
            'parent' => 'reports',
            'action' => 'system/event',
            'description' => 'Открыть error.log MODX',
            'icon' => '',
            'menuindex' => 1,
            'params' => '',
            'handler' => '',
            'permissions' => 'view_eventlog',
            'namespace' => 'core',
            'id' => 'eventlog_viewer',
            'children' => 
            array (
            ),
            'controller' => '',
          ),
          2 => 
          array (
            'text' => 'Информация о системе',
            'parent' => 'reports',
            'action' => 'system/info',
            'description' => 'Просмотр информации о сервере, настройках PHP, информации о базе данных MySQL, и многое другое',
            'icon' => '',
            'menuindex' => 2,
            'params' => '',
            'handler' => '',
            'permissions' => 'view_sysinfo',
            'namespace' => 'core',
            'id' => 'view_sysinfo',
            'children' => 
            array (
            ),
            'controller' => '',
          ),
        ),
        'controller' => '',
      ),
    ),
    'controller' => '',
  ),
  3 => 
  array (
    'text' => 'О проекте',
    'parent' => 'usernav',
    'action' => 'help',
    'description' => 'Помощь!',
    'icon' => '<i class="icon-question-circle icon"></i>',
    'menuindex' => 3,
    'params' => '',
    'handler' => '',
    'permissions' => 'help',
    'namespace' => 'core',
    'id' => 'about',
    'children' => 
    array (
    ),
    'controller' => '',
  ),
);