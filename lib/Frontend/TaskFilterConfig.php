<?php

namespace Gladushenko\TaskUserFields\Frontend;

use CUserFieldEnum;
use Gladushenko\TaskUserFields\Task\UserFieldService;
use Gladushenko\TaskUserFields\Task\UserFieldSettings;

class TaskFilterConfig
{
    /**
     * Собирает конфиг списочных пользовательских полей для фильтра задач.
     *
     * @param array $availableFields
     *
     * @return array
     */
    public static function build(array $availableFields): array
    {
        $frontendSets = [];

        foreach (UserFieldSettings::getDisplaySets() as $displaySet) {
            $fields = static::buildFilterFields((array)($displaySet['fields'] ?? []), $availableFields);

            if (empty($fields)) {
                continue;
            }

            $frontendSets[] = [
                'id' => (string)($displaySet['id'] ?? ''),
                'projectIds' => array_values(array_map('intval', (array)($displaySet['projectIds'] ?? []))),
                'fields' => $fields,
            ];
        }

        return [
            'sets' => $frontendSets,
            'fields' => $frontendSets[0]['fields'] ?? [],
        ];
    }

    /**
     * Собирает поля области для фильтра задач.
     *
     * @param array $displayFields
     * @param array $availableFields
     *
     * @return array
     */
    private static function buildFilterFields(array $displayFields, array $availableFields): array
    {
        $filterFields = [];

        foreach ($displayFields as $displayField) {
            if (empty($displayField['enabled'])) {
                continue;
            }

            $fieldName = (string)($displayField['name'] ?? '');

            if ($fieldName === '' || !isset($availableFields[$fieldName])) {
                continue;
            }

            $userField = $availableFields[$fieldName];

            if (($userField['USER_TYPE_ID'] ?? '') !== 'enumeration') {
                continue;
            }

            $items = static::getEnumItems($userField);

            if (empty($items)) {
                continue;
            }

            $filterFields[] = [
                'name' => $fieldName,
                'label' => !empty($displayField['label'])
                    ? (string)$displayField['label']
                    : UserFieldService::getFieldSystemLabel($userField, $fieldName),
                'multiple' => ($userField['MULTIPLE'] ?? 'N') === 'Y',
                'items' => $items,
            ];
        }

        return $filterFields;
    }

    /**
     * Возвращает значения пользовательского списка для фильтра.
     *
     * @param array $userField
     *
     * @return array
     */
    private static function getEnumItems(array $userField): array
    {
        if (empty($userField['ID'])) {
            return [];
        }

        $items = [];
        $result = CUserFieldEnum::GetList(
            [
                'SORT' => 'ASC',
                'ID' => 'ASC',
            ],
            [
                'USER_FIELD_ID' => (int)$userField['ID'],
            ]
        );

        while ($row = $result->Fetch()) {
            $items[] = [
                'NAME' => (string)$row['VALUE'],
                'VALUE' => (string)$row['ID'],
            ];
        }

        return $items;
    }
}
