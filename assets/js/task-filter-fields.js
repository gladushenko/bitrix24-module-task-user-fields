/**
 * Преобразует списочные пользовательские поля в фильтре задач в списочные контролы.
 */
(function (window, document) {
    'use strict';

    window.TaskUserFieldsModule = window.TaskUserFieldsModule || {};

    class TaskFilterFields {
        /**
         * Создает менеджер пользовательских полей фильтра задач.
         *
         * @param {object} config
         */
        constructor(config) {
            this.config = config || {};
            this.filterConfig = this.config.filter || {};
            this.sets = this.filterConfig.sets || [];
            this.fields = this.filterConfig.fields || [];
            this.observer = null;
            this.patchTimeout = null;
        }

        /**
         * Запускает преобразование пользовательских полей фильтра.
         *
         * @return {void}
         */
        init() {
            if (!this.hasFilterFields()) {
                return;
            }

            const run = () => {
                this.patch();
                this.observe();
            };

            if (window.BX && BX.ready) {
                BX.ready(run);
                return;
            }

            document.addEventListener('DOMContentLoaded', run);
        }

        /**
         * Проверяет, есть ли настроенные списочные поля фильтра.
         *
         * @return {boolean}
         */
        hasFilterFields() {
            if (this.sets.length) {
                return this.sets.some(function (set) {
                    return set && set.fields && set.fields.length;
                });
            }

            return this.fields.length > 0;
        }

        /**
         * Наблюдает за пересозданием DOM фильтра.
         *
         * @return {void}
         */
        observe() {
            if (this.observer || !document.body || !window.MutationObserver) {
                return;
            }

            this.observer = new MutationObserver(() => {
                window.clearTimeout(this.patchTimeout);
                this.patchTimeout = window.setTimeout(() => this.patch(), 80);
            });

            this.observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }

        /**
         * Преобразует поля фильтра на странице.
         *
         * @return {void}
         */
        patch() {
            const fields = this.getActiveFields();

            if (!fields.length) {
                return;
            }

            fields.forEach((field) => {
                const containers = document.querySelectorAll(
                    '.main-ui-filter-wield-with-label[data-name="' + this.escapeAttribute(field.name) + '"]'
                );

                Array.prototype.forEach.call(containers, (container) => {
                    this.patchContainer(container, field);
                });
            });

            this.patchFilterInstances(fields);
        }

        /**
         * Возвращает поля фильтра для текущего проекта.
         *
         * @return {Array}
         */
        getActiveFields() {
            if (!this.sets.length) {
                return this.fields;
            }

            const projectId = this.getCurrentProjectId();
            let fallbackFields = [];

            for (let i = 0; i < this.sets.length; i++) {
                const set = this.sets[i];
                const projectIds = (set.projectIds || []).map(function (id) {
                    return parseInt(id, 10);
                }).filter(function (id) {
                    return id > 0;
                });

                if (!fallbackFields.length && !projectIds.length) {
                    fallbackFields = set.fields || [];
                }

                if (projectId > 0 && projectIds.indexOf(projectId) !== -1) {
                    return set.fields || [];
                }
            }

            return fallbackFields;
        }

        /**
         * Возвращает ID проекта из адреса страницы.
         *
         * @return {number}
         */
        getCurrentProjectId() {
            const match = window.location.pathname.match(/\/workgroups\/group\/(\d+)\//i);

            return match ? parseInt(match[1], 10) || 0 : 0;
        }

        /**
         * Преобразует одно поле фильтра.
         *
         * @param {HTMLElement} container
         * @param {object} field
         *
         * @return {void}
         */
        patchContainer(container, field) {
            if (!container || container.getAttribute('data-glad-task-uf-filter-patched') === 'Y') {
                return;
            }

            if (container.getAttribute('data-type') !== 'STRING') {
                return;
            }

            const input = container.querySelector('input.main-ui-control-string[name="' + this.escapeAttribute(field.name) + '"]');

            if (!input) {
                return;
            }

            const selectedValues = this.resolveSelectedValues(input.value, field.items || []);
            const control = this.createMultiSelectControl(field, selectedValues);

            container.setAttribute('data-type', 'MULTI_SELECT');
            container.setAttribute('data-glad-task-uf-filter-patched', 'Y');
            input.parentNode.replaceChild(control, input);
            this.removeStringDeleteControls(container);
            this.initializeControl(control);
        }

        /**
         * Создает DOM-контрол MULTI_SELECT.
         *
         * @param {object} field
         * @param {Array} selectedValues
         *
         * @return {HTMLElement}
         */
        createMultiSelectControl(field, selectedValues) {
            const control = document.createElement('div');
            const squareContainer = document.createElement('span');
            const searchContainer = document.createElement('span');
            const searchInput = document.createElement('input');
            const deleteContainer = document.createElement('span');
            const deleteItem = document.createElement('div');

            control.setAttribute('data-name', field.name);
            control.setAttribute('data-params', JSON.stringify({ isMulti: true }));
            control.setAttribute('data-items', JSON.stringify(field.items || []));
            control.setAttribute('data-value', JSON.stringify(selectedValues));
            control.className = 'main-ui-control main-ui-multi-select';

            squareContainer.className = 'main-ui-square-container';
            searchContainer.className = 'main-ui-square-search';
            searchInput.type = 'text';
            searchInput.className = 'main-ui-square-search-item';
            deleteContainer.className = 'main-ui-hide main-ui-control-value-delete';
            deleteItem.className = 'main-ui-control-value-delete-item';

            searchContainer.appendChild(searchInput);
            deleteContainer.appendChild(deleteItem);
            control.appendChild(squareContainer);
            control.appendChild(searchContainer);
            control.appendChild(deleteContainer);

            return control;
        }

        /**
         * Удаляет старую кнопку очистки строкового поля.
         *
         * @param {HTMLElement} container
         *
         * @return {void}
         */
        removeStringDeleteControls(container) {
            const deleteControls = Array.prototype.filter.call(container.children, function (child) {
                return child.classList.contains('main-ui-control-value-delete');
            });

            Array.prototype.forEach.call(deleteControls, function (deleteControl) {
                deleteControl.parentNode.removeChild(deleteControl);
            });
        }

        /**
         * Инициализирует контрол средствами Битрикс, если API доступен.
         *
         * @param {HTMLElement} control
         *
         * @return {void}
         */
        initializeControl(control) {
            if (
                window.BX
                && BX.Main
                && BX.Main.ui
                && BX.Main.ui.Factory
                && typeof BX.Main.ui.Factory.create === 'function'
            ) {
                try {
                    BX.Main.ui.Factory.create(control);
                } catch (exception) {}
            }
        }

        /**
         * Обновляет внутреннее описание полей в экземплярах main.ui.filter.
         *
         * @param {Array} fields
         *
         * @return {void}
         */
        patchFilterInstances(fields) {
            if (
                !window.BX
                || !BX.Main
                || !BX.Main.filterManager
                || typeof BX.Main.filterManager.getById !== 'function'
            ) {
                return;
            }

            this.getFilterIds().forEach((filterId) => {
                const filter = BX.Main.filterManager.getById(filterId);

                if (!filter) {
                    return;
                }

                fields.forEach((field) => {
                    this.patchFilterMetadata(filter, field);
                });
            });
        }

        /**
         * Возвращает ID фильтров на странице.
         *
         * @return {Array}
         */
        getFilterIds() {
            const ids = [];
            const containers = document.querySelectorAll('.main-ui-filter-search[id$="_search_container"]');

            Array.prototype.forEach.call(containers, function (container) {
                const filterId = container.id.replace(/_search_container$/, '');

                if (filterId && ids.indexOf(filterId) === -1) {
                    ids.push(filterId);
                }
            });

            return ids;
        }

        /**
         * Обновляет описание одного поля внутри экземпляра фильтра.
         *
         * @param {object} filter
         * @param {object} field
         *
         * @return {void}
         */
        patchFilterMetadata(filter, field) {
            this.patchFilterParam(filter, 'FIELDS', field);
            this.patchFilterParam(filter, 'fields', field);
            this.patchFieldCollection(filter.fields, field);
            this.patchFieldCollection(filter._fields, field);
            this.patchFieldCollection(filter.controls, field);
            this.patchFieldCollection(filter._controls, field);

            if (filter.params) {
                this.patchFieldCollection(filter.params.FIELDS, field);
                this.patchFieldCollection(filter.params.fields, field);
            }

            if (typeof filter.getField === 'function') {
                this.patchFieldDefinition(filter.getField(field.name), field);
            }
        }

        /**
         * Обновляет поле в параметрах фильтра.
         *
         * @param {object} filter
         * @param {string} paramName
         * @param {object} field
         *
         * @return {void}
         */
        patchFilterParam(filter, paramName, field) {
            if (typeof filter.getParam !== 'function') {
                return;
            }

            const collection = filter.getParam(paramName);

            if (!collection) {
                return;
            }

            this.patchFieldCollection(collection, field);

            if (typeof filter.setParam === 'function') {
                filter.setParam(paramName, collection);
            }
        }

        /**
         * Обновляет поле внутри коллекции описаний.
         *
         * @param {Array|object|null} collection
         * @param {object} field
         *
         * @return {void}
         */
        patchFieldCollection(collection, field) {
            if (!collection || typeof collection !== 'object') {
                return;
            }

            if (Array.isArray(collection)) {
                collection.forEach((definition) => {
                    this.patchFieldDefinition(definition, field);
                });
                return;
            }

            if (collection[field.name]) {
                this.patchFieldDefinition(collection[field.name], field);
            }

            Object.keys(collection).forEach((key) => {
                this.patchFieldDefinition(collection[key], field);
            });
        }

        /**
         * Обновляет описание одного поля фильтра.
         *
         * @param {object|null} definition
         * @param {object} field
         *
         * @return {void}
         */
        patchFieldDefinition(definition, field) {
            if (!definition || typeof definition !== 'object') {
                return;
            }

            const names = [
                definition.name,
                definition.NAME,
                definition.id,
                definition.ID,
            ].filter(function (value) {
                return value !== undefined && value !== null;
            }).map(function (value) {
                return String(value);
            });

            if (names.indexOf(String(field.name)) === -1) {
                return;
            }

            definition.type = 'MULTI_SELECT';
            definition.TYPE = 'MULTI_SELECT';
            definition.items = field.items || [];
            definition.ITEMS = field.items || [];
            definition.params = Object.assign({}, definition.params || {}, { isMulti: true });
            definition.PARAMS = Object.assign({}, definition.PARAMS || {}, { isMulti: true });
        }

        /**
         * Восстанавливает выбранные значения из старого текстового поля.
         *
         * @param {string} rawValue
         * @param {Array} items
         *
         * @return {Array}
         */
        resolveSelectedValues(rawValue, items) {
            const values = String(rawValue || '').split(',').map(function (value) {
                return value.trim();
            }).filter(function (value) {
                return value !== '';
            });

            if (!values.length) {
                return [];
            }

            return values.map(function (value) {
                const item = items.find(function (option) {
                    return String(option.VALUE) === value || String(option.NAME) === value;
                });

                return item ? String(item.VALUE) : null;
            }).filter(function (value) {
                return value !== null;
            });
        }

        /**
         * Экранирует значение для CSS-селектора атрибута.
         *
         * @param {string} value
         *
         * @return {string}
         */
        escapeAttribute(value) {
            return String(value || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        }

        /**
         * Экранирует значение для регулярного выражения.
         *
         * @param {string} value
         *
         * @return {string}
         */
        escapeRegExp(value) {
            return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
    }

    window.TaskUserFieldsModule.TaskFilterFields = TaskFilterFields;
})(window, document);
