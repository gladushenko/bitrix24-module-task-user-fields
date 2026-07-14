<?php
/**
 * Страница администратора: Пользовательские поля в карточке задачи.
 *
 * Позволяет выбрать UF-поля сущности TASKS_TASK для отображения
 * в карточке задачи Bitrix24.
 */

defined('B_PROLOG_INCLUDED') || define('B_PROLOG_INCLUDED', true);
defined('STOP_STATISTICS') || define('STOP_STATISTICS', true);
defined('NO_KEEP_STATISTIC') || define('NO_KEEP_STATISTIC', 'Y');
defined('NEED_AUTH') || define('NEED_AUTH', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Gladushenko\TaskUserFields\Frontend\AssetLoader;
use Gladushenko\TaskUserFields\Task\UserFieldService;
use Gladushenko\TaskUserFields\Task\UserFieldSettings;

/** @var CUser $USER */
/** @var CMain $APPLICATION */

if (!$USER->IsAdmin()) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    CAdminMessage::ShowMessage(['MESSAGE' => 'Доступ запрещён.', 'TYPE' => 'ERROR']);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
}

Loader::includeModule('gladushenko.taskuserfields');

if (!function_exists('taskUserFieldsHtml')) {
    /**
     * Возвращает безопасное значение для HTML.
     *
     * @param mixed $value
     *
     * @return string
     */
    function taskUserFieldsHtml($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, LANG_CHARSET);
    }
}

/**
 * Возвращает карту сохраненных полей области.
 *
 * @param array $displaySet
 *
 * @return array
 */
function taskUserFieldsBuildSavedMap(array $displaySet): array
{
    $savedMap = [];

    foreach ((array)($displaySet['fields'] ?? []) as $field) {
        if (!empty($field['name'])) {
            $savedMap[$field['name']] = $field;
        }
    }

    return $savedMap;
}

/**
 * Считает количество включенных полей области.
 *
 * @param array $displaySet
 *
 * @return int
 */
function taskUserFieldsCountEnabledFields(array $displaySet): int
{
    $count = 0;

    foreach ((array)($displaySet['fields'] ?? []) as $field) {
        if (!empty($field['enabled'])) {
            $count++;
        }
    }

    return $count;
}

$saveError = '';

if (!empty($_POST['action']) && check_bitrix_sessid()) {
    $action = (string)$_POST['action'];

    try {
        if ($action !== 'save_task_uf_config') {
            throw new InvalidArgumentException('Неизвестный action: ' . $action);
        }

        UserFieldSettings::saveNativeBlockHidden(!empty($_POST['hide_native']));
        UserFieldSettings::saveDisplaySets([(array)($_POST['set'] ?? [])]);
        LocalRedirect($APPLICATION->GetCurPageParam('saved=Y', ['saved']));
    } catch (Throwable $exception) {
        $saveError = $exception->getMessage();
    }
}

$allUfFields = UserFieldService::getAvailableFields();
$displaySets = UserFieldSettings::getDisplaySets();
$displaySet = $displaySets[0] ?? [
    'id' => 'default',
    'title' => UserFieldSettings::DEFAULT_TITLE,
    'projectIds' => [],
    'fields' => [],
];
$projectList = UserFieldService::getProjectList();
$hideNative = UserFieldSettings::isNativeBlockHidden();
$savedMap = taskUserFieldsBuildSavedMap($displaySet);
$selectedProjectIds = array_map('intval', (array)($displaySet['projectIds'] ?? []));
$hasFields = !empty($allUfFields);
$enabledFieldsCount = taskUserFieldsCountEnabledFields($displaySet);
$hasEnabled = $enabledFieldsCount > 0;

$APPLICATION->AddHeadString('<base href="/bitrix/admin/">');
$APPLICATION->SetTitle('Пользовательские поля в задачах Б24');
AssetLoader::addAdminAssets();
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if ($saveError !== '') {
    CAdminMessage::ShowMessage(['MESSAGE' => $saveError, 'TYPE' => 'ERROR']);
} elseif (($_GET['saved'] ?? '') === 'Y') {
    CAdminMessage::ShowMessage(['MESSAGE' => 'Настройки сохранены.', 'TYPE' => 'OK']);
}
?>

<div class="adm-detail-content-wrap">
    <div class="adm-detail-content">
        <form method="post" id="glad-task-uf-settings-form">
            <input type="hidden" name="action" value="save_task_uf_config">
            <?= bitrix_sessid_post() ?>
            <div id="glad-task-uf-generated-fields"></div>

            <details class="glad-task-uf-guide">
                <summary>Как добавить пользовательские поля в карточки задач</summary>
                <div class="glad-task-uf-steps">
                    <div class="glad-task-uf-step">
                        <div class="glad-task-uf-step-num <?= $hasFields ? 'done' : '' ?>">1</div>
                        <div>
                            <div class="glad-task-uf-step-title">Создайте пользовательские поля</div>
                            <div class="glad-task-uf-step-desc">
                                Перейдите в
                                <a href="/bitrix/admin/userfield_edit.php?lang=ru&ENTITY_ID=TASKS_TASK" target="_blank">
                                    Настройки&nbsp;→ Пользовательские поля
                                </a>
                                и создайте поля для сущности <b>TASKS_TASK</b>.
                            </div>
                        </div>
                    </div>
                    <div class="glad-task-uf-step">
                        <div class="glad-task-uf-step-num <?= $hasEnabled ? 'done' : '' ?>">2</div>
                        <div>
                            <div class="glad-task-uf-step-title">Настройте область</div>
                            <div class="glad-task-uf-step-desc">
                                Выберите проекты и отметьте поля, которые должны отображаться в карточке задачи.
                            </div>
                        </div>
                    </div>
                    <div class="glad-task-uf-step">
                        <div class="glad-task-uf-step-num <?= $hasEnabled ? 'done' : '' ?>">3</div>
                        <div>
                            <div class="glad-task-uf-step-title">Готово</div>
                            <div class="glad-task-uf-step-desc">
                                После сохранения откройте задачу в выбранном проекте — настроенная область
                                появится в карточке автоматически.
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <div class="glad-task-uf-info">
                <div class="glad-task-uf-info-row">
                    <span><b>Всего полей:</b> <?= count($allUfFields) ?></span>
                </div>

                <div class="glad-task-uf-info-row">
                    <span><b>Активных полей:</b> <?= $enabledFieldsCount ?></span>
                </div>

                <div class="glad-task-uf-info-row">
                    <label for="glad-task-uf-hide-native" class="glad-task-uf-checkbox-label">
                        <b>Скрыть вывод стандартного блока пользовательских полей в карточках задач Bitrix24</b>
                    </label>
                    <input type="checkbox" id="glad-task-uf-hide-native" name="hide_native" value="1" <?= $hideNative ? 'checked' : '' ?>>
                </div>
            </div>

            <?php if (!$hasFields): ?>
                <div class="glad-task-uf-no-fields">
                    Для сущности <b>TASKS_TASK</b> не зарегистрировано ни одного пользовательского поля.<br>
                    Создайте поля на шаге 1 — они сразу появятся в таблице ниже.
                </div>
            <?php else: ?>
                <div class="glad-task-uf-settings-panel"
                     id="glad-task-uf-set-panel"
                     data-set-id="<?= taskUserFieldsHtml($displaySet['id'] ?? 'default') ?>">
                    <div class="glad-task-uf-set-grid">
                        <label>
                            <span>Название области в карточке задачи</span>
                            <input type="text"
                                   class="glad-task-uf-title-input glad-task-uf-set-title"
                                   value="<?= taskUserFieldsHtml($displaySet['title'] ?? '') ?>"
                                   placeholder="оставьте пустым, чтобы скрыть заголовок">
                        </label>

                        <label>
                            <span>Проекты</span>
                            <select multiple class="glad-task-uf-project-select">
                                <?php foreach ($projectList as $project): ?>
                                    <option value="<?= (int)$project['id'] ?>"
                                        <?= in_array((int)$project['id'], $selectedProjectIds, true) ? 'selected' : '' ?>>
                                        <?= taskUserFieldsHtml('[' . (int)$project['id'] . '] ' . $project['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="glad-task-uf-hint">
                                Если проекты не выбраны, область будет показана во всех проектах.
                            </span>
                        </label>
                    </div>
                </div>

                <table class="glad-task-uf-table">
                    <thead>
                        <tr>
                            <th class="glad-task-uf-activity-column">Активность</th>
                            <th class="glad-task-uf-mute-column">Только чтение</th>
                            <th class="glad-task-uf-id-column">ID</th>
                            <th>Название</th>
                            <th>Символьный код</th>
                            <th>Тип</th>
                            <th>Название в карточке задачи</th>
                        </tr>
                    </thead>
                    <tbody class="glad-task-uf-tbody">
                        <?php foreach ($allUfFields as $ufName => $userField):
                            $saved = $savedMap[$ufName] ?? [];
                            $enabled = !empty($saved['enabled']);
                            $muted = !empty($saved['muted']);
                            $label = $saved['label'] ?? '';
                            $typeId = (string)$userField['USER_TYPE_ID'];
                            $systemLabel = UserFieldService::getFieldSystemLabel($userField, $ufName);
                            $supported = UserFieldService::isSupportedType($typeId);
                            $typeClass = $supported ? '' : ' glad-task-uf-type-unsupported';
                            $unsupportedReason = UserFieldService::getUnsupportedReason($typeId);
                            $editUrl = '/bitrix/admin/userfield_edit.php?lang='
                                . urlencode(LANGUAGE_ID)
                                . '&ID='
                                . (int)$userField['ID'];
                        ?>
                            <tr data-field-name="<?= taskUserFieldsHtml($ufName) ?>">
                                <td class="glad-task-uf-cell-center">
                                    <?php if ($supported): ?>
                                        <input type="checkbox"
                                               class="glad-task-uf-enabled"
                                               value="1"
                                            <?= $enabled ? 'checked' : '' ?>>
                                    <?php else: ?>
                                        <input type="checkbox"
                                               class="glad-task-uf-enabled glad-task-uf-disabled-control"
                                               value="0"
                                               disabled
                                               title="<?= taskUserFieldsHtml($unsupportedReason) ?>">
                                    <?php endif; ?>
                                </td>
                                <td class="glad-task-uf-cell-center">
                                    <?php if ($supported): ?>
                                        <input type="checkbox"
                                               class="glad-task-uf-muted"
                                               value="1"
                                            <?= $muted ? 'checked' : '' ?>>
                                    <?php else: ?>
                                        <input type="checkbox"
                                               class="glad-task-uf-muted glad-task-uf-disabled-control"
                                               value="0"
                                               disabled
                                               title="<?= taskUserFieldsHtml($unsupportedReason) ?>">
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="<?= taskUserFieldsHtml($editUrl) ?>" target="_blank">
                                        <?= (int)$userField['ID'] ?>
                                    </a>
                                </td>
                                <td><?= taskUserFieldsHtml($systemLabel) ?></td>

                                <td><code><?= taskUserFieldsHtml($ufName) ?></code></td>
                                <td>
                                    <span class="glad-task-uf-type-badge<?= $typeClass ?>"><?= taskUserFieldsHtml($typeId) ?></span>
                                    <?php if (!$supported): ?>
                                        <span class="glad-task-uf-unsupported-reason">
                                            — <?= taskUserFieldsHtml($unsupportedReason) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="text"
                                           class="glad-task-uf-label-input glad-task-uf-label<?= $supported ? '' : ' glad-task-uf-disabled-control' ?>"
                                           placeholder="<?= taskUserFieldsHtml($systemLabel) ?>"
                                           value="<?= taskUserFieldsHtml($label) ?>"
                                        <?= $supported ? '' : 'disabled' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="glad-task-uf-actions">
                    <input type="submit" class="adm-btn-save" id="glad-task-uf-save-btn" value="Сохранить">
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
