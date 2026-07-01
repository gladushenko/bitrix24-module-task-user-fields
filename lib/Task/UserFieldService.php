<?php

namespace Gladushenko\TaskUserFields\Task;

use CUserFieldEnum;

class UserFieldService
{
    public const UF_ENTITY = 'TASKS_TASK';
    public const UNSUPPORTED_REASON = 'не поддерживается модулем';

    private const SUPPORTED_TYPES = [
        'integer',
        'double',
        'string',
        'enumeration',
        'boolean',
        'datetime',
        'date',
        'money',
        'url',
        'iblock_element',
        'iblock_section',
    ];

    /**
     * Возвращает все пользовательские поля задачи.
     *
     * @return array
     */
    public static function getAvailableFields(): array
    {
        global $USER_FIELD_MANAGER;

        if (!$USER_FIELD_MANAGER) {
            return [];
        }

        $fields = $USER_FIELD_MANAGER->GetUserFields(self::UF_ENTITY, 0, LANGUAGE_ID);

        return is_array($fields) ? $fields : [];
    }

    /**
     * Возвращает сырые значения пользовательских полей задачи.
     *
     * @param int $taskId
     *
     * @return array
     */
    public static function getTaskValues(int $taskId): array
    {
        if ($taskId <= 0) {
            return [];
        }

        global $USER_FIELD_MANAGER;

        if (!$USER_FIELD_MANAGER) {
            return [];
        }

        $fields = $USER_FIELD_MANAGER->GetUserFields(self::UF_ENTITY, $taskId, LANGUAGE_ID);
        $values = [];

        foreach ($fields as $fieldName => $field) {
            $values[$fieldName] = $field['VALUE'];
        }

        return $values;
    }

    /**
     * Возвращает пользовательские поля задачи в формате для frontend.
     *
     * @param int $taskId
     *
     * @return array|null
     */
    public static function getTaskFieldsForView(int $taskId): ?array
    {
        if ($taskId <= 0) {
            return null;
        }

        global $USER_FIELD_MANAGER;

        if (!$USER_FIELD_MANAGER) {
            return null;
        }

        $rawFields = $USER_FIELD_MANAGER->GetUserFields(self::UF_ENTITY, $taskId, LANGUAGE_ID);
        $formattedFields = [];

        foreach ($rawFields as $fieldName => $field) {
            $formattedFields[$fieldName] = static::formatFieldForView($field);
        }

        return $formattedFields;
    }

    /**
     * Сохраняет значение пользовательского поля задачи.
     *
     * @param int $taskId
     * @param string $fieldName
     * @param mixed $value
     *
     * @return bool
     */
    public static function saveTaskFieldValue(int $taskId, string $fieldName, $value): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        global $USER_FIELD_MANAGER;

        if (!$USER_FIELD_MANAGER) {
            return false;
        }

        return (bool)$USER_FIELD_MANAGER->Update(self::UF_ENTITY, $taskId, [$fieldName => $value]);
    }

    /**
     * Проверяет, разрешено ли поле для редактирования через модуль.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public static function isFieldAllowed(string $fieldName): bool
    {
        foreach (UserFieldSettings::getDisplayFields() as $displayField) {
            if (!empty($displayField['enabled']) && $displayField['name'] === $fieldName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает список поддерживаемых типов пользовательских полей.
     *
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    /**
     * Проверяет, поддерживается ли тип пользовательского поля модулем.
     *
     * @param string $typeId
     *
     * @return bool
     */
    public static function isSupportedType(string $typeId): bool
    {
        return in_array($typeId, self::SUPPORTED_TYPES, true);
    }

    /**
     * Возвращает причину недоступности типа поля.
     *
     * @param string $typeId
     *
     * @return string
     */
    public static function getUnsupportedReason(string $typeId): string
    {
        return self::UNSUPPORTED_REASON;
    }

    /**
     * Возвращает системную подпись пользовательского поля.
     *
     * @param array $userField
     * @param string $fallback
     *
     * @return string
     */
    public static function getFieldSystemLabel(array $userField, string $fallback): string
    {
        return (string)($userField['EDIT_FORM_LABEL'] ?: $fallback);
    }

    /**
     * Форматирует пользовательское поле для отображения в JS.
     *
     * @param array $field
     *
     * @return array
     */
    private static function formatFieldForView(array $field): array
    {
        $value = $field['VALUE'];
        $typeId = $field['USER_TYPE_ID'] ?? '';
        $entry = [
            'value' => $value,
            'display' => '',
            'options' => null,
        ];

        switch ($typeId) {
            case 'enumeration':
                $entry['display'] = static::resolveEnumDisplay($field, $value);
                $entry['options'] = static::extractEnumOptions($field);
                break;

            case 'boolean':
                $entry['display'] = ($value && $value !== '0') ? 'Да' : 'Нет';
                break;

            case 'datetime':
            case 'date':
                $entry['display'] = is_string($value) ? $value : '';
                break;

            default:
                $entry['display'] = is_array($value)
                    ? implode(', ', array_filter(array_map('strval', $value)))
                    : (string)$value;
                break;
        }

        return $entry;
    }

    /**
     * Возвращает человекочитаемые значения списка.
     *
     * @param array $field
     * @param mixed $value
     *
     * @return string
     */
    private static function resolveEnumDisplay(array $field, $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }

        $ids = is_array($value) ? $value : [$value];
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return '';
        }

        $enumMap = static::getEnumMap($field);
        $labels = [];

        foreach ($ids as $id) {
            if (isset($enumMap[$id])) {
                $labels[] = $enumMap[$id];
            }
        }

        if ($labels) {
            return implode(', ', $labels);
        }

        return static::loadEnumLabels($ids);
    }

    /**
     * Возвращает карту значений списка из данных поля.
     *
     * @param array $field
     *
     * @return array
     */
    private static function getEnumMap(array $field): array
    {
        $enumMap = [];

        if (empty($field['ENUM']) || !is_array($field['ENUM'])) {
            return $enumMap;
        }

        foreach ($field['ENUM'] as $item) {
            $enumMap[(int)$item['ID']] = $item['VALUE'];
        }

        return $enumMap;
    }

    /**
     * Загружает подписи значений списка из базы.
     *
     * @param array $ids
     *
     * @return string
     */
    private static function loadEnumLabels(array $ids): string
    {
        $labels = [];

        foreach ($ids as $id) {
            $result = CUserFieldEnum::GetList([], ['ID' => $id]);

            if ($row = $result->GetNext()) {
                $labels[] = $row['VALUE'];
            }
        }

        return implode(', ', $labels);
    }

    /**
     * Возвращает варианты значения пользовательского списка.
     *
     * @param array $field
     *
     * @return array
     */
    private static function extractEnumOptions(array $field): array
    {
        if (!empty($field['ENUM']) && is_array($field['ENUM'])) {
            return static::formatEnumOptions($field['ENUM']);
        }

        if (empty($field['ID'])) {
            return [];
        }

        $options = [];
        $result = CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => (int)$field['ID']]);

        while ($row = $result->GetNext()) {
            $options[] = [
                'id' => (string)$row['ID'],
                'value' => (string)$row['VALUE'],
            ];
        }

        return $options;
    }

    /**
     * Форматирует варианты списка для frontend.
     *
     * @param array $enumItems
     *
     * @return array
     */
    private static function formatEnumOptions(array $enumItems): array
    {
        $options = [];

        foreach ($enumItems as $item) {
            $options[] = [
                'id' => (string)$item['ID'],
                'value' => (string)$item['VALUE'],
            ];
        }

        return $options;
    }
}
