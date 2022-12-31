<?php
namespace Git\Module\Mediator\Abstracts;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\GroupTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IMember,
    Git\Module\Mediator\Interfaces\IForMediator,
    \Bitrix\Main\Event,
    Git\Module\Models\Connections;

/**
 * Этот класс должен быть реализован самим посредником
 * Class Member
 * @package Git\Module\Mediator\Abstracts
 */
abstract class Member implements IMember
{
    protected $table;
    protected $table_entity;

    /**
     * Поля таблицы, настоящие коды знает только класс
     */
    public $arFields;

    public function __construct()
    {
        $this->arFields = [
            'PARENT' => 'UF_PARENT_ENTITY',
            'PARENT_ID' => 'UF_PARENT_ID',
            'CHILD' => 'UF_CHILD_ENTITY',
            'CHILD_ID' => 'UF_CHILD_ID',
            'ACTIVE' => 'UF_ACTIVE',
            'DATE' => 'UF_DATE_CREATE'
        ];
        $this->table = new Connections();
        $this->table_entity = $this->table::getHlb()->getClass();
    }

    /**
     * Получение по фильтру
     * TODO: подумать, как подключить JOIN
     * @param array $params
     * @return mixed
     */
    public function get(array $params = [], $limit = false, $arOrder = [])
    {
        return $this->getData($params, $limit, $arOrder);
    }

    /**
     * Запись участников связанных с объектом
     * @param IForMediator $parent
     * @param IForMediator $child
     * @return mixed|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function add(IForMediator $parent, IForMediator $child) : AddResult
    {
        return $this->addData($parent, $child);
    }

    /**
     * Удаление участников связанных с объектом
     * @param array $arId
     * @return mixed|void
     */
    public function del(array $arId)
    {
        return $this->delData($arId);
    }

    /**
     * @param int $id
     * @param array $arParam
     * @return UpdateResult|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function update(int $id, array $arParam) : UpdateResult
    {
        return $this->updateData($id, $arParam);
    }

    /**
     * @param int $id
     * @param array $arParam
     * @return \Bitrix\Main\ORM\Data\UpdateResult
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function updateData(int $id, array $arParam) : UpdateResult
    {
        $bxEvent = new Event('git.module', 'onBeforeMediatorMemberUpdate', []);
        $bxEvent->send();

        $result = $this->table::update($id, $arParam);

        $bxEvent = new Event('git.module', 'onAfterMediatorMemberUpdate', []);
        $bxEvent->send();

        return $result;
    }

    /**
     * Запись новой связи в таблицу
     * @param IForMediator $parent
     * @param IForMediator $child
     * @return \Bitrix\Main\Entity\AddResult
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function addData(IForMediator $parent, IForMediator $child) : AddResult
    {
        $arParam = [
            $this->arFields['PARENT'] => $parent->getName(),
            $this->arFields['PARENT_ID'] => $parent->getId(),
            $this->arFields['CHILD'] => $child->getName(),
            $this->arFields['CHILD_ID'] => $child->getId()
        ];

        $result = new \Bitrix\Main\ORM\Data\AddResult();

        $bxEvent = new Event('git.module', 'onBeforeMediatorMemberAdd', []);
        $bxEvent->send();

        $check = $this->isItemAlreadyIsset($arParam);
        if ($check == false)
        {
            $arParam[$this->arFields['ACTIVE']] = 'Y';
            $arParam[$this->arFields['DATE']] = new \Bitrix\Main\Type\DateTime;
            $result = $this->table::add($arParam);
        }
        else
            $result->addError(new \Bitrix\Main\Error(Loc::getMessage('ITEM_ALREADY_ISSET')));

        $bxEvent = new Event('git.module', 'onAfterMediatorMemberAdd', []);
        $bxEvent->send();

        return $result;
    }

    /**
     * Проверка элемента на наличие в списке
     * @param $arParam
     * @return bool
     */
    protected function isItemAlreadyIsset($arParam)
    {
        $arFormatParams = [
            $this->arFields['PARENT'] => $arParam[$this->arFields['PARENT']] ? Utils::formatClassNameForWrite($arParam[$this->arFields['PARENT']]) : '',
            $this->arFields['CHILD'] => $arParam[$this->arFields['CHILD']] ? Utils::formatClassNameForWrite($arParam[$this->arFields['CHILD']]) : '',
            $this->arFields['CHILD_ID'] => $arParam[$this->arFields['CHILD_ID']],
            $this->arFields['PARENT_ID'] => $arParam[$this->arFields['PARENT_ID']]
        ];
        $result = $this->get($arFormatParams);

        if (empty($result))
            $result = false;
        else
            $result = true;

        return $result;
    }

    /**
     * Получение по фильтру
     * @param array $param
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getData(array $param = [], $limit = false, $arOrder = [])
    {
        return $this->table->getList($param, $limit, $arOrder);
    }

    /**
     * Удаление участников связанных с объектом
     * @param array $arId
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function delData(array $arId)
    {
        $bxEvent = new Event('git.module', 'onBeforeMediatorMemberDel', []);
        $bxEvent->send();

        $return = $this->table::Delete($arId);

        $bxEvent = new Event('git.module', 'onAfterMediatorMemberDel', []);
        $bxEvent->send();

        return $return;
    }

    /**
     * Получить объекты участников модуля по его данным
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return mixed
     */
    protected function getElsChildByParent(IForMediator $obParent, IForMediator $obChild, bool $need_array = false)
    {
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['PARENT_ID'] => $obParent->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obChild->getName())
        ];
        $arList = $this->get($arData);
        if (!empty($arList))
        {
            if ($need_array)
                $arResult = $arList;
            else {
                foreach ($arList as $arData)
                {
                    $ob = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
                    $ob->setStatus($arData[$this->arFields['ACTIVE']]);
                    $arResult[] = $ob;
                }
            }
        }

        return $arResult;
    }

    /**
     * Получение объекта родителя по классу наследника
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return array
     */
    protected function getElParentByChild(IForMediator $obParent, IForMediator $obChild)
    {
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['CHILD_ID'] => $obChild->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obChild->getName())
        ];
        $arList = $this->get($arData);
        if (!empty($arList))
        {
            foreach ($arList as $arData)
            {
                $arResult[] = $this->buildClass($arData[$this->arFields['PARENT_ID']]);
            }
        }

        return $arResult;
    }

    /**
     * Вернет все активные элементы своего типа (например посредник тренировок вернет все тренировки)
     * @return mixed
     */
    abstract public function getAll();
    abstract public static function getAllStatic();
}
?>