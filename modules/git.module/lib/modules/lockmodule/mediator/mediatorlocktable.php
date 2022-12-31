<?php
namespace Git\Module\Modules\LockModule\Mediator;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Event;
use Bitrix\Main\Type\DateTime;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Modules\Events\General\Events;
use Git\Module\Tables\lockModulesTable;

class MediatorLockTable
{
    protected $table;
    protected $arFields = [
        'PARENT' => 'UF_PARENT_ENTITY',
        'PARENT_ID' => 'UF_PARENT_ID',
        'CHILD' => 'UF_CHILD_ENTITY',
        'CHILD_ID' => 'UF_CHILD_ID',
        'ACTIVE' => 'UF_ACTIVE',
        'DATE' => 'UF_DATE_CREATE'
    ];

    public function __construct()
    {
        $this->table = new lockModulesTable();
    }

    /**
     * Вернет массив с активными попусками
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return mixed
     */
    public function getUserLockByParent(IForMediator $obParent, IForMediator $obChild)
    {
        $arData['filter'] = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obChild->getName()),
            $this->arFields['CHILD_ID'] => $obChild->getId(),
            $this->arFields['ACTIVE'] => 'Y'
        ];

        $rsUsers = lockModulesTable::getList($arData);
        while ($arUser = $rsUsers->fetch())
            $arUsers[] = $arUser;

        return $arUsers;
    }

    /**
     * Добавить объект в таблицу блокировок если его там нет
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return mixed
     */
    public function add(IForMediator $obParent, IForMediator $obChild)
    {
        $arData = [
            $this->arFields['PARENT'] => $obParent->getName(),
            $this->arFields['PARENT_ID'] => $obParent->getId(),
            $this->arFields['CHILD'] => $obChild->getName(),
            $this->arFields['CHILD_ID'] => $obChild->getId()
        ];

        $check = $this->isItemAlreadyIsset($arData);

        if ($check == false) {
            $arData[$this->arFields['ACTIVE']] = 'Y';
            $arData[$this->arFields['DATE']] = new \Bitrix\Main\Type\DateTime;
            $result = $this->table::add($arData);
        }

        return $result;
    }

    /**
     * Изменить объект в таблице блокировок
     * @param $id
     * @param $arParam
     * @return mixed
     */
    public function update($id, $arParam)
    {
        $bxEvent = new Event('git.module', 'onBeforeTableLockUpdate', []);
        $bxEvent->send();

        $result = $this->table::update($id, $arParam);

        $bxEvent = new Event('git.module', 'onAfterTableLockUpdate', []);
        $bxEvent->send();

        return $result;
    }

    /**
     * Убрать объект из таблицы блокировок если он там есть
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return mixed
     */
    public function del(IForMediator $obParent, IForMediator $obChild)
    {
        $arData = [
            $this->arFields['PARENT'] => $obParent->getName(),
            $this->arFields['PARENT_ID'] => $obParent->getId(),
            $this->arFields['CHILD'] => $obChild->getName(),
            $this->arFields['CHILD_ID'] => $obChild->getId()
        ];

        $check = $this->isItemAlreadyIsset($arData);

        if ($check != false) {
            $result = $this->table::delete($check);
            if ($result->isSuccess())
                $result->setData(['OLD_ID' => $check]);
        }

        return $result;
    }

    /**
     * Проверка элемента на наличие в списке
     * @param $arParam
     * @return bool
     */
    protected function isItemAlreadyIsset($arParam)
    {
        $arFormatParams['filter'] = [
            $this->arFields['PARENT'] => $arParam[$this->arFields['PARENT']] ? Utils::formatClassNameForWrite($arParam[$this->arFields['PARENT']]) : '',
            $this->arFields['CHILD'] => $arParam[$this->arFields['CHILD']] ? Utils::formatClassNameForWrite($arParam[$this->arFields['CHILD']]) : '',
            $this->arFields['CHILD_ID'] => $arParam[$this->arFields['CHILD_ID']],
            $this->arFields['PARENT_ID'] => $arParam[$this->arFields['PARENT_ID']]
        ];

        $arList = $this->table::getList($arFormatParams);

        while ($arData = $arList->fetch())
            $result[] = $arData;

        if (empty($result))
            $result = false;
        else
            $result = $result[0]['ID'];

        return $result;
    }

    /**
     * Получить дату последнего запланированного вызова функции обнуления блокировок
     * @param array $arListLock
     * @return string
     * @throws \Bitrix\Main\ObjectException
     */
    public function getEndDateLockUserInTraining(array $arListLock)
    {
        $obEvents = new Events();
        $arLastLock = end($arListLock);

        $arData = [
            'UF_TYPE' => 'FUNCTION',
            'UF_XML_ID' => 'DEL_ALL_LOCK_IN_TRAINING',
            'UF_ID_PARENT' => $arLastLock['ID'],
            'UF_ACTIVE' => true
        ];

        $arEvent = $obEvents->get($arData);

        if (!empty($arEvent)) {
            $time = end($arEvent)['UF_DATE_START'];

            if (!empty($time)) {
                $obDate = new DateTime($time);
                $date = $obDate->format('d.m.Y');
            }

        }

        return $date;
    }
}
?>