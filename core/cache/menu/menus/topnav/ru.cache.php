<?php  return array (
  0 => 
  array (
    'text' => 'Содержимое',
    'parent' => 'topnav',
    'action' => '',
    'description' => '',
    'icon' => '<i class="icon-file-text-o icon"></i>',
    'menuindex' => 0,
    'params' => '',
    'handler' => '',
    'permissions' => 'menu_site',
    'namespace' => 'core',
    'id' => 'site',
    'children' => 
    array (
      0 => 
      array (
        'text' => 'Создать ресурс',
        'parent' => 'site',
        'action' => 'resource/create',
        'description' => 'Создать новый ресурс',
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => 'new_document',
        'namespace' => 'core',
        'id' => 'new_resource',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      1 => 
      array (
        'text' => 'Очистить кэш',
        'parent' => 'site',
        'action' => '',
        'description' => 'Очистить кэш сайта во всех контекстах',
        'icon' => '',
        'menuindex' => 1,
        'params' => '',
        'handler' => 'MODx.clearCache(); return false;',
        'permissions' => 'empty_cache',
        'namespace' => 'core',
        'id' => 'refresh_site',
        'children' => 
        array (
          0 => 
          array (
            'text' => 'Обновить URI-ссылки',
            'parent' => 'refresh_site',
            'action' => '',
            'description' => 'Перегенерировать URI ресурсов',
            'icon' => '',
            'menuindex' => 0,
            'params' => '',
            'handler' => 'MODx.refreshURIs(); return false;',
            'permissions' => 'empty_cache',
            'namespace' => 'core',
            'id' => 'refreshuris',
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
        'text' => 'Удалить блокировки',
        'parent' => 'site',
        'action' => '',
        'description' => 'Удалить все блокировки. Эти блокировки являются результатом того, что другие пользователи редактируют эти страницы',
        'icon' => '',
        'menuindex' => 2,
        'params' => '',
        'handler' => 'MODx.removeLocks();return false;',
        'permissions' => 'remove_locks',
        'namespace' => 'core',
        'id' => 'remove_locks',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      3 => 
      array (
        'text' => 'Расписание сайта',
        'parent' => 'site',
        'action' => 'resource/site_schedule',
        'description' => 'Просмотр расписания публикаций и снятия с публикации',
        'icon' => '',
        'menuindex' => 3,
        'params' => '',
        'handler' => '',
        'permissions' => 'view_document',
        'namespace' => 'core',
        'id' => 'site_schedule',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      4 => 
      array (
        'text' => 'Типы содержимого',
        'parent' => 'site',
        'action' => 'system/contenttype',
        'description' => 'Вы можете добавить новые типы содержимого для ресурсов, например такие как .html, .js, и т.п.',
        'icon' => '',
        'menuindex' => 4,
        'params' => '',
        'handler' => '',
        'permissions' => 'content_types',
        'namespace' => 'core',
        'id' => 'content_types',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      5 => 
      array (
        'text' => 'Корзина',
        'parent' => 'site',
        'action' => 'resource/trash',
        'description' => 'Управление удаленными ресурсами.',
        'icon' => '',
        'menuindex' => 5,
        'params' => '',
        'handler' => '',
        'permissions' => 'menu_trash',
        'namespace' => 'core',
        'id' => 'trash',
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
    'text' => 'Медиа',
    'parent' => 'topnav',
    'action' => '',
    'description' => '',
    'icon' => '<i class="icon-file-image-o icon"></i>',
    'menuindex' => 1,
    'params' => '',
    'handler' => '',
    'permissions' => 'file_manager',
    'namespace' => 'core',
    'id' => 'media',
    'children' => 
    array (
      0 => 
      array (
        'text' => 'Управление медиа',
        'parent' => 'media',
        'action' => 'media/browser',
        'description' => 'Отображение, загрузка и управление медиа-файлами',
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => 'file_manager',
        'namespace' => 'core',
        'id' => 'file_browser',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
      1 => 
      array (
        'text' => 'Источники файлов',
        'parent' => 'media',
        'action' => 'source',
        'description' => 'Управление источниками файлов',
        'icon' => '',
        'menuindex' => 1,
        'params' => '',
        'handler' => '',
        'permissions' => 'sources',
        'namespace' => 'core',
        'id' => 'sources',
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
    'text' => 'Пакеты',
    'parent' => 'topnav',
    'action' => '',
    'description' => '',
    'icon' => '<i class="icon-cube icon"></i>',
    'menuindex' => 2,
    'params' => '',
    'handler' => '',
    'permissions' => 'components',
    'namespace' => 'core',
    'id' => 'components',
    'children' => 
    array (
      0 => 
      array (
        'text' => 'Установщик',
        'parent' => 'components',
        'action' => 'workspaces',
        'description' => 'Управление пакетами и репозиториями',
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => 'packages',
        'namespace' => 'core',
        'id' => 'installer',
        'children' => 
        array (
        ),
        'controller' => '',
      ),
    ),
    'controller' => '',
  ),
);