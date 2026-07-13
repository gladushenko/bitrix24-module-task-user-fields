/**
 * Отображает и редактирует пользовательские поля в карточке задачи.
 */
(function (window) {
    'use strict';

    window.TaskUserFieldsModule = window.TaskUserFieldsModule || {};

    class TaskUserFields {
        /**
         * Создает менеджер пользовательских полей задачи.
         *
         * @param {object} config
         */
        constructor(config) {
            this.config = config || {};
            this.sets = this.config.sets || [];
            this.fields = this.config.fields || [];
            this.actions = this.config.actions || {};
            this.blockTitle = this.config.title !== undefined ? this.config.title : 'Дополнительные поля';
            this.hideNative = !!this.config.hideNative;
            this.cardOrder = typeof this.config.cardOrder !== 'undefined' ? parseInt(this.config.cardOrder, 10) : 0;

            this.blockId = 'glad-task-uf-block';
            this.taskUrlPattern = /\/tasks\/task\/view\/(\d+)\//;
            this.pendingTaskIds = {};
            this.cardBlockObserver = null;
            this.activeEditCancel = null;
        }

        /**
         * Запускает отображение пользовательских полей.
         *
         * @return {void}
         */
        init() {
            if (!this.hasRenderableConfig() || !window.BX || !BX.Event || !BX.Event.EventEmitter) {
                return;
            }

            BX.Event.EventEmitter.subscribe('tasks:full-card:init', this.onCardInit.bind(this));

            BX.ready(() => {
                if (document.querySelector('.tasks-full-card')) {
                    this.onCardInit();
                }
            });
        }

        /**
         * Проверяет, есть ли настроенные области для отображения.
         *
         * @return {boolean}
         */
        hasRenderableConfig() {
            if (this.sets.length) {
                return this.sets.some(function (set) {
                    return set && set.fields && set.fields.length;
                });
            }

            return this.fields.length > 0;
        }

        /**
         * Возвращает область, подходящую для проекта задачи.
         *
         * @param {number} groupId
         *
         * @return {object|null}
         */
        getActiveSet(groupId) {
            if (!this.sets.length) {
                return this.fields.length ? {
                    id: 'default',
                    title: this.blockTitle,
                    projectIds: [],
                    fields: this.fields,
                } : null;
            }

            const projectId = parseInt(groupId, 10) || 0;
            let fallbackSet = null;

            for (let i = 0; i < this.sets.length; i++) {
                const set = this.sets[i];
                const projectIds = (set.projectIds || []).map(function (id) {
                    return parseInt(id, 10);
                }).filter(function (id) {
                    return id > 0;
                });

                if (!fallbackSet && !projectIds.length) {
                    fallbackSet = set;
                }

                if (projectId > 0 && projectIds.indexOf(projectId) !== -1) {
                    return set;
                }
            }

            return fallbackSet;
        }

        /**
         * Возвращает ID текущей задачи из DOM или адреса страницы.
         *
         * @return {number}
         */
        getTaskId() {
            const element = document.querySelector('[data-task-field-id="userFields"]');

            if (element) {
                const id = parseInt(element.getAttribute('data-task-id'), 10);

                if (id > 0) {
                    return id;
                }
            }

            const match = window.location.href.match(this.taskUrlPattern);

            return match ? parseInt(match[1], 10) : 0;
        }

        /**
         * Возвращает контейнер карточки задачи для вставки блока.
         *
         * @return {Element|null}
         */
        findContainer() {
            return document.querySelector('.tasks-full-card-fields')
                || document.querySelector('.tasks-full-card-content')
                || document.querySelector('.tasks-full-card-main');
        }

        /**
         * Создает DOM-элемент для отображения значения поля.
         *
         * @param {object} field
         * @param {object} entry
         *
         * @return {HTMLElement}
         */
        createDisplayElement(field, entry) {
            const raw = entry.value;
            const display = entry.display;

            if (field.type === 'boolean') {
                const checked = raw && raw !== '0' && raw !== false;
                const wrap = document.createElement('span');
                const checkbox = document.createElement('input');
                const text = document.createElement('span');

                wrap.style.cssText = 'display: inline-flex; align-items: center; gap: 5px;';
                checkbox.type = 'checkbox';
                checkbox.checked = !!checked;
                checkbox.disabled = true;
                checkbox.style.cssText = 'pointer-events: none; margin: 0;';
                text.className = 'ui-text --md';
                text.textContent = checked ? 'Да' : 'Нет';

                wrap.appendChild(checkbox);
                wrap.appendChild(text);

                return wrap;
            }

            const stringValue = typeof raw === 'string' ? raw : (typeof display === 'string' ? display : '');

            if (stringValue) {
                const match = stringValue.match(this.taskUrlPattern);

                if (match) {
                    const link = document.createElement('a');
                    link.href = stringValue;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.className = 'ui-text --md';
                    link.style.cssText = 'color: #2979ff; text-decoration: none;';
                    link.textContent = 'Задача №' + match[1];
                    link.addEventListener('click', function (event) {
                        event.stopPropagation();
                    });

                    return link;
                }
            }

            const span = document.createElement('span');
            span.className = 'ui-text --md';
            span.textContent = display || '';

            if (field.type === 'string' && this.getFieldRows(field) > 1) {
                span.style.cssText = 'white-space: pre-wrap; max-width: 520px;';
            }

            return span;
        }

        /**
         * Создает элемент редактирования значения поля.
         *
         * @param {object} field
         * @param {object} entry
         *
         * @return {{container: HTMLElement, control: HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement}}
         */
        createEditWidget(field, entry) {
            const raw = entry.value;

            if (field.type === 'enumeration') {
                const wrapper = this.createSelectWrapper();
                const select = document.createElement('select');
                const empty = document.createElement('option');
                const rawIds = Array.isArray(raw) ? raw.map(String) : [String(raw || '')];

                select.className = 'ui-ctl-element';
                empty.value = '';
                empty.textContent = '— не выбрано —';
                select.appendChild(empty);

                (entry.options || []).forEach(function (option) {
                    const item = document.createElement('option');
                    item.value = option.id;
                    item.textContent = option.value;
                    item.selected = rawIds.indexOf(String(option.id)) !== -1;
                    select.appendChild(item);
                });

                wrapper.appendChild(select);

                return {
                    container: wrapper,
                    control: select,
                };
            }

            if (field.type === 'boolean') {
                const wrapper = this.createSelectWrapper();
                const select = document.createElement('select');
                const checked = raw && raw !== '0' && raw !== false;

                select.className = 'ui-ctl-element';

                [['', '— не выбрано —'], ['1', 'Да'], ['0', 'Нет']].forEach(function (pair) {
                    const option = document.createElement('option');
                    option.value = pair[0];
                    option.textContent = pair[1];

                    if (pair[0] === '1' && checked) {
                        option.selected = true;
                    }

                    if (pair[0] === '0' && !checked && raw !== '' && raw !== null && raw !== undefined) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });

                wrapper.appendChild(select);

                return {
                    container: wrapper,
                    control: select,
                };
            }

            if (field.type === 'string' && this.getFieldRows(field) > 1) {
                const wrapper = this.createTextareaWrapper();
                const textarea = document.createElement('textarea');

                textarea.className = 'ui-ctl-element';
                textarea.rows = this.getFieldRows(field);
                textarea.value = Array.isArray(raw) ? raw.join('\n') : (raw || '');
                wrapper.appendChild(textarea);

                return {
                    container: wrapper,
                    control: textarea,
                };
            }

            const wrapper = this.createInputWrapper();
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'ui-ctl-element';
            input.value = Array.isArray(raw) ? raw.join(', ') : (raw || '');
            wrapper.appendChild(input);

            return {
                container: wrapper,
                control: input,
            };
        }

        /**
         * Возвращает количество строк текстового поля.
         *
         * @param {object} field
         *
         * @return {number}
         */
        getFieldRows(field) {
            const rows = field && field.settings ? parseInt(field.settings.rows, 10) : 1;

            return rows > 1 ? rows : 1;
        }

        /**
         * Создает стандартную Б24-обертку для текстового поля.
         *
         * @return {HTMLElement}
         */
        createInputWrapper() {
            const wrapper = document.createElement('div');
            wrapper.className = 'ui-ctl ui-ctl-textbox ui-ctl-xs';
            wrapper.style.cssText = 'min-width: 160px; width: auto;';

            return wrapper;
        }

        /**
         * Создает стандартную Б24-обертку для многострочного текстового поля.
         *
         * @return {HTMLElement}
         */
        createTextareaWrapper() {
            const wrapper = document.createElement('div');
            wrapper.className = 'ui-ctl ui-ctl-textarea';
            wrapper.style.cssText = 'min-width: 260px; width: min(100%, 520px);';

            return wrapper;
        }

        /**
         * Создает стандартную Б24-обертку для выпадающего списка.
         *
         * @return {HTMLElement}
         */
        createSelectWrapper() {
            const wrapper = document.createElement('div');
            const icon = document.createElement('div');

            wrapper.className = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-xs';
            wrapper.style.cssText = 'min-width: 160px; width: auto;';
            icon.className = 'ui-ctl-after ui-ctl-icon-angle';
            wrapper.appendChild(icon);

            return wrapper;
        }

        /**
         * Сохраняет значение пользовательского поля задачи.
         *
         * @param {number} taskId
         * @param {string} fieldName
         * @param {string} value
         * @param {Function} onSuccess
         * @param {Function} onError
         *
         * @return {void}
         */
        saveField(taskId, fieldName, value, onSuccess, onError) {
            if (!this.actions.saveTaskUf) {
                onError('Не настроено действие сохранения');
                return;
            }

            BX.ajax.runAction(
                this.actions.saveTaskUf,
                { data: { taskId: taskId, fieldName: fieldName, value: value } }
            ).then(function (response) {
                if (response && response.data && response.data.saved) {
                    onSuccess();
                    return;
                }

                onError('Не удалось сохранить');
            }).catch(function (error) {
                const message = (error && error.errors && error.errors[0] && error.errors[0].message) || 'Ошибка сети';
                onError(message);
            });
        }

        /**
         * Создает строку пользовательского поля.
         *
         * @param {object} field
         * @param {object} entry
         * @param {number} taskId
         *
         * @return {HTMLElement}
         */
        buildFieldRow(field, entry, taskId) {
            const row = document.createElement('div');
            const label = document.createElement('span');
            const valueWrap = document.createElement('div');
            const isMuted = Boolean(field.muted);
            const editButton = isMuted ? null : this.createEditButton();
            let currentEntry = { value: entry.value, display: entry.display, options: entry.options };
            let displayElement = this.createDisplayElement(field, currentEntry);

            row.className = 'tasks-user-field print-no-border --string';
            if (isMuted) {
                row.classList.add('glad-task-uf-muted-row');
            }

            label.className = 'ui-text --xs tasks-user-field-title';
            label.textContent = field.label;

            valueWrap.className = 'tasks-user-field-value';
            valueWrap.style.cssText = 'display: flex; align-items: center; gap: 8px; flex: 1; width: 100%;';
            valueWrap.appendChild(displayElement);
            if (editButton) {
                valueWrap.appendChild(editButton);
            }

            const showEditButton = () => {
                if (!editButton) {
                    return;
                }

                if (!row.classList.contains('--editing')) {
                    editButton.style.opacity = '1';
                    editButton.style.pointerEvents = 'auto';
                }
            };

            const hideEditButton = () => {
                if (!editButton) {
                    return;
                }

                editButton.style.opacity = '0';
                editButton.style.pointerEvents = 'none';
            };

            const startEdit = () => {
                if (isMuted || row.classList.contains('--editing')) {
                    return;
                }

                this.closeActiveEdit();
                row.classList.add('--editing');
                hideEditButton();
                valueWrap.removeChild(displayElement);
                valueWrap.removeChild(editButton);

                const editWidget = this.createEditWidget(field, currentEntry);
                const widget = editWidget.control;
                const widgetContainer = editWidget.container;
                const buttonWrap = document.createElement('span');
                const saveButton = document.createElement('button');
                const cancelButton = document.createElement('button');

                widgetContainer.style.maxWidth = '100%';
                widgetContainer.style.flex = '0 1 auto';
                valueWrap.appendChild(widgetContainer);
                buttonWrap.style.cssText = 'display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: auto;';

                saveButton.type = 'button';
                saveButton.className = 'ui-btn ui-btn-xs ui-btn-success';
                saveButton.textContent = 'Сохранить';
                saveButton.title = 'Сохранить (Enter)';

                cancelButton.type = 'button';
                cancelButton.className = 'ui-btn ui-btn-xs ui-btn-light-border';
                cancelButton.textContent = 'Отменить';
                cancelButton.title = 'Отмена (Esc)';

                buttonWrap.appendChild(saveButton);
                buttonWrap.appendChild(cancelButton);
                valueWrap.appendChild(buttonWrap);

                const exitEdit = (newEntry) => {
                    row.classList.remove('--editing');
                    valueWrap.removeChild(widgetContainer);
                    valueWrap.removeChild(buttonWrap);

                    if (this.activeEditCancel === exitEdit) {
                        this.activeEditCancel = null;
                    }

                    if (newEntry) {
                        currentEntry = newEntry;
                    }

                    displayElement = this.createDisplayElement(field, currentEntry);
                    valueWrap.appendChild(displayElement);
                    if (editButton) {
                        valueWrap.appendChild(editButton);
                    }
                };

                this.activeEditCancel = exitEdit;

                const doSave = () => {
                    const newValue = widget.value !== undefined ? widget.value : '';
                    saveButton.disabled = true;
                    saveButton.textContent = 'Сохранение...';

                    this.saveField(
                        taskId,
                        field.name,
                        newValue,
                        () => {
                            if (!this.actions.getTaskUf) {
                                exitEdit(null);
                                return;
                            }

                            BX.ajax.runAction(
                                this.actions.getTaskUf,
                                { data: { taskId: taskId } }
                            ).then((response) => {
                                const responseData = response && response.data;
                                const freshFields = responseData && responseData.fields ? responseData.fields : responseData;
                                const freshEntry = freshFields && freshFields[field.name];
                                exitEdit(freshEntry || null);
                            }).catch(function () {
                                exitEdit(null);
                            });
                        },
                        (errorMessage) => {
                            saveButton.disabled = false;
                            saveButton.textContent = 'Сохранить';
                            this.showRowError(row, 'Ошибка сохранения');
                            this.showGlobalError(errorMessage || 'Ошибка сохранения');
                        }
                    );
                };

                cancelButton.addEventListener('click', function (event) {
                    event.stopPropagation();
                    exitEdit(null);
                });

                saveButton.addEventListener('click', function (event) {
                    event.stopPropagation();
                    doSave();
                });

                widget.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        exitEdit(null);
                    }

                    if (
                        event.key === 'Enter'
                        && widget.tagName !== 'TEXTAREA'
                        && field.type !== 'enumeration'
                        && field.type !== 'boolean'
                    ) {
                        event.preventDefault();
                        doSave();
                    }
                });

                if (typeof widget.focus === 'function') {
                    widget.focus();
                }
            };

            row.addEventListener('mouseenter', showEditButton);
            row.addEventListener('mouseleave', hideEditButton);

            if (editButton) {
                editButton.addEventListener('click', function (event) {
                    event.stopPropagation();
                    startEdit();
                });
            }

            row.appendChild(label);
            row.appendChild(valueWrap);

            return row;
        }

        /**
         * Закрывает текущее редактируемое поле без сохранения.
         *
         * @return {void}
         */
        closeActiveEdit() {
            if (typeof this.activeEditCancel !== 'function') {
                return;
            }

            const cancel = this.activeEditCancel;
            this.activeEditCancel = null;
            cancel(null);
        }

        /**
         * Создает кнопку перехода к редактированию поля.
         *
         * @return {HTMLButtonElement}
         */
        createEditButton() {
            const editButton = document.createElement('button');

            editButton.type = 'button';
            editButton.className = 'ui-btn ui-btn-xs ui-btn-light-border';
            editButton.textContent = 'Редактировать';
            editButton.title = 'Редактировать поле';
            editButton.style.cssText = [
                'margin-left: auto',
                'flex-shrink: 0',
                'opacity: 0',
                'pointer-events: none',
                'transition: opacity .18s ease'
            ].join(';');

            return editButton;
        }

        /**
         * Показывает ошибку сохранения в строке поля.
         *
         * @param {HTMLElement} row
         * @param {string} message
         *
         * @return {void}
         */
        showRowError(row, message) {
            const errorClass = 'glad-task-uf-row-err';
            const existing = row.querySelector('.' + errorClass);

            if (existing) {
                existing.parentNode.removeChild(existing);
            }

            const element = document.createElement('span');
            element.className = errorClass;
            element.style.cssText = 'display:block;color:#c62828;font-size:11px;margin-top:2px;';
            element.textContent = message;
            row.appendChild(element);

            setTimeout(function () {
                if (element.parentNode) {
                    element.parentNode.removeChild(element);
                }
            }, 4000);
        }

        /**
         * Показывает общую ошибку сохранения задачи.
         *
         * @param {string} message
         *
         * @return {void}
         */
        showGlobalError(message) {
            if (
                window.TaskUserFieldsModule
                && typeof window.TaskUserFieldsModule.showTaskError === 'function'
            ) {
                window.TaskUserFieldsModule.showTaskError(message);
            }
        }

        /**
         * Скрывает нативный блок пользовательских полей задачи.
         *
         * @return {void}
         */
        applyHideNative() {
            if (!this.hideNative || document.getElementById('glad-task-uf-hide-native-style')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'glad-task-uf-hide-native-style';
            style.textContent =
                '.tasks-field-user-fields:not(.glad-task-uf-inner) > *:not(.tasks-field-user-fields-title) {' +
                '    display: none !important;' +
                '}';
            document.head.appendChild(style);
        }

        /**
         * Вставляет элемент на заданную позицию.
         *
         * @param {Element} container
         * @param {Element} node
         * @param {number} position
         *
         * @return {void}
         */
        insertAtPosition(container, node, position) {
            const siblings = Array.prototype.filter.call(container.children, function (child) {
                return child !== node;
            });

            if (position > 0 && position <= siblings.length) {
                container.insertBefore(node, siblings[position - 1]);
                return;
            }

            container.appendChild(node);
        }

        /**
         * Защищает вставленный элемент от удаления при Vue-патчинге.
         *
         * @param {Element} container
         * @param {Element} node
         * @param {number} position
         *
         * @return {MutationObserver}
         */
        makeProtector(container, node, position) {
            const observer = new MutationObserver((mutations) => {
                let removed = false;

                for (let i = 0; i < mutations.length; i++) {
                    const removedNodes = mutations[i].removedNodes;

                    for (let j = 0; j < removedNodes.length; j++) {
                        if (removedNodes[j] === node) {
                            removed = true;
                            break;
                        }
                    }

                    if (removed) {
                        break;
                    }
                }

                if (removed) {
                    observer.disconnect();
                    this.insertAtPosition(container, node, position);
                    observer.observe(container, { childList: true });
                }
            });

            observer.observe(container, { childList: true });

            return observer;
        }

        /**
         * Рендерит блок пользовательских полей в карточке задачи.
         *
         * @param {Element} container
         * @param {number} taskId
         * @param {object} data
         * @param {object} activeSet
         *
         * @return {void}
         */
        renderBlock(container, taskId, data, activeSet) {
            const oldBlock = document.getElementById(this.blockId);

            if (oldBlock) {
                oldBlock.parentNode.removeChild(oldBlock);
            }

            if (!activeSet || !activeSet.fields || !activeSet.fields.length) {
                return;
            }

            const block = document.createElement('div');
            const inner = document.createElement('div');
            const grid = document.createElement('div');
            const title = activeSet.title !== undefined ? activeSet.title : this.blockTitle;

            block.id = this.blockId;
            block.setAttribute('data-task-user-fields-task-id', taskId);
            block.className = 'tasks-full-card-field-container print-before-divider-accent --custom';
            inner.className = 'tasks-field-user-fields print-no-box-shadow glad-task-uf-inner';

            if (title) {
                const titleRow = document.createElement('div');
                const titleText = document.createElement('span');

                titleRow.className = 'tasks-field-user-fields-title';
                titleRow.style.cssText = 'grid-column: 1 / -1;';
                titleText.className = 'ui-text --md --accent';
                titleText.textContent = title;
                titleRow.appendChild(titleText);
                inner.appendChild(titleRow);
            }

            grid.style.cssText = 'display: grid; grid-template-columns: 1fr; gap: 0;';

            activeSet.fields.forEach((field) => {
                const entry = data[field.name] || { value: '', display: '', options: null };

                grid.appendChild(this.buildFieldRow(field, entry, taskId));
            });

            inner.appendChild(grid);
            block.appendChild(inner);

            if (this.cardBlockObserver) {
                this.cardBlockObserver.disconnect();
                this.cardBlockObserver = null;
            }

            this.insertAtPosition(container, block, this.cardOrder);
            this.cardBlockObserver = this.makeProtector(container, block, this.cardOrder);
            this.applyHideNative();
        }

        /**
         * Загружает пользовательские поля и запускает рендер.
         *
         * @param {number} taskId
         *
         * @return {void}
         */
        loadAndRender(taskId) {
            if (this.pendingTaskIds[taskId]) {
                return;
            }

            this.pendingTaskIds[taskId] = true;

            if (!this.actions.getTaskUf) {
                delete this.pendingTaskIds[taskId];
                return;
            }

            BX.ajax.runAction(
                this.actions.getTaskUf,
                { data: { taskId: taskId } }
            ).then((response) => {
                delete this.pendingTaskIds[taskId];

                const responseData = response && response.data;
                const fields = responseData && responseData.fields ? responseData.fields : responseData;
                const task = responseData && responseData.task ? responseData.task : {};
                const activeSet = this.getActiveSet(task.groupId || 0);

                if (!fields || typeof fields !== 'object') {
                    return;
                }

                const container = this.findContainer();

                if (!container) {
                    return;
                }

                if (!activeSet) {
                    const oldBlock = document.getElementById(this.blockId);

                    if (oldBlock) {
                        oldBlock.parentNode.removeChild(oldBlock);
                    }

                    return;
                }

                this.renderBlock(container, taskId, fields, activeSet);
            }).catch(() => {
                delete this.pendingTaskIds[taskId];
            });
        }

        /**
         * Обрабатывает инициализацию полной карточки задачи.
         *
         * @return {void}
         */
        onCardInit() {
            const taskId = this.getTaskId();

            if (!taskId) {
                return;
            }

            const initKey = 'init_' + taskId;

            if (this.pendingTaskIds[initKey]) {
                return;
            }

            this.pendingTaskIds[initKey] = true;
            let done = false;

            const doRender = () => {
                if (done) {
                    return;
                }

                done = true;
                delete this.pendingTaskIds[initKey];
                this.disconnectObservers();

                const existing = document.getElementById(this.blockId);

                if (existing && parseInt(existing.getAttribute('data-task-user-fields-task-id'), 10) === taskId) {
                    return;
                }

                if (existing) {
                    existing.parentNode.removeChild(existing);
                }

                this.loadAndRender(taskId);
            };

            if (document.querySelector('.tasks-full-card-fields')) {
                doRender();
                return;
            }

            const root = document.querySelector('.tasks-full-card-main, .tasks-full-card');

            if (!root) {
                delete this.pendingTaskIds[initKey];
                return;
            }

            const observer = new MutationObserver(function () {
                if (document.querySelector('.tasks-full-card-fields')) {
                    observer.disconnect();
                    doRender();
                }
            });

            observer.observe(root, { childList: true, subtree: true });

            setTimeout(function () {
                observer.disconnect();
                doRender();
            }, 3000);
        }

        /**
         * Отключает наблюдатели текущей карточки.
         *
         * @return {void}
         */
        disconnectObservers() {
            if (this.cardBlockObserver) {
                this.cardBlockObserver.disconnect();
                this.cardBlockObserver = null;
            }
        }
    }

    window.TaskUserFieldsModule.TaskUserFields = TaskUserFields;
})(window);
