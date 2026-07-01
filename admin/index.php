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

$saveError = '';

if (!empty($_POST['action']) && check_bitrix_sessid()) {
    $action = (string)$_POST['action'];

    try {
        if ($action !== 'save_task_uf_config') {
            throw new InvalidArgumentException('Неизвестный action: ' . $action);
        }

        UserFieldSettings::saveBlockTitle(trim((string)($_POST['block_title'] ?? '')));
        UserFieldSettings::saveNativeBlockHidden(!empty($_POST['hide_native']));

        $rawFields = $_POST['fields'] ?? [];
        $fields = [];

        if (is_array($rawFields)) {
            foreach ($rawFields as $item) {
                $name = trim((string)($item['name'] ?? ''));

                if ($name === '' || strpos($name, 'UF_') !== 0) {
                    continue;
                }

                $fields[] = [
                    'name' => $name,
                    'label' => trim((string)($item['label'] ?? '')),
                    'enabled' => !empty($item['enabled']),
                ];
            }
        }

        UserFieldSettings::saveDisplayFields($fields);
        LocalRedirect($APPLICATION->GetCurPageParam('saved=Y', ['saved']));
    } catch (Throwable $exception) {
        $saveError = $exception->getMessage();
    }
}

$allUfFields = UserFieldService::getAvailableFields();
$displayFields = UserFieldSettings::getDisplayFields();
$blockTitle = UserFieldSettings::getBlockTitle();
$hideNative = UserFieldSettings::isNativeBlockHidden();
$savedMap = [];

foreach ($displayFields as $field) {
    $savedMap[$field['name']] = $field;
}

$hasFields = !empty($allUfFields);
$enabledFieldsCount = count(array_filter($displayFields, static fn ($field) => !empty($field['enabled'])));
$hasEnabled = $enabledFieldsCount > 0;

$APPLICATION->AddHeadString('<base href="/bitrix/admin/">');
$APPLICATION->SetTitle('Пользовательские поля в задачах Б24');
AssetLoader::addAdminStyles();
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
                            <div class="glad-task-uf-step-title">Включите нужные поля</div>
                            <div class="glad-task-uf-step-desc">
                                В таблице ниже поставьте галочку напротив каждого поля, которое должно
                                отображаться в карточке задачи.
                            </div>
                        </div>
                    </div>
                    <div class="glad-task-uf-step">
                        <div class="glad-task-uf-step-num <?= $hasEnabled ? 'done' : '' ?>">3</div>
                        <div>
                            <div class="glad-task-uf-step-title">Готово</div>
                            <div class="glad-task-uf-step-desc">
                                После сохранения откройте любую задачу — включённые поля с их значениями
                                появятся в карточке задачи автоматически.
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

        <div class="glad-task-uf-settings-panel">

            <div class="glad-task-uf-title-row">
                <label class="glad-task-uf-title-label">Название блока в карточке задачи:</label>
                <input type="text"
                       id="glad-task-uf-block-title"
                       name="block_title"
                       value="<?= taskUserFieldsHtml($blockTitle) ?>"
                       placeholder="оставьте пустым, чтобы скрыть заголовок"
                       class="glad-task-uf-title-input">
            </div>

        </div>

        <?php if (!$hasFields): ?>
            <div class="glad-task-uf-no-fields">
                Для сущности <b>TASKS_TASK</b> не зарегистрировано ни одного пользовательского поля.<br>
                Создайте поля на шаге 1 — они сразу появятся в таблице ниже.
            </div>
        <?php else: ?>
            <table class="glad-task-uf-table">
                <thead>
                    <tr>
                        <th class="glad-task-uf-activity-column">Активность</th>
                        <th>Символьный код</th>
                        <th>Тип</th>
                        <th>Название</th>
                        <th>Название в карточке задачи</th>
                    </tr>
                </thead>
                <tbody id="glad-task-uf-tbody">
                    <?php foreach ($allUfFields as $ufName => $userField):
                        $saved = $savedMap[$ufName] ?? [];
                        $enabled = !empty($saved['enabled']);
                        $label = $saved['label'] ?? '';
                        $typeId = $userField['USER_TYPE_ID'];
                        $systemLabel = UserFieldService::getFieldSystemLabel($userField, $ufName);
                        $supported = UserFieldService::isSupportedType((string) $typeId);
                        $typeClass = $supported ? '' : ' glad-task-uf-type-unsupported';
                        $unsupportedReason = UserFieldService::getUnsupportedReason((string) $typeId);
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
                            <td><code><?= taskUserFieldsHtml($ufName) ?></code></td>
                            <td>
                                <span class="glad-task-uf-type-badge<?= $typeClass ?>"><?= taskUserFieldsHtml($typeId) ?></span>
                                <?php if (!$supported): ?>
                                    <span class="glad-task-uf-unsupported-reason">
                                        — <?= taskUserFieldsHtml($unsupportedReason) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= taskUserFieldsHtml($systemLabel) ?></td>
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

<script>
(function () {
    'use strict';

    const form = document.getElementById('glad-task-uf-settings-form');
    const saveButton = document.getElementById('glad-task-uf-save-btn');
    const tbody = document.getElementById('glad-task-uf-tbody');
    const generatedFields = document.getElementById('glad-task-uf-generated-fields');
    if (!form || !saveButton || !tbody || !generatedFields) {
        return;
    }

    /**
     * Создает скрытое поле формы.
     *
     * @param {string} name
     * @param {string|number} value
     *
     * @return {void}
     */
    function appendHiddenField(name, value) {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = name;
        input.value = String(value);
        input.className = 'glad-task-uf-generated-field';
        generatedFields.appendChild(input);
    }

    form.addEventListener('submit', function () {
        const rows = document.querySelectorAll('#glad-task-uf-tbody tr[data-field-name]');
        const fields = [];

        generatedFields.innerHTML = '';

        rows.forEach(function (row) {
            const enabled = row.querySelector('.glad-task-uf-enabled');
            const label = row.querySelector('.glad-task-uf-label');

            fields.push({
                name: row.dataset.fieldName,
                label: label ? label.value.trim() : '',
                enabled: enabled && enabled.checked ? 1 : 0
            });
        });

        saveButton.disabled = true;

        fields.forEach(function (field, index) {
            appendHiddenField('fields[' + index + '][name]', field.name);
            appendHiddenField('fields[' + index + '][label]', field.label);
            appendHiddenField('fields[' + index + '][enabled]', field.enabled);
        });
    });
})();
</script>

<script>
BX.ready(function () {
    const menuItem = document.getElementById('global_menu_taskufields');

    if (menuItem && !menuItem.classList.contains('adm-main-menu-item-active')) {
        BX.adminMenu.GlobalMenuClick('taskufields');
    }

    const link = document.querySelector(
        'a.adm-submenu-item-name-link[href="/local/modules/gladushenko.taskuserfields/admin/index.php"]'
    );

    if (link) {
        const block = link.closest('.adm-sub-submenu-block');

        if (block) {
            block.classList.add('adm-submenu-item-active');
        }
    }
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
