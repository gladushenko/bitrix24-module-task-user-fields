/**
 * Точка входа frontend-части модуля пользовательских полей задач.
 */
(function (window) {
    'use strict';

    window.TaskUserFieldsModule = window.TaskUserFieldsModule || {};

    /**
     * Запускает frontend-расширения модуля.
     *
     * @return {void}
     */
    function bootstrap() {
        const config = window.TASK_USER_FIELDS_MODULE_CONFIG || {};

        if (window.TaskUserFieldsModule.TaskErrorNotifier) {
            const taskErrorNotifier = new window.TaskUserFieldsModule.TaskErrorNotifier();
            taskErrorNotifier.install();
        }

        if (window.TaskUserFieldsModule.TaskUserFields) {
            const taskUserFields = new window.TaskUserFieldsModule.TaskUserFields(config);
            taskUserFields.init();
        }

        if (window.TaskUserFieldsModule.TaskFilterFields) {
            const taskFilterFields = new window.TaskUserFieldsModule.TaskFilterFields(config);
            taskFilterFields.init();
        }
    }

    bootstrap();
})(window);
