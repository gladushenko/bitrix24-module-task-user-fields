/**
 * Управляет настройками области пользовательских полей в админке модуля.
 */
(function (window, document) {
    'use strict';

    /**
     * Запускает логику страницы настроек.
     *
     * @return {void}
     */
    function init() {
        const form = document.getElementById('glad-task-uf-settings-form');

        if (!form) {
            return;
        }

        const panel = document.getElementById('glad-task-uf-set-panel');
        const generatedFields = document.getElementById('glad-task-uf-generated-fields');
        const saveButton = document.getElementById('glad-task-uf-save-btn');

        if (!panel || !generatedFields) {
            return;
        }

        form.addEventListener('submit', function () {
            generatedFields.innerHTML = '';
            appendSetToFormData(generatedFields, panel);

            if (saveButton) {
                saveButton.disabled = true;
            }
        });

        let element = document.getElementById('global_menu_taskufields');
        if (element && !element.classList.contains('adm-main-menu-item-active')) {
            BX.adminMenu.GlobalMenuClick('taskufields');
        }

        let link = document.querySelector('a.adm-submenu-item-name-link[href="/local/modules/gladushenko.taskuserfields/admin/index.php"]');
        if (link) {
            let block = link.closest('.adm-sub-submenu-block');
            if (block) {
                block.classList.add('adm-submenu-item-active');
            }
        }
    }

    /**
     * Добавляет область в скрытые поля формы.
     *
     * @param {HTMLElement} generatedFields
     * @param {HTMLElement} panel
     *
     * @return {void}
     */
    function appendSetToFormData(generatedFields, panel) {
        const setId = panel.getAttribute('data-set-id') || 'default';
        const titleInput = panel.querySelector('.glad-task-uf-set-title');
        const projectSelect = panel.querySelector('.glad-task-uf-project-select');
        const rows = Array.prototype.slice.call(document.querySelectorAll('tr[data-field-name]'));

        appendHidden(generatedFields, 'set[id]', setId);
        appendHidden(generatedFields, 'set[title]', titleInput ? titleInput.value : '');

        if (projectSelect) {
            Array.prototype.forEach.call(projectSelect.selectedOptions, function (option, projectIndex) {
                appendHidden(generatedFields, 'set[projectIds][' + projectIndex + ']', option.value);
            });
        }

        rows.forEach(function (row, fieldIndex) {
            const fieldName = row.getAttribute('data-field-name') || '';
            const enabledInput = row.querySelector('.glad-task-uf-enabled');
            const mutedInput = row.querySelector('.glad-task-uf-muted');
            const labelInput = row.querySelector('.glad-task-uf-label');
            const enabled = enabledInput && !enabledInput.disabled && enabledInput.checked ? '1' : '0';
            const muted = mutedInput && !mutedInput.disabled && mutedInput.checked ? '1' : '0';
            const label = labelInput && !labelInput.disabled ? labelInput.value : '';

            appendHidden(generatedFields, 'set[fields][' + fieldIndex + '][name]', fieldName);
            appendHidden(generatedFields, 'set[fields][' + fieldIndex + '][enabled]', enabled);
            appendHidden(generatedFields, 'set[fields][' + fieldIndex + '][muted]', muted);
            appendHidden(generatedFields, 'set[fields][' + fieldIndex + '][label]', label);
        });
    }

    /**
     * Добавляет скрытое поле в форму.
     *
     * @param {HTMLElement} container
     * @param {string} name
     * @param {string} value
     *
     * @return {void}
     */
    function appendHidden(container, name, value) {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = name;
        input.value = value;
        container.appendChild(input);
    }

    if (window.BX && BX.ready) {
        BX.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(window, document);
