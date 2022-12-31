<?php
namespace Git\Module\RegularTraining\Mediator;

use Bitrix\Main\Diag\Debug;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Factory\General\FactoryRegularTraining;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\General\Member;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\RegularTraining\General\RegularTraining;
use Git\Module\Training\Interfaces\ITraining;
use Git\Module\Training\Mediator\MediatorForTraining;
use Git\Module\User\Mediator\MediatorForUser;

class MediatorForRegularTraining extends Member
{
    protected $entity;
    protected $arFilter = [];

    /**
     * Возвращает нужный класс сформированных фабрикой
     * @param int|null $id
     * @return \Git\Module\RegularTraining\General\RegularTraining
     */
    public function buildClass(int $id)
    {
        $obRg = $this->entity = new FactoryRegularTraining($id);
        return $obRg->getClass();
    }

    /**
     * Получить массив данных всех активных элементов из инфоблока
     * @return array
     */
    public function getAll()
    {
        return self::getAllStatic();
    }

    /**
     * Получить массив данных всех элементов из инфоблока
     * @return array
     */
    public function getAllD()
    {
        return self::getAllStaticD();
    }

    /**
     * Получить массив ID всех элементов из инфоблока
     */
    public function getAllId(array $arFilter = [])
    {
        return self::getAllIdStatic($arFilter);
    }

    /**
     * Вернет Список регулярных тренировок вместе с их тренировками
     * Вернет объект для строителя
     * @return array
     */
    public function getAllWithChildren()
    {
        /**
         * Получение рег трени, потом зайти в таблицу связей и забрать все ID детей (тренировок),
         * потом скушать каждый ID для получения объектов Training и получение метода getInfo.
         * Вот только вопрос, допустим у нас 90 тренировок, каждый будет делать запрос в БД для получения инфы
         * плюс доп выборки, такие как пользователи и т.д
         * Это неоправданная нагрузка
         */
        /**
         * У регулярной тренировки может быть несколько типов, а значит и класс будет отличаться, а значит
         * и запись в таблицу будет разной. Нужно получать объект рег трен от фабрики и дальше
         * каждый новый ID тренировки тоже пропускать через фабрику. Зачем? вдруг вывод информации будет отличаться?
         */
        $arRegularId = $this->getAllId($this->arFilter);
        if (!empty($arRegularId))
            foreach ($arRegularId as $RgId)
            {
                $obRg[] = $this->buildClass($RgId);
            }

        return $obRg;
    }

    /**
     * Получение регулярной тренировки по id тренировки
     * @param $id_training
     * @return array
     */
    public function getRgByIdTraining($id_training)
    {
        $obMediator = self::getMediatorTraining();
        $obTraining = $obMediator->buildClass($id_training);
        $obRgTraining = $this->getRgTrainingObj();

        $obFindRgTraining = $this->getElParentByChild($obRgTraining, $obTraining);

        return $obFindRgTraining;
    }

    /**
     * Получение регулярной тренировки по классу тренировки
     * @param IForMediator $obTraining
     * @return array
     */
    public function getRgByObjTraining(IForMediator $obTraining)
    {
        $obRgTraining = $this->getRgTrainingObj();

        $obFindRgTraining = $this->getElParentByChild($obRgTraining, $obTraining);

        return $obFindRgTraining;
    }

    /**
     * Установка фильтра для выборки
     * @param array $arFilter
     */
    public function setFilter(array $arFilter)
    {
        $this->arFilter = $arFilter;
    }

    /**
     * Получить массив ID всех элементов из инфоблока
     * @return array
     */
    public static function getAllIdStatic(array $arFilter = [])
    {
        return RegularTraining::getAllIdStatic($arFilter);
    }

    /**
     * Получить массив данных всех активных элементов из инфоблока
     * @return array
     */
    public static function getAllStatic()
    {
        return RegularTraining::getAllStatic();
    }

    /**
     * Получить массив данных всех элементов из инфоблока
     * @return array
     */
    public static function getAllStaticD()
    {
        return RegularTraining::getAllStaticD();
    }

    /**
     * Вернет куратора тренировок
     * @return mixed
     */
    public function getKurator()
    {
        $rsMediator = new FactoryMediator('User');
        $obMediator = $rsMediator->getClass();
        $obKurator = $obMediator->getMainKurator();

        return $obKurator;
    }

    /**
     * Получение списка тренировок по регулярной тренировке
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getTrainingsInRegularTraining(IForMediator $obParent, bool $convert_in_array = true)
    {
        $mediatorTraining = self::getMediatorTraining();

        $arTrainig = $mediatorTraining->getTrainingByParent($obParent, $convert_in_array);

        return $arTrainig;
    }

    /**
     * Получение одной тренировки внутри регулярной
     * @param $id
     */
    public function getTrainingByIdInRegular($id)
    {
        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTrainings[] = $obMediator->buildClass($id);
        return $obTrainings;
    }

    /**
     * Вернет количество Тренировок прошедших
     *
     * @return array
     */
    public function getTrainingCount()
    {
        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTrainings[] = $obMediator->buildClass($id);
        return $obTrainings;
    }

    /**
     * Промежуточный метод для добавления тренировок в регулярную тренировку (нужен для контроллера)
     * @param int $id_regular
     * @param array $arTraining
     * @return mixed
     */
    public function addTrainingInRegularById(int $id_regular, array $arTraining)
    {
        $obRgTraining = $this->buildClass($id_regular);
        return $obRgTraining->addTraining($arTraining);
    }

    /**
     * Записать тренировки в регулярную тренировку
     * @param IForMediator $obParent
     * @param array $arChild
     */
    public function addTraining(IForMediator $obParent, array $arChild)
    {
        $mediatorTraining = self::getMediatorTraining();

        $arResult['ERRORS'] = [];

        foreach ($arChild as $child)
        {
            $obTraining = $mediatorTraining->getTrainingObj($child);
            if (!empty($obTraining->getId()))
            {
                $tmpResult = $this->add($obParent, $obTraining);
                $tmpError = $tmpResult->getErrorMessages();

                if ($tmpResult->isSuccess())
                {
                    $arResult['SUCCESS'][$obTraining->getId()] = 'Y';
                }
                if (!empty($tmpError))
                    $arResult['ERRORS'] = array_merge($arResult['ERRORS'], $tmpError);

            }
        }

        return $arResult;
    }

    /**
     * Обновить тренера у тренировки если новый отличается от старого
     * @param IForMediator $obRgTraining
     * @param $id_trainer
     * @return array|\Bitrix\Main\Entity\AddResult|mixed|string[]|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function updateTrainerById(IForMediator $obRgTraining, $id_trainer)
    {
        $arTrainerData = $this->getTrainer($obRgTraining, true)[0];
        $rsMediator = new FactoryMediator('User');
        $obMediator = $rsMediator->getClass();
        $obTrainer = $obMediator->buildClass($id_trainer);
        $arTrainer = $obTrainer->getInfo();

        if (empty($obTrainer->getId())) {
            $arResult = [
                'ERROR' => 'Пользователя не существует'
            ];
            return $arResult;
        } elseif ($arTrainer['TYPE'] != 'trainer') {
            $arResult = [
                'ERROR' => 'Пользователь не является тренером'
            ];
            return $arResult;
        }

        if (empty($arTrainerData)) {
            $arResult = $this->setTrainer($obRgTraining, $id_trainer);
        } elseif ($arTrainerData[$this->arFields['CHILD_ID']] != (int)$id_trainer) {
                $arData = [
                    $this->arFields['CHILD_ID'] => $id_trainer
                ];
                $result = $this->update($arTrainerData['ID'], $arData);
            if (!$result->isSuccess())
                $arResult['ERROR'] = $result->getErrorMessages();
        }

        return $arResult;
    }

    /**
     * Получение тренера для тренировки
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getTrainer(IForMediator $obParent, bool $need_array = false)
    {
        $userResponseName = self::getMediatorUser();
        return $userResponseName->getTrainerByParent($obParent, $need_array);
    }

    /**
     * Получить тренера тренировки
     * @param IForMediator $obTraining
     * @return mixed
     */
    public function getTrainerByTraining(IForMediator $obTraining)
    {
        // Найти регулярную тренировку по id тренировки
        $obRgTraining = $this->getRgTrainingObj();
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obRgTraining->getName()),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['CHILD_ID'] => $obTraining->getId()
        ];

        $arList = $this->get($arData);

        if (!empty($arList[0][$this->arFields['PARENT_ID']]))
        {
            $obRg = $this->buildClass($arList[0][$this->arFields['PARENT_ID']]);
            if (!empty($obRg))
                $obTrainer = $this->getTrainer($obRg);
        }

        return $obTrainer;
    }

    /**
     * Установка тренера для тренировки
     * @param IForMediator $obTraining
     * @param int $id_trainer
     * @return array|\Bitrix\Main\Entity\AddResult|mixed|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setTrainer(IForMediator $obRgTraining, int $id_trainer)
    {
        $userResponseName = self::getMediatorUser();
        $obTrainer = $userResponseName->buildClass($id_trainer, 'trainer');

        $result = $this->add($obRgTraining, $obTrainer);

        if (!$result->isSuccess())
            $arResult['ERRORS'] = $result->getErrorMessages();
        else
            $arResult['SUCCESS'] = 'Y';

        return $arResult;
    }

    /**
     * Установка тренера для тренировки по Ид
     * @param int $id_rg_training
     * @param int $id_trainer
     * @return array|\Bitrix\Main\Entity\AddResult|mixed|void
     */
    public function setTrainerFromId(int $id_rg_training, int $id_trainer)
    {
        $obRgTraining = $this->buildClass($id_rg_training);
        $result = $this->setTrainer($obRgTraining, $id_trainer);

        return $result;
    }

    /**
     * Получение макс кол участников в регулярной тренировке
     * @param ITraining $obTraining
     * @return int
     */
    public function getMaxMemberInRegTraining(ITraining $obTraining)
    {
        $count = 0;

        $obRgTraining = $this->getRgTrainingObj();
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obRgTraining->getName()),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['CHILD_ID'] => $obTraining->getId()
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
        {
            foreach ($arList as $item)
                $arId[] = $item[$this->arFields['PARENT_ID']];

            $arRgTraining = RegularTraining::getListById($arId);
            $count = (int)$arRgTraining[0]['PROPERTIES']['NUMBER_OF_PLACES']['VALUE'];
        }

        return $count;
    }

    /**
     * Вернет все медиа внутри тренировок данной регулярки
     * @param IForMediator $obRgTraining
     */
    public function getAllMediaInRgTraining(IForMediator $obRgTraining)
    {
        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $arObMedia = $obMediator->getAllMediaByRgTraining($obRgTraining);

        return $arObMedia;
    }

    /**
     * Получение объекта регулярной тренировки, например для фильтрации по названию класса
     * @return RegularTraining
     */
    public function getRgTrainingObj()
    {
        // Непонятно как получать класс когда регл. тренировок будет несколько типов
        return new RegularTraining();
    }
}
?>