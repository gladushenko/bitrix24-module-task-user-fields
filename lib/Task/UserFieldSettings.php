<?php

namespace Gladushenko\TaskUserFields\Task;

use Bitrix\Main\Config\Option;

class UserFieldSettings
{
    public const MODULE_ID = 'gladushenko.taskuserfields';
    public const DEFAULT_TITLE = 'Дополнительные поля';

    private const OPTION_FIELDS = 'task_uf_display_fields';
    private const OPTION_FIELD_SETS = 'task_uf_display_sets';
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
     * Возвращает сохраненные области пользовательских полей.
     *
     * @return array
     */
    public static function getDisplaySets(): array
    {
        $json = Option::get(self::MODULE_ID, self::OPTION_FIELD_SETS, '[]');
        $sets = json_decode($json, true);

        if (is_array($sets) && !empty($sets)) {
            return static::limitDisplaySets(static::normalizeDisplaySets($sets));
        }

        return [static::buildLegacyDisplaySet()];
    }

    /**
     * Сохраняет области пользовательских полей.
     *
     * @param array $sets
     *
     * @return void
     */
    public static function saveDisplaySets(array $sets): void
    {
        Option::set(
            self::MODULE_ID,
            self::OPTION_FIELD_SETS,
            json_encode(static::limitDisplaySets(static::normalizeDisplaySets($sets)))
        );
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

    /**
     * Собирает область из старых настроек модуля.
     *
     * @return array
     */
    private static function buildLegacyDisplaySet(): array
    {
        return [
            'id' => 'default',
            'title' => static::getBlockTitle(),
            'projectIds' => [],
            'fields' => static::getDisplayFields(),
        ];
    }

    /**
     * Нормализует список областей пользовательских полей.
     *
     * @param array $sets
     *
     * @return array
     */
    private static function normalizeDisplaySets(array $sets): array
    {
        $normalizedSets = [];

        foreach ($sets as $index => $set) {
            if (!is_array($set)) {
                continue;
            }

            $fields = static::normalizeDisplayFields((array)($set['fields'] ?? []));

            if (empty($fields)) {
                continue;
            }

            $normalizedSets[] = [
                'id' => static::normalizeSetId((string)($set['id'] ?? ''), $index),
                'title' => trim((string)($set['title'] ?? '')),
                'projectIds' => static::normalizeProjectIds((array)($set['projectIds'] ?? [])),
                'fields' => $fields,
            ];
        }

        if (!empty($normalizedSets)) {
            return $normalizedSets;
        }

        return [static::buildLegacyDisplaySet()];
    }

    /**
     * Оставляет одну основную область пользовательских полей.
     *
     * @param array $sets
     *
     * @return array
     */
    private static function limitDisplaySets(array $sets): array
    {
        return array_slice($sets, 0, 1);
    }

    /**
     * Нормализует список полей области.
     *
     * @param array $fields
     *
     * @return array
     */
    private static function normalizeDisplayFields(array $fields): array
    {
        $normalizedFields = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = trim((string)($field['name'] ?? ''));

            if ($name === '' || strpos($name, 'UF_') !== 0) {
                continue;
            }

            $normalizedFields[] = [
                'name' => $name,
                'label' => trim((string)($field['label'] ?? '')),
                'enabled' => !empty($field['enabled']),
            ];
        }

        return $normalizedFields;
    }

    /**
     * Нормализует ID области.
     *
     * @param string $id
     * @param int $index
     *
     * @return string
     */
    private static function normalizeSetId(string $id, int $index): string
    {
        $id = trim($id);

        if ($id !== '') {
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        }

        return 'set_' . ($index + 1);
    }

    /**
     * Нормализует список ID проектов.
     *
     * @param array $projectIds
     *
     * @return array
     */
    private static function normalizeProjectIds(array $projectIds): array
    {
        $ids = [];

        foreach ($projectIds as $projectId) {
            $projectId = (int)$projectId;

            if ($projectId > 0) {
                $ids[] = $projectId;
            }
        }

        return array_values(array_unique($ids));
    }
}
