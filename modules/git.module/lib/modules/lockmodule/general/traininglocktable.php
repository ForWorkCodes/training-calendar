<?php
namespace Git\Module\Modules\LockModule\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Event;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Modules\LockModule\Mediator\MediatorLockTable;

class TrainingLockTable extends MediatorLockTable
{
    /**
     * Добавить участника тренировки в таблицу
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return AddResult|mixed
     */
    public function addLock(IForMediator $obParent, IForMediator $obChild)
    {
        $result = $this->add($obParent, $obChild);
        if ($result instanceof AddResult) {
            if ($result->isSuccess()) {
                $bxEvent = new Event('git.module', 'onAfterMemberAddInTable', [
                    'ID' => $result->getId(),
                    'ENTITY_PARENT' => $obParent,
                    'ENTITY_CHILD' => $obChild,
                ]);
                $bxEvent->send();
            }
        }

        return $result;
    }

    /**
     * Убрать участника тренировки из таблицы
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return DeleteResult|mixed
     */
    public function delLock(IForMediator $obParent, IForMediator $obChild)
    {
        $result = $this->del($obParent, $obChild);

        if ($result instanceof DeleteResult) {
            if ($result->isSuccess()) {
                $arData = $result->getData();
                $bxEvent = new Event('git.module', 'onAfterMemberDelFromTable', [
                    'OLD_ID' => $arData['OLD_ID'],
                    'ENTITY_PARENT' => $obParent,
                    'ENTITY_CHILD' => $obChild,
                ]);
                $bxEvent->send();
            }
        }

        return $result;
    }

    /**
     * Убрать активность на определенной блокировке
     * @param $id
     * @return mixed
     */
    public function deactivatedLock($id)
    {
        $bxEvent = new Event('git.module', 'onBeforeDeActivLock', []);
        $bxEvent->send();

        $arParam = [
            $this->arFields['ACTIVE'] => 'N'
        ];

        $result = $this->update($id, $arParam);

        $bxEvent = new Event('git.module', 'onAfterDeActivLock', []);
        $bxEvent->send();

        return $result;
    }
}
?>