<?php
namespace Git\Module\Training\Abstracts;

use Bitrix\Main\Diag\Debug;
use Git\Module\Helpers\Utils;
use Git\Module\Training\Interfaces\ITraining;

abstract class Training implements ITraining
{
    protected $id;
    protected $name;
    protected $info;
    protected $members;
    protected $mediator;
    protected $status;
    const CODE_TYPE = 'TYPE';

    public function __construct($id = null)
    {
        $this->setName();

        if ($id != null)
            $this->setInfo($id);
    }

    public function getInfo()
    {
        return $this->info;
    }

    protected function setInfo($id)
    {
        $this->setId($id);

        $this->info = $this->getDetailInfo();

        if (!empty($this->id))
            $this->getStatusInMuduleTable();

        $this->info['ID'] = $this->id;
        $this->info['CLASS'] = $this->name;
    }

    /**
     * Получить детальную информацию из инфоблока по этой тренировке
     * @return array|void
     */
    protected function getDetailInfo()
    {
        if (empty($this->id)) return;

        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ACTIVE' => 'Y',
            '=ID' => $this->id
        ];
        $obDatas = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            false,
            []
        );
        while ($obData = $obDatas->GetNextElement())
        {
            $arResult = $obData->GetFields();
            $arResult['PROPERTIES'] = $obData->GetProperties();
        }

        if (empty($arResult))
            $this->id = '';

        return $arResult;
    }

    /**
     * Получение активности тренировки в таблице связей модулей
     * @return mixed
     */
    private function getStatusInMuduleTable()
    {
        $this->getMediator();
        return $this->info['STATUS_ACTIVE'] = $this->mediator->getStatusTraining($this);
    }

    /**
     * Получение участников тренировки
     * @return mixed
     */
    public function getMembers()
    {
        $this->getMediator();
        return $this->info['MEMBERS'] = $this->mediator->getListMembers($this);
    }

    /**
     * Отправить посреднику запрос на добавление участников
     * TODO: Где добавить проверку на разрешение? Например человек в бане
     * @param array $arData
     */
    public function addMembers(array $arData)
    {
        $this->getMediator();
        $this->mediator->addMembers($this, $arData);
    }

    /**
     * Отправить посреднику запрос на удаление участников
     * TODO: Где добавить проверку на разрешение? Например другой пользователь пытается это сделать
     * @param array $arData
     */
    public function delMembers(array $arData)
    {
        $this->getMediator();
        $this->mediator->delMembers($this, $arData);
    }

    /**
     * Получение тренера тренировки
     * @deprecated Тренер устанавливается в регулярной тренировке
     * @return mixed
     */
    public function getTrainer()
    {
        $this->getMediator();
        return $this->info['TRAINER'] = $this->mediator->getTrainer($this);
    }

    /**
     * Вернет куратора
     * @return mixed|void
     */
    public function getKurator()
    {
        $this->getMediator();
        return $this->info['KURATOR'] = $this->mediator->getKurator()['RESULT'];
    }

    /**
     * Установка тренера для тренировки
     * @deprecated Тренер устанавливается в регулярной тренировке
     * @param int $id_trainer
     */
    public function setTrainer(int $id_trainer)
    {
        $this->getMediator();
        $this->mediator->setTrainer($this, $id_trainer);
    }

    public function getDate()
    {
        // TODO: Implement getDate() method.
    }

    public function getWave()
    {
        // TODO: Implement getWave() method.
    }

    /**
     * Название класса
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * ID из БД
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Название класса
     */
    protected function setName()
    {
        $this->name = get_class($this);
    }

    /**
     * ID из БД
     * @param $id
     */
    protected function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Вернет код свойства который содержит тип тренировки
     * @return string
     */
    public static function getCodeTypeProp()
    {
        return self::CODE_TYPE;
    }

    public function getMediator()
    {
        $this->setMediator();
        return $this->mediator;
    }

    public static function getIblockId()
    {
        $iblock_id = Utils::getIdByCode('training');
        return $iblock_id;
    }

    /**
     * Установка статуса объекта в таблице связей
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Вернет статус объекта в таблице связей
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Реализующий класс будет знать какой посредник с ним контактирует
     * @return mixed
     */
    abstract protected function setMediator();
    abstract public static function getListById(array $arId);
}
?>