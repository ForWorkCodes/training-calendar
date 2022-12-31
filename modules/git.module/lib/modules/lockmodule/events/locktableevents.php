<?php
namespace Git\Module\Modules\LockModule\Events;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Modules\Events\General\Events;
use Git\Module\Modules\LockModule\General\TrainingLockTable;
use Git\Module\Training\Mediator\MediatorForTraining;
use Git\Module\User\Mediator\MediatorForUser;

class LockTableEvents
{
    /**
     * Событие при добавлении участника тренировки в таблицу
     * @param \Bitrix\Main\Event $event
     */
    public static function memberAddInTable(\Bitrix\Main\Event $event)
    {
        $arData = $event->getParameters();

        if ( !($arData['ENTITY_PARENT'] instanceof IForMediator) || !($arData['ENTITY_CHILD'] instanceof IForMediator) ) return;

        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $arUsers = $obMediator->getDataFromLockTable($arData['ENTITY_PARENT'], $arData['ENTITY_CHILD']);
        $lockCount = count($arUsers);
        $maxLock = \COption::GetOptionString('git.module', 'TRAINING_MAX_BLOCKED', '3');
        $objDateTime = new DateTime();
        $objDateTime->add('1 months');

        if ($lockCount == $maxLock) {

            /* Уведомление "Возможность записи на тренировки возобновлена" */
            $arFields = [
                'UF_TYPE' => 'MESSAGE',
                'UF_XML_ID' => 'E0087',
                'UF_ID_PARENT' => $arData['ID'],
                'UF_DATE_START' => $objDateTime->toString(),
                'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                'UF_PARAMS' => [
                    'CODE' => 'E0087',
                    'USER_ID' => [$arData['ENTITY_CHILD']->getId()],
                    'REPLACE_RULES' => []
                ],
            ];
            $obMediator = new Events();
            $obMediator->add($arFields);

            /* Функция "Деактивировать все блокировки, запускается после определенного времени" */
            $arFields = [
                'UF_TYPE' => 'FUNCTION',
                'UF_XML_ID' => 'DEL_ALL_LOCK_IN_TRAINING',
                'UF_ID_PARENT' => $arData['ID'],
                'UF_DATE_START' => $objDateTime->toString(),
                'UF_FUNCTION' => '\Git\Module\Modules\LockModule\Events\LockTableEvents::deactivatedAllLocksInTraining',
                'UF_PARAMS' => [
                    'ID_USER' => $arData['ENTITY_CHILD']->getId(),
                    'ID_TRAINING' => $arData['ENTITY_PARENT']->getId()
                ],
            ];
            $obMediator = new Events();
            $obMediator->add($arFields);
        } else {
            /* Функция "Деактивировать блокировку, запускается после определенного времени" */
            $arFields = [
                'UF_TYPE' => 'FUNCTION',
                'UF_XML_ID' => 'ADD_LOCK_IN_TRAINING',
                'UF_ID_PARENT' => $arData['ID'],
                'UF_DATE_START' => $objDateTime->toString(),
                'UF_FUNCTION' => '\Git\Module\Modules\LockModule\Events\LockTableEvents::deactivatedLock',
                'UF_PARAMS' => [
                    'ID_ITEM_IN_TABLE' => $arData['ID']
                ],
            ];
            $obMediator = new Events();
            $obMediator->add($arFields);
        }

    }

    /**
     * Событие при удалении участника тренировки из таблицы
     * @param \Bitrix\Main\Event $event
     */
    public static function memberDelFromTable(\Bitrix\Main\Event $event)
    {
        $arData = $event->getParameters();
        if (empty($arData['OLD_ID'])) return;

        $obMediator = new Events();
        $obMediator->del($arData['OLD_ID']);
    }

    /**
     * Деактивировать блокировку, запускается после определенного времени
     * @param $arParams
     */
    public static function deactivatedLock($arParams)
    {
        if (empty($arParams['ID_ITEM_IN_TABLE'])) return;

        $table = new TrainingLockTable();
        $table->deactivatedLock($arParams['ID_ITEM_IN_TABLE']);
    }

    /**
     * Деактивировать все блокировки, запускается после определенного времени
     * @param $arParams
     */
    public static function deactivatedAllLocksInTraining($arParams) {
        if (empty($arParams['ID_USER']) || empty($arParams['ID_TRAINING'])) return;

        $obMediatorT = new MediatorForTraining();
        $obTraining = $obMediatorT->buildClass((int)$arParams['ID_TRAINING']);

        $obMediatorU = new MediatorForUser();
        $obUser = $obMediatorU->buildClass((int)$arParams['ID_USER'], 'user');

        if (empty($obUser->getId())) return;

        $table = new TrainingLockTable();
        $arDatas = $table->getUserLockByParent($obTraining, $obUser);

        if (!empty($arDatas))
            foreach ($arDatas as $arData) {
                $table->deactivatedLock($arData['ID']);
            }

    }
}
?>