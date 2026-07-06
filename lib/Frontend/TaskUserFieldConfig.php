<?php

namespace Gladushenko\TaskUserFields\Frontend;

use Gladushenko\TaskUserFields\Task\UserFieldService;
use Gladushenko\TaskUserFields\Task\UserFieldSettings;

class TaskUserFieldConfig
{
    /**
     * Собирает конфиг пользовательских полей задачи для JS.
     *
     * @return array
     */
    public static function build(): array
    {
        $availableFields = UserFieldService::getAvailableFields();
        $frontendSets = static::buildFrontendSets(UserFieldSettings::getDisplaySets(), $availableFields);
        $firstSet = $frontendSets[0] ?? [];

        return [
            'title' => $firstSet['title'] ?? UserFieldSettings::DEFAULT_TITLE,
            'hideNative' => UserFieldSettings::isNativeBlockHidden(),
            'cardOrder' => 0,
            'actions' => [
                'getTaskUf' => 'gladushenko:taskuserfields.controller.taskuserfield.getTaskUf',
                'saveTaskUf' => 'gladushenko:taskuserfields.controller.taskuserfield.saveTaskUf',
            ],
            'sets' => $frontendSets,
            'fields' => $firstSet['fields'] ?? [],
        ];
    }

    /**
     * Собирает области пользовательских полей для frontend.
     *
     * @param array $displaySets
     * @param array $availableFields
     *
     * @return array
     */
    private static function buildFrontendSets(array $displaySets, array $availableFields): array
    {
        $frontendSets = [];

        foreach ($displaySets as $displaySet) {
            $fields = static::buildFrontendFields((array)($displaySet['fields'] ?? []), $availableFields);

            $frontendSets[] = [
                'id' => (string)($displaySet['id'] ?? ''),
                'title' => (string)($displaySet['title'] ?? UserFieldSettings::DEFAULT_TITLE),
                'projectIds' => array_values(array_map('intval', (array)($displaySet['projectIds'] ?? []))),
                'fields' => $fields,
            ];
        }

        return $frontendSets;
    }

    /**
     * Собирает поля области для frontend.
     *
     * @param array $displayFields
     * @param array $availableFields
     *
     * @return array
     */
    private static function buildFrontendFields(array $displayFields, array $availableFields): array
    {
        $frontendFields = [];

        foreach ($displayFields as $displayField) {
            if (empty($displayField['enabled'])) {
                continue;
            }

            $fieldName = $displayField['name'] ?? '';

            if ($fieldName === '' || !isset($availableFields[$fieldName])) {
                continue;
            }

            $userField = $availableFields[$fieldName];
            $typeId = (string)$userField['USER_TYPE_ID'];

            if (!UserFieldService::isSupportedType($typeId)) {
                continue;
            }

            $frontendFields[] = [
                'name' => $fieldName,
                'label' => !empty($displayField['label'])
                    ? $displayField['label']
                    : UserFieldService::getFieldSystemLabel($userField, $fieldName),
                'type' => $typeId,
            ];
        }

        return $frontendFields;
    }
}
