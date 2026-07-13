<?php

namespace Gladushenko\TaskUserFields\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Gladushenko\TaskUserFields\Task\UserFieldService;

class TaskUserField extends Controller
{
    /**
     * Возвращает настройки действий контроллера.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'getTaskUf' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                ],
            ],
            'saveTaskUf' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    /**
     * Возвращает пользовательские поля задачи для frontend.
     *
     * @param int $taskId
     *
     * @return array|null
     */
    public function getTaskUfAction(int $taskId): ?array
    {
        if ($taskId <= 0) {
            return null;
        }

        Loader::includeModule('gladushenko.taskuserfields');

        return [
            'task' => [
                'id' => $taskId,
                'groupId' => UserFieldService::getTaskGroupId($taskId),
            ],
            'fields' => UserFieldService::getTaskFieldsForView($taskId),
        ];
    }

    /**
     * Сохраняет значение одного пользовательского поля задачи.
     *
     * @param int $taskId
     * @param string $fieldName
     * @param mixed $value
     *
     * @return array|null
     */
    public function saveTaskUfAction(int $taskId, string $fieldName, $value): ?array
    {
        if ($taskId <= 0) {
            return null;
        }

        if (!preg_match('/^UF_[A-Z0-9_]+$/i', $fieldName)) {
            $this->addError(new Error('Недопустимое имя поля'));
            return null;
        }

        Loader::includeModule('gladushenko.taskuserfields');

        $projectId = UserFieldService::getTaskGroupId($taskId);

        if (!UserFieldService::isFieldAllowed($fieldName, $projectId)) {
            $this->addError(new Error('Поле не настроено для отображения'));
            return null;
        }

        if (!UserFieldService::isFieldEditable($fieldName, $projectId)) {
            $this->addError(new Error('Поле недоступно для редактирования'));
            return null;
        }

        $saved = UserFieldService::saveTaskFieldValue($taskId, $fieldName, $value);

        if (!$saved) {
            global $APPLICATION;

            $exception = $APPLICATION->GetException();
            $this->addError(new Error($exception ? $exception->GetString() : 'Не удалось сохранить'));

            return null;
        }

        return [
            'saved' => true,
        ];
    }
}
