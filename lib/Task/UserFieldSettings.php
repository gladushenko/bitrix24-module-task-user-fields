<?php

namespace Gladushenko\TaskUserFields\Task;

use Bitrix\Main\Config\Option;

class UserFieldSettings
{
    public const MODULE_ID = 'gladushenko.taskuserfields';
    public const DEFAULT_TITLE = 'Дополнительные поля';

    private const OPTION_FIELDS = 'task_uf_display_fields';
    private const OPTION_TITLE = 'task_uf_block_title';
    private const OPTION_HIDE_NATIVE = 'task_uf_hide_native';

    /**
     * Возвращает сохраненный список полей для отображения.
     *
     * @return array
     */
    public static function getDisplayFields(): array
    {
        $json = Option::get(self::MODULE_ID, self::OPTION_FIELDS, '[]');
        $fields = json_decode($json, true);

        return is_array($fields) ? $fields : [];
    }

    /**
     * Сохраняет список полей для отображения.
     *
     * @param array $fields
     *
     * @return void
     */
    public static function saveDisplayFields(array $fields): void
    {
        Option::set(self::MODULE_ID, self::OPTION_FIELDS, json_encode(array_values($fields)));
    }

    /**
     * Возвращает заголовок блока пользовательских полей.
     *
     * @return string
     */
    public static function getBlockTitle(): string
    {
        return Option::get(self::MODULE_ID, self::OPTION_TITLE, self::DEFAULT_TITLE);
    }

    /**
     * Сохраняет заголовок блока пользовательских полей.
     *
     * @param string $title
     *
     * @return void
     */
    public static function saveBlockTitle(string $title): void
    {
        Option::set(self::MODULE_ID, self::OPTION_TITLE, trim($title));
    }

    /**
     * Проверяет, нужно ли скрывать нативный блок UF-полей Битрикса.
     *
     * @return bool
     */
    public static function isNativeBlockHidden(): bool
    {
        return Option::get(self::MODULE_ID, self::OPTION_HIDE_NATIVE, 'N') === 'Y';
    }

    /**
     * Сохраняет настройку скрытия нативного блока UF-полей.
     *
     * @param bool $isHidden
     *
     * @return void
     */
    public static function saveNativeBlockHidden(bool $isHidden): void
    {
        Option::set(self::MODULE_ID, self::OPTION_HIDE_NATIVE, $isHidden ? 'Y' : 'N');
    }
}
