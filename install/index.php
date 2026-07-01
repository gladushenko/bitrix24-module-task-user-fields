<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class gladushenko_taskuserfields extends CModule
{
    public const MODULE_ID = 'gladushenko.taskuserfields';

    public $MODULE_ID = self::MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    /**
     * Инициализирует метаданные модуля для списка модулей Битрикса.
     *
     * @return void
     */
    public function __construct()
    {
        $moduleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $moduleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $moduleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('PARTNER_URI');
    }

    /**
     * Устанавливает модуль, файлы и обработчики событий.
     *
     * @return bool
     */
    public function DoInstall(): bool
    {
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();

        return true;
    }

    /**
     * Регистрирует модуль в Битриксе.
     *
     * @return bool
     */
    public function InstallDB(): bool
    {
        ModuleManager::registerModule(self::MODULE_ID);

        return true;
    }

    /**
     * Показывает подтверждение удаления или удаляет модуль.
     *
     * @return bool
     */
    public function DoUninstall(): bool
    {
        if ($this->getUninstallStep() < 2) {
            $this->showUninstallConfirmation();
            return true;
        }

        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();

        return true;
    }

    /**
     * Снимает регистрацию модуля в Битриксе.
     *
     * @return bool
     */
    public function UnInstallDB(): bool
    {
        ModuleManager::unRegisterModule(self::MODULE_ID);

        return true;
    }

    /**
     * Регистрирует обработчики событий модуля.
     *
     * @return void
     */
    public function InstallEvents(): void
    {
        foreach ($this->getEventHandlers() as $handler) {
            $this->registerEventHandler($handler);
        }
    }

    /**
     * Удаляет регистрацию обработчиков событий модуля.
     *
     * @return void
     */
    public function UnInstallEvents(): void
    {
        foreach ($this->getEventHandlers() as $handler) {
            $this->unregisterEventHandler($handler);
        }
    }

    /**
     * Оставляет файлы модуля внутри директории модуля.
     *
     * @return void
     */
    public function InstallFiles(): void
    {
    }

    /**
     * Оставляет файлы модуля внутри директории модуля.
     *
     * @return void
     */
    public function UnInstallFiles(): void
    {
    }

    /**
     * Возвращает список прав модуля.
     *
     * @return array
     */
    public function GetModuleRightList(): array
    {
        return [];
    }

    /**
     * Возвращает текущий шаг удаления модуля.
     *
     * @return int
     */
    private function getUninstallStep(): int
    {
        return (int)($_REQUEST['step'] ?? 1);
    }

    /**
     * Показывает страницу подтверждения удаления модуля.
     *
     * @return void
     */
    private function showUninstallConfirmation(): void
    {
        global $APPLICATION;

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('UNINSTALL_TITLE') ?: 'Удаление модуля',
            __DIR__ . '/unstep_1.php'
        );
    }

    /**
     * Возвращает список обработчиков событий модуля.
     *
     * @return array
     */
    private function getEventHandlers(): array
    {
        return [
            [
                'module' => 'main',
                'event' => 'OnBuildGlobalMenu',
                'class' => Gladushenko\TaskUserFields\Admin\Menu::class,
                'method' => 'onBuildGlobalMenu',
            ],
            [
                'module' => 'main',
                'event' => 'OnProlog',
                'class' => Gladushenko\TaskUserFields\Frontend\AssetLoader::class,
                'method' => 'onProlog',
            ],
        ];
    }

    /**
     * Регистрирует один обработчик события.
     *
     * @param array $handler
     *
     * @return void
     */
    private function registerEventHandler(array $handler): void
    {
        EventManager::getInstance()->registerEventHandler(
            $handler['module'],
            $handler['event'],
            self::MODULE_ID,
            $handler['class'],
            $handler['method']
        );
    }

    /**
     * Удаляет регистрацию одного обработчика события.
     *
     * @param array $handler
     *
     * @return void
     */
    private function unregisterEventHandler(array $handler): void
    {
        EventManager::getInstance()->unRegisterEventHandler(
            $handler['module'],
            $handler['event'],
            self::MODULE_ID,
            $handler['class'],
            $handler['method']
        );
    }

}
