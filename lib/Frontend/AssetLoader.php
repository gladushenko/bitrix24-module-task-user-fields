<?php

namespace Gladushenko\TaskUserFields\Frontend;

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

class AssetLoader
{
    /**
     * Подключает CSS и JS модуля на нужных страницах.
     *
     * @return void
     */
    public static function onProlog(): void
    {
        global $APPLICATION;

        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            static::addAdminAssets();
            return;
        }

        if (!Loader::includeModule('gladushenko.taskuserfields')) {
            return;
        }

        $frontendConfig = TaskUserFieldConfig::build();
        $configJson = json_encode($frontendConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        $APPLICATION->AddHeadString('<script>window.TASK_USER_FIELDS_MODULE_CONFIG = ' . $configJson . ';</script>');

        foreach (static::getScriptPaths() as $scriptPath) {
            static::addScript($scriptPath);
        }
    }

    /**
     * Подключает CSS и JS административной части модуля.
     *
     * @return void
     */
    public static function addAdminAssets(): void
    {
        global $APPLICATION;
        $APPLICATION->SetAdditionalCSS('/local/modules/gladushenko.taskuserfields/assets/css/admin.css');
        $APPLICATION->AddHeadScript('/local/modules/gladushenko.taskuserfields/assets/js/admin-settings.js');
    }

    /**
     * Возвращает список frontend-скриптов модуля в порядке подключения.
     *
     * @return array
     */
    private static function getScriptPaths(): array
    {
        return [
            '/local/modules/gladushenko.taskuserfields/assets/js/task-error-notifier.js',
            '/local/modules/gladushenko.taskuserfields/assets/js/task-user-fields.js',
            '/local/modules/gladushenko.taskuserfields/assets/js/main.js',
        ];
    }

    /**
     * Подключает JS-файл.
     *
     * @param string $scriptPath
     *
     * @return void
     */
    private static function addScript(string $scriptPath): void
    {
        Asset::getInstance()->addJs($scriptPath);
    }
}
