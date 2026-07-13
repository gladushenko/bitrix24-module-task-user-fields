/**
 * Расширяет штатные AJAX-действия задач и показывает понятные ошибки пользователю.
 */
(function (window) {
    'use strict';

    window.TaskUserFieldsModule = window.TaskUserFieldsModule || {};

    class TaskErrorNotifier {
        /**
         * Создает обработчик ошибок задач.
         */
        constructor() {
            this.notificationId = 'task-user-fields-bottom-error';
        }

        /**
         * Устанавливает перехватчик ошибок штатных AJAX-действий задач.
         *
         * @return {void}
         */
        install() {
            if (
                !window.BX
                || !BX.ajax
                || !BX.ajax.runAction
                || BX.ajax.runAction.__taskUserFieldsErrorNotifierInstalled
            ) {
                return;
            }

            const originalRunAction = BX.ajax.runAction;
            const notifier = this;

            window.TaskUserFieldsModule.showTaskError = function (message) {
                notifier.showBottomError(
                    notifier.normalizeTaskAjaxErrorMessage(message || 'Ошибка сохранения')
                );
            };

            BX.ajax.runAction = function (action, config) {
                const promise = originalRunAction.apply(this, arguments);

                if (
                    !notifier.isTaskUpdateAction(action)
                    || !promise
                    || typeof promise.catch !== 'function'
                ) {
                    return promise;
                }

                return promise.catch(function (response) {
                    notifier.showTaskAjaxError(response);
                    throw response;
                });
            };

            BX.ajax.runAction.__taskUserFieldsErrorNotifierInstalled = true;
        }

        /**
         * Проверяет, относится ли AJAX-действие к обновлению задачи.
         *
         * @param {string} action
         *
         * @return {boolean}
         */
        isTaskUpdateAction(action) {
            return typeof action === 'string'
                && action.indexOf('tasks.v2.Task.') === 0
                && action.indexOf('.update') !== -1;
        }

        /**
         * Показывает ошибку AJAX-действия задачи.
         *
         * @param {object} response
         *
         * @return {void}
         */
        showTaskAjaxError(response) {
            const message = this.getTaskAjaxErrorMessage(response);
            this.showBottomError(message);
        }

        /**
         * Показывает нижнее уведомление об ошибке.
         *
         * @param {string} message
         *
         * @return {void}
         */
        showBottomError(message) {
            const exists = document.getElementById(this.notificationId);

            if (exists) {
                exists.parentNode.removeChild(exists);
            }

            const notification = document.createElement('div');
            notification.id = this.notificationId;
            notification.textContent = message;
            notification.style.cssText = [
                'position: fixed',
                'left: 50%',
                'bottom: 64px',
                'transform: translateX(-50%)',
                'width: auto',
                'max-width: 720px',
                'box-sizing: border-box',
                'padding: 13px 18px',
                'border-radius: 4px',
                'border: 1px solid #f5a6a6',
                'background: #fde8e8',
                'color: #a10707',
                'font: 14px/1.45 Arial, Helvetica, sans-serif',
                'box-shadow: 0 10px 30px rgba(82, 92, 105, .22)',
                'z-index: 2147483647',
                'text-align: center',
                'word-break: break-word'
            ].join(';');

            document.body.appendChild(notification);

            setTimeout(function () {
                notification.style.transition = 'opacity .2s ease';
                notification.style.opacity = '0';

                setTimeout(function () {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 220);
            }, 7000);
        }

        /**
         * Возвращает текст ошибки из ответа AJAX-действия.
         *
         * @param {object} response
         *
         * @return {string}
         */
        getTaskAjaxErrorMessage(response) {
            const errors = response && response.errors;

            if (!errors || !errors.length || !errors[0].message) {
                return 'Ошибка сохранения';
            }

            return this.normalizeTaskAjaxErrorMessage(errors[0].message);
        }

        /**
         * Очищает техническую обертку ошибки Bitrix Command.
         *
         * @param {string} message
         *
         * @return {string}
         */
        normalizeTaskAjaxErrorMessage(message) {
            const match = message.match(/Command has unprocessed exception: "([^"]+)"/);

            return match && match[1] ? match[1] : message;
        }
    }

    window.TaskUserFieldsModule.TaskErrorNotifier = TaskErrorNotifier;
})(window);
