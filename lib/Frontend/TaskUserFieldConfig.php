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
        $displayFields = UserFieldSettings::getDisplayFields();
        $enabledFields = array_filter($displayFields, static fn ($field) => !empty($field['enabled']));

        if (empty($enabledFields)) {
            return ['fields' => []];
        }

        $availableFields = UserFieldService::getAvailableFields();
        $frontendFields = [];

        foreach ($enabledFields as $displayField) {
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

        return [
            'title' => UserFieldSettings::getBlockTitle(),
            'hideNative' => UserFieldSettings::isNativeBlockHidden(),
            'cardOrder' => 0,
            'actions' => [
                'getTaskUf' => 'gladushenko:taskuserfields.controller.taskuserfield.getTaskUf',
                'saveTaskUf' => 'gladushenko:taskuserfields.controller.taskuserfield.saveTaskUf',
            ],
            'fields' => $frontendFields,
        ];
    }
}
