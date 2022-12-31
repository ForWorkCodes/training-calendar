<?php
namespace Git\Module\User\Mediator;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\General\Member;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Modules\LockModule\Mediator\MediatorLockTable;
use Git\Module\User\Factory\FactoryUser;
use Git\Module\User\General\Kurator;
use Git\Module\User\General\Trainer;
use Git\Module\User\General\User;

class MediatorForUser extends Member
{
    /**
     * @deprecated
     * @param array $arId
     * @return mixed
     */
    public function getListItemsById(array $arId)
    {
        return User::getListById($arId);
    }

    public function checkIdForResponse($id)
    {

    }

    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * Вернет всех пользователей которые состоят в группе "Тренер"
     */
    public function getAllTrainers()
    {
        $obTrainer = $this->getTrainerObj();
        return $obTrainer->getAll();
    }

    public static function getAllStatic()
    {
        // TODO: Implement getAllStatic() method.
    }

    /**
     * Возвращает нужный класс сформированных фабрикой
     * @param int $id
     * @return mixed
     */
    public function buildClass(int $id, string $type = null) : IForMediator
    {
        $obRg = $this->entity = new FactoryUser($id, $type);
        return $obRg->getClass();
    }

    /**
     * Получить типы активного пользователя
     * @return array|false
     */
    public function getTypeMyUser()
    {
        global $USER;
        if (!$USER->isAuthorized()) return false;

        $arType = [];
        $arGroups = \CUser::GetUserGroup($USER->GetID());

        if ( in_array(TRAINER_GROUP, $arGroups) )
            $arType['isTrainer'] = true;
        if ( in_array(KURATOR_GROUP, $arGroups) )
            $arType['isKurator'] = true;

        return $arType;
    }

    /**
     * Получение тренеров отдельной тренировки
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getTrainerByParent(IForMediator $obParent, bool $need_array = false)
    {
        $obTrainer = $this->getTrainerObj();
        return $this->getElsChildByParent($obParent, $obTrainer, $need_array);
    }

    /**
     * Получение списка пользователей по объекту родителя
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getUsersByParent(IForMediator $obParent)
    {
        $obUser = $this->getUserObj();
        $arUsers = $this->getElsChildByParent($obParent, $obUser, true);
        if (!empty($arUsers)) {
            foreach ($arUsers as $arUser) {
                $tmpUser = $this->getUserObj($arUser[$this->arFields['CHILD_ID']]);
                $tmpUser->setStatus($arUser[$this->arFields['ACTIVE']]);
                $obUsers[] = $tmpUser;
            }
        }
        return $obUsers;


    }

    /**
     * Вернет объект тренера
     * @param int|null $id
     * @return Trainer
     */
    public function getTrainerObj(int $id = null)
    {
        $obj = new Trainer($id);
        return $obj;
    }

    /**
     * Вернет объект пользователя
     * @param int|null $id
     * @return User
     */
    public function getUserObj(int $id = null)
    {
        $obj = new User($id);
        return $obj;
    }

    /**
     * Вернет объект куратора
     * @param int|null $id
     * @return Kurator
     */
    public function getKuratorObj(int $id = null)
    {
        $obj = new Kurator($id);
        return $obj;
    }

    /**
     * @return array
     */
    public function getMainKurator()
    {
        $obEmptyKurator = $this->getKuratorObj();
        $arSimpleKurator = $obEmptyKurator->getMainKurator();

        $obKurator = $this->getKuratorObj($arSimpleKurator['ID']);

        if (!empty($obKurator->getId()))
            $arResult['RESULT'] = $obKurator;
        else
            $arResult['ERROR'] = 'Empty';

        return $arResult;
    }

    /**
     * Получить список всех участников тренировок по строке поиска
     * @param string $string
     * @return array
     */
    public function getAllMembersByString(string $string)
    {
        $arFindUsersInUserTable = $this->getUsersInUserTableByString($string);

        if (!empty($arFindUsersInUserTable)) {
            foreach ($arFindUsersInUserTable as $arUser)
                $arId[] = $arUser['ID'];
            $arUserInModule = $this->getAllMembersInModuleTableByString($arId);
        }

        if (!empty($arFindUsersInUserTable))
            foreach ($arFindUsersInUserTable as $arUser) {
                if (!empty($arUserInModule[$arUser['ID']][$this->arFields['CHILD_ID']])) {
                    $obUsers[] = $this->buildClass($arUserInModule[$arUser['ID']][$this->arFields['CHILD_ID']], 'user');

                }
            }

        return $obUsers;
    }

    /**
     * Получить список всех пользователей битрикс по строке поиска
     * @param string $string
     * @return mixed
     */
    public function getUsersInUserTableByString(string $string)
    {
        $obUser = $this->getUserObj();
        $return = $obUser->getAllByString($string);

        return $return;
    }

    public function getAllMembersInModuleTableByString(array $arId)
    {
        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTraining = $obMediator->getTrainingObj();
        $obUser = $this->getUserObj();

        $arFilter = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obUser->getName()),
            $this->arFields['CHILD_ID'] => $arId
        ];

        $arList = $this->get($arFilter);

        if (!empty($arList))
            foreach ($arList as $arData) {
                $arResult[$arData[$this->arFields['CHILD_ID']]] = $arData;
            }

        return $arResult;
    }

    /**
     * Получить данные блокировки активного пользователя в тренировках
     * @return array
     */
    public function getDataFromLockTableForThisUser()
    {
        $id_user = null;
        $arResult = [];

        global $USER;
        if ($USER->isAuthorized())
            $id_user = $USER->GetID();

        if ($id_user != null) {
            $obMediator = self::getMediatorTraining();
            $obTraining = $obMediator->getTrainingObj();
            $obMediatorLock = new MediatorLockTable();

            $obUser = $this->getUserObj($id_user);

            if (!empty($obUser->getId())) {
                $arUsers = $obMediator->getDataFromLockTable($obTraining, $obUser);
                $max = (int)\COption::GetOptionString('git.module', 'TRAINING_MAX_BLOCKED', '3');
                $real = count($arUsers);

                $lockModuleActive = (bool)\COption::GetOptionString('git.module', 'TRAINING_BLOCKED_ACTIVE', false);
                $left = $max - $real;

                if ($left <= 0 && $lockModuleActive) {
                    $arResult['SUCCESS']['LEFT'] = true;
                    $time = $obMediatorLock->getEndDateLockUserInTraining($arUsers);

                    if (!empty($time))
                        $arResult['SUCCESS']['DATE'] = $time;
                }
                else
                    $arResult['SUCCESS']['EMPTY'] = 'Y';

            } else {
                $arResult['ERRORS'][] = GetMessage('NO_USER');
                $arResult['ERRORS_CODE'][] = 'NO_USER';
            }
        } else {
            $arResult['ERRORS'][] = GetMessage('NO_USER');
            $arResult['ERRORS_CODE'][] = 'NO_USER';
        }

        return $arResult ;
    }

    /**
     * Добавить пользователя который не зарегистрирован
     * @param $arFields
     * @return User
     */
    public function addUnregisterUser($arFields)
    {
        $obUser = $this->getUserObj();
        $obUser->addUnregisterUser($arFields);

        return $obUser;
    }
}
?>