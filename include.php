<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;
use Gladushenko\TaskUserFields\Admin\Menu;
use Gladushenko\TaskUserFields\Controller\TaskUserField;
use Gladushenko\TaskUserFields\Frontend\AssetLoader;
use Gladushenko\TaskUserFields\Frontend\TaskFilterConfig;
use Gladushenko\TaskUserFields\Frontend\TaskUserFieldConfig;
use Gladushenko\TaskUserFields\Task\UserFieldService;
use Gladushenko\TaskUserFields\Task\UserFieldSettings;

Loader::registerAutoLoadClasses(
    'gladushenko.taskuserfields',
    [
        Menu::class => 'lib/Admin/Menu.php',
        AssetLoader::class => 'lib/Frontend/AssetLoader.php',
        TaskFilterConfig::class => 'lib/Frontend/TaskFilterConfig.php',
        TaskUserFieldConfig::class => 'lib/Frontend/TaskUserFieldConfig.php',
        UserFieldSettings::class => 'lib/Task/UserFieldSettings.php',
        UserFieldService::class => 'lib/Task/UserFieldService.php',
        TaskUserField::class => 'lib/Controller/TaskUserField.php',
    ]
);
