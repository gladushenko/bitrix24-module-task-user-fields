<?php

namespace Gladushenko\TaskUserFields\Admin;

class Menu
{
    /**
     * Добавляет пункт модуля в административное меню Битрикса.
     *
     * @param array $globalMenu
     * @param array $moduleMenu
     *
     * @return void
     */
    public static function onBuildGlobalMenu(array &$globalMenu, array &$moduleMenu): void
    {
        if (!isset($globalMenu['global_menu_taskufields'])) {
            $globalMenu['global_menu_taskufields'] = [
                'menu_id' => 'taskufields',
                'text' => 'ПП задач Б24',
                'title' => 'ПП задач Б24',
                'sort' => 510,
                'url' => '/local/modules/gladushenko.taskuserfields/admin/index.php',
                'items_id' => 'global_menu_taskufields',
                'items' => [],
            ];
        }

        $moduleMenu[] = [
            'parent_menu' => 'global_menu_taskufields',
            'sort' => 320,
            'text' => 'Настройки',
            'title' => 'Настройка пользовательских полей задач Б24',
            'url' => '/local/modules/gladushenko.taskuserfields/admin/index.php',
            'items_id' => 'menu_taskufields_settings',
            'items' => [],
        ];
    }
}
