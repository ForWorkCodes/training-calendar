<?php
namespace Git\Module\RegularTraining\Abstracts;

use Git\Module\Helpers\Utils;
use Git\Module\RegularTraining\Interfaces\IRegularTraining;

abstract class RegularTraining implements IRegularTraining
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

    /**
     * Вернет список тренировок внутри регулярной тренировки
     * @return array
     */
    public function getTrainings(bool $convert_in_array = true)
    {
        return $this->findTrainings($convert_in_array);
    }

    /**
     * Получение одной тренировки внутри регулярной
     * @param $id
     * @return mixed
     */
    public function getTrainingById($id)
    {
        $this->setMediator();
        return $this->info['TRAININGS'] = $this->mediator->getTrainingByIdInRegular($id);
    }

    /**
     * Найдет тренировки внутри регулярной тренировки
     * @return mixed
     */
    protected function findTrainings(bool $convert_in_array = true)
    {
        $this->setMediator();
        if (!empty($this->id))
        {
            return $this->info['TRAININGS'] = $this->mediator->getTrainingsInRegularTraining($this, $convert_in_array);
        }
    }

    /**
     * Добавить тренировки в эту регулярную тренировку
     * @param array $arData
     * @return mixed
     */
    public function addTraining(array $arData)
    {
        $this->setMediator();
        $result = $this->mediator->addTraining($this, $arData);
        return $result;
    }

    public function getInfo()
    {
        return $this->info;
    }

    protected function setInfo($id)
    {
        $this->setId($id);

        $this->info = $this->getDetailInfo();

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
     * Установка тренера для тренировки
     * @param int $id_trainer
     */
    public function setTrainer(int $id_trainer)
    {
        $this->getMediator();
        $this->mediator->setTrainer($this, $id_trainer);
    }

    /**
     * Получение тренера тренировки
     * @return mixed
     */
    public function getTrainer()
    {
        $this->getMediator();
        return $this->info['TRAINER'] = $this->mediator->getTrainer($this);
    }

    public function getMembers()
    {
        // TODO: Implement getMembers() method.
    }

    /**
     * Вернет куратора тренировок
     * @return mixed
     */
    public function getKurator()
    {
        $this->getMediator();
        return $this->info['KURATOR'] = $this->mediator->getKurator();
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
     * Вернет код свойства который содержит тип регулярной тренировки
     * @return string
     */
    public static function getCodeTypeProp()
    {
        return self::CODE_TYPE;
    }

    /**
     * Установил посредника для этого класса
     * @return mixed
     */
    public function getMediator()
    {
        $this->setMediator();
        return $this->mediator;
    }

    public static function getIblockId()
    {
        $iblock_id = Utils::getIdByCode('regularTraining');
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
    abstract public static function getAllStatic();
    abstract public function getAll();
}

?>