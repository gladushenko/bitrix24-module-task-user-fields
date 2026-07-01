<?php
defined('B_PROLOG_INCLUDED') || die;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
/** @var CMain $APPLICATION */
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="gladushenko.taskuserfields">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">

    <p><b>Вы точно хотите удалить модуль «<?= Loc::getMessage('MODULE_NAME') ?>»?</b></p>

    <input type="submit" value="Удалить" class="adm-btn-save">
</form>
