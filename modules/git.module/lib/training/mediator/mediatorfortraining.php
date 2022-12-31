<?php
namespace Git\Module\Training\Mediator;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\Result;
use CIBlockPropertyEnum;
use CUtil;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Factory\General\FactoryTraining;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\General\Member;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\RegularTraining\General\RegularTraining;
use Git\Module\Tables\lockModulesTable;
use Git\Module\Training\General\Training;
use Git\Module\Training\Interfaces\ITraining;
use Git\Module\Training\Permission\PermissionForTraining;
use Git\Module\User\General\User;

/**
 * Посредник между тренировкой и основным посредником для записи в БД.
 * Этот посредник знает кто у его тренировки участник (пользователь, группа и т.д)
 * Class MediatorForTraining
 * @package Git\Module\Training\Mediator
 */
class MediatorForTraining extends Member
{
    /**
     * Получение макс кол участников у тренировки
     */
    public static function getMaxMemberInTraining(ITraining $obTraining)
    {
        $count = self::getMaxMemberInRegTraining($obTraining);
        return $count;
    }

    /**
     * Получение макс кол участников у регулярной тренировки
     */
    public static function getMaxMemberInRegTraining(ITraining $obTraining)
    {
        $obMediator = self::getMediatorRegTraining();
        $count = $obMediator->getMaxMemberInRegTraining($obTraining);

        return $count;
    }

    /**
     * Вернет количество тренеровок Прошедших
     *
     * @param $id
     */
    private  function getCountTrainingRegular($id)
    {
        $obj = new RegularTraining($id);
        return count($this->getTrainingByParentAll($obj))+1;
    }

    /**
     * Посредник знает кто пытается записаться на тренировку и получает его объект
     * @param IForMediator $obParent
     * @param array $arChild
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addMembers(IForMediator $obParent, array $arChild)
    {
        $userResponse = self::getMediatorUser();

        foreach ($arChild as $child)
        {
            // TODO: Заменить объявление User на его посредника (MediatorForUser)
            $obChild = new User($child);
            $obPermission = new PermissionForTraining($obParent, $obChild);
            if (!empty($obPermission->isCanJoin()))
                $arReturn[] = $this->add($obParent, $obChild);
            else {
                $obResult = new AddResult();
                $obResult->addError(new Error(GetMessage('PERMISSION_DEN') . ' - ' . $obChild->getId()));
                $arReturn[] = $obResult;
            }
        }

        return $arReturn;
    }

    /**
     * Запись пользователя в тренировку по id
     * @param $id_training
     * @param $id_member
     * @param bool $check_permission
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addMembersById($id_training, $id_member, bool $check_permission = true)
    {
        $obMediator = self::getMediatorUser();
        $obChild = $obMediator->buildClass($id_member, 'user');
        $obTraining = $this->buildClass($id_training);
        $obTraining->getMembers();

        $return = new Result();

        if ($check_permission == true) {
            $obPermission = new PermissionForTraining($obTraining, $obChild);
            if (!empty($obPermission->isCanJoin())) {
                $return = $this->add($obTraining, $obChild);
            } else {
                $return->addError(new Error('Нет мест'));
            }
        } else {
            $return = $this->add($obTraining, $obChild);
        }
        $arResult['DETAIL'] = $obPermission->detail;
        if ($return->isSuccess())
            $arResult['SUCCESS'] = 'SUCCESS';
        else {
            $arResult['ERRORS'] = $return->getErrorMessages();
        }

        return $arResult;
    }

    /**
     * @param $id_training
     * @param $id_member
     * @param bool $check_permission
     * @return array
     */
    public function delMembersById($id_training, $id_member, bool $check_permission = true)
    {
        $obMediator = self::getMediatorUser();
        $obChild = $obMediator->buildClass($id_member, 'user');
        $obTraining = $this->buildClass($id_training);

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['PARENT_ID'] => $obTraining->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obChild->getName()),
            $this->arFields['CHILD_ID'] => $obChild->getId()
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
            foreach ($arList as $item)
                $id[] = $item['ID'];

        if ($check_permission == true) {

            $obPermission = new PermissionForTraining($obTraining, $obChild);
            if (!empty($obPermission->isCanLeave()))
                if (!empty($id))
                    $return = $this->del($id);
                else
                    $arResult['ERRORS'][] = GetMessage('NO_USER');
            else
                $arResult['ERRORS'][] = GetMessage('PERMISSION_DEN');

        } else {
            if (!empty($id))
                $return = $this->del($id);
            else
                $arResult['ERRORS'][] = GetMessage('NO_USER');
        }


        if ($return[0]->isSuccess())
            $arResult['SUCCESS'] = 'SUCCESS';
        else
            $arResult['ERRORS'][] = $return->getErrorMessages();

        return $arResult;
    }

    /**
     * Проверить, можно ли изменит статус участника тренировки
     * @param $id_member
     * @param $id_training
     * @param $status
     * @return array|void
     */
    public function changeStatusMember($id_member, $id_training, $status)
    {
        $obTraining = $this->buildClass($id_training);

        $obPermission = $this->getPermissionForUser($obTraining);

        if ($obPermission->canChangeStatusMember()) {
            $arResult = $this->doChangeStatusMember($obTraining, $id_member, $status);
        } else {
            $arResult['ERRORS'] = [GetMessage('PERMISSION_DEN')];
        }

        return $arResult;
    }

    /**
     * Изменит статус участника тренировки
     * @param $obTraining
     * @param $id_member
     * @param $status
     * @return array|void
     */
    private function doChangeStatusMember($obTraining, $id_member, $status)
    {
        if ($status != 'Y') $status = 'N';

        $userResponse = self::getMediatorUser();
        $obUser = $userResponse->getUserObj($id_member);

        if (empty($obUser->getId())) return;

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['PARENT_ID'] => $obTraining->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obUser->getName()),
            $this->arFields['CHILD_ID'] => $obUser->getId()
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
            foreach ($arList as $item)
                $id = $item['ID'];

        $arParam = [
            $this->arFields['ACTIVE'] => $status
        ];
        $result = $this->update((int)$id, $arParam);

        if ($result->isSuccess())
            $arResult['SUCCESS'][$obUser->getId()] = 'Y';
        else
            $tmpError = $result->getErrorMessages();

        if (!empty($tmpError))
            $arResult['ERRORS'] = array_merge($arResult['ERRORS'], $tmpError);

        return $arResult;
    }

    /**
     * Посредник получит список участников, знает что это пользователи
     * @param IForMediator $obParent
     * @param array $arChild
     */
    public function delMembers(IForMediator $obParent, array $arChild)
    {
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['PARENT_ID'] => $obParent->getId()
        ];
        // TODO: Переделать на получение через фабрику, обдумать как получить именно пользователя. Либо через посредника пользователей
        foreach ($arChild as $child)
        {
            $obChild = new User($child);
            if (!empty($obChild->getId()))
            {
                $arData[$this->arFields['CHILD']][] = Utils::formatClassNameForWrite($obChild->getName());
                $arData[$this->arFields['CHILD_ID']][] = $obChild->getId();
            }
        }

        $arList = $this->get($arData);

        if (!empty($arList))
            foreach ($arList as $item)
                $arId[] = $item['ID'];

        $this->del($arId);
    }

    /**
     * Получение участников, запрашиваем их у посредника класса User т.к мы знаем что у Training участники только User
     * @param IForMediator $obTraining
     */
    public function getListMembers(IForMediator $obTraining)
    {
        $userResponseName = self::getMediatorUser();
        return $userResponseName->getUsersByParent($obTraining);
    }

    /**
     * Вернет список всех тренировок
     * @return array|mixed
     */
    public function getAll()
    {
        return self::getAllStatic();
    }

    /**
     * Вернет список всех тренировок
     * @return array
     */
    public static function getAllStatic()
    {
        return Training::getAllStatic();
    }

    /**
     * Вернет объекты тренировок по фильтру родителю
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getTrainingByParent(IForMediator $obParent, bool $convert_in_array = true)
    {
        $obTraining = $this->getTrainingObj();
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['PARENT_ID'] => $obParent->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['ACTIVE'] => 'Y'
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
        {
            foreach ($arList as $arData)
            {
                if ($convert_in_array) {
                    $obTrain = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
                    $arResult[] = $obTrain->getInfo();
                } else {
                    $arResult[] = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
                }
            }
        }

        return $arResult;
    }
    /**
     * Вернет объекты тренировок
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getTrainingByParentAll(IForMediator $obParent, bool $convert_in_array = true)
    {
        $obTraining = $this->getTrainingObj();
        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['PARENT_ID'] => $obParent->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obTraining->getName()),
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
        {
            foreach ($arList as $arData)
            {
                if ($convert_in_array) {
                    $obTrain = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
                    $arResult[] = $obTrain->getInfo();
                } else {
                    $arResult[] = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
                }
            }
        }

        return $arResult;
    }

    /**
     * Сформирует массив инициализированных объектов тренировок, которые активны на данный момент
     * @param bool $convert_to_obj
     * @return array
     */
    public function getActiveTrainingInModuleTable(bool $convert_to_obj = true) : array
    {
        $arObs = [];

        $obRgMediator = self::getMediatorRegTraining();
        $obRgTraining = $obRgMediator->getRgTrainingObj();

        $obTraining = $this->getTrainingObj();

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obRgTraining->getName()),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['ACTIVE'] => 'Y'
        ];

        $arList = $this->get($arData);

        if (!empty($arList)) {
            foreach ($arList as $arOne) {
                if ($convert_to_obj)
                    $arObs[] = $this->buildClass($arOne[$this->arFields['CHILD_ID']]);
                else
                    $arObs[] = $arOne;
            }
        }

        return $arObs;
    }

    /**
     * Закрыть активные тренировки
     * @return array|false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function closeActiveTrainingsInModuleTable()
    {
        $arObsTrainings = $this->getActiveTrainingInModuleTable(false);
        $arResult = [];

        if (empty($arObsTrainings)) return false;

        $arFields[$this->arFields['ACTIVE']] = 'N';

        foreach ($arObsTrainings as $arTraining) {
            $result = $this->update($arTraining['ID'], $arFields);

            if (!$result->isSuccess())
                $arResult['ERRORS'][$arTraining['ID']] = $result->getErrorMessages();
        }

        if (empty($arResult['ERRORS']))
            $arResult['SUCCESS'] = 'Y';

        return $arResult;
    }

    /**
     * Вернет объект тренировки
     * @return Training
     */
    public function getTrainingObj(int $id = null)
    {
        $obj = new Training($id);
        return $obj;
    }

    /**
     * Возвращает нужный класс сформированных фабрикой
     * @param int $id
     * @return mixed
     */
    public function buildClass(int $id) : ITraining
    {
        $obRg = $this->entity = new FactoryTraining($id);
        return $obRg->getClass();
    }

    /**
     * Получение тренера для тренировки
     * @param IForMediator $obParent
     * @return mixed
     */
    public function getTrainer(IForMediator $obParent)
    {
        $userResponseName = self::getMediatorRegTraining();
        return $userResponseName->getTrainerByTraining($obParent);
    }

    /**
     * Вернет куратора
     * @return array
     */
    public function getKurator()
    {
        $rsMediator = new FactoryMediator('User');
        $obMediator = $rsMediator->getClass();
        $obKurator = $obMediator->getMainKurator();

        return $obKurator;
    }

    /**
     * Установка тренера для тренировки
     * @deprecated Перенесли в регулярную тренировку
     * @param IForMediator $obTraining
     * @param int $id_trainer
     * @return array|\Bitrix\Main\Entity\AddResult|mixed|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setTrainer(IForMediator $obTraining, int $id_trainer)
    {
        $userResponseName = self::getMediatorUser();
        $obTrainer = $userResponseName->buildClass($id_trainer);

        $result = $this->add($obTraining, $obTrainer);

        return $result;
    }

    /**
     * Установка тренера для тренировки по Ид
     * @deprecated Перенесли в регулярную тренировку
     * @param int $id_training
     * @param int $id_trainer
     * @return array|\Bitrix\Main\Entity\AddResult|mixed|void
     */
    public function setTrainerFromId(int $id_training, int $id_trainer)
    {
        $obTraining = $this->buildClass($id_training);
        $result = $this->setTrainer($obTraining, $id_trainer);

        return $result;
    }

    /**
     * Объект прав отдельного пользователя
     * @param IForMediator $obTraining
     * @param null $id_user
     * @return PermissionForTraining
     */
    public function getPermissionForUser(IForMediator $obTraining, $id_user = null)
    {
        if ($id_user == null)
        {
            global $USER;
            if ($USER->isAuthorized())
                $id_user = $USER->GetID();
        }

        if ($id_user != null && !empty($obTraining->getId()))
        {
            $obTrainer = $this->getTrainer($obTraining);

            if ($obTrainer[0]->getId() == $id_user)
                $is_trainer = 'Y';

            $obMediator = self::getMediatorUser();

            if ($is_trainer == 'Y')
                $obUser = $obMediator->getTrainerObj($id_user);
            else
            {
                $arGroups = explode(',', $USER->GetGroups());
                if (in_array(KURATOR_GROUP, $arGroups))
                    $obUser = $obMediator->getKuratorObj($id_user);
                else
                    $obUser = $obMediator->getUserObj($id_user);
            }

            $obPermission = new PermissionForTraining($obTraining, $obUser);

            return $obPermission;
        }
    }

    /**
     * Может ли пользователь редактировать медиа тренировки
     * @param IForMediator $obTraining
     * @param null $id_user
     * @return bool
     */
    public function isCanUserUploadMedia(IForMediator $obTraining, $id_user = null)
    {
        $obPermission = $this->getPermissionForUser($obTraining, $id_user);
        return $obPermission->isCanEditPicture();
    }

    /**
     * Получение текстового представления возможности пользователя для отдельной тренировки
     * @param IForMediator $obTraining
     * @param null $id_user
     * @return string|null
     */
    public function getWhatCanUser(IForMediator $obTraining, $id_user = null)
    {
        if ($id_user == null)
        {
            global $USER;
            if ($USER->isAuthorized())
                $id_user = $USER->GetID();
        }

        if ($id_user != null)
        {
            $obTrainer = $this->getTrainer($obTraining);
            $obMediator = self::getMediatorUser();

            $obKurator = $obMediator->getMainKurator();
            if (!empty($obKurator['RESULT']))
                $id_kurator = $obKurator['RESULT']->getId();

            if ($obTrainer[0]->getId() == $id_user)
                $is_trainer = 'Y';

            if ($is_trainer == 'Y')
                $obUser = $obMediator->getTrainerObj($id_user);
            elseif ($id_user == $id_kurator)
                $obUser = $obMediator->getKuratorObj($id_user);
            else
                $obUser = $obMediator->getUserObj($id_user);

            $obPermission = new PermissionForTraining($obTraining, $obUser);

            return $obPermission->whatCanUser();
        }
    }

    /**
     * Поиск пользователя в тренировке
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return bool
     */
    public function checkStatusUserInTraining(IForMediator $obParent, IForMediator $obChild)
    {
        $obMediator = self::getMediatorUser();
        $obUser = $obMediator->getUserObj($obChild->getId());

        $arParams = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obParent->getName()),
            $this->arFields['PARENT_ID'] => $obParent->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obUser->getName()),
            $this->arFields['CHILD_ID'] => $obUser->getId()
        ];
        $arResult = $this->get($arParams);

        $status = $arResult[0]['UF_ACTIVE'];

        return $status;
    }

    /**
     * Получение активности тренировки в таблице связей модулей
     * @param ITraining $obTraining
     */
    public function getStatusTraining(ITraining $obTraining)
    {
        $obMediator = self::getMediatorRegTraining();
        $obRgTraining = $obMediator->getRgTrainingObj();

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obRgTraining->getName()),
            $this->arFields['CHILD_ID'] => $obTraining->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obTraining->getName())
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
        {
            foreach ($arList as $arData)
            {
                $status = $arData['UF_ACTIVE'];
            }
        }

        return $status;
    }

    /**
     * Получение записей из таблицы блокировок
     * @param IForMediator $obParent
     * @param IForMediator $obChild
     * @return array
     */
    public function getDataFromLockTable(IForMediator $obParent, IForMediator $obChild)
    {
        $arFields = [
            'filter' => [
                'UF_PARENT_ENTITY' => Utils::formatClassNameForWrite($obParent->getName()),
                'UF_CHILD_ENTITY' => Utils::formatClassNameForWrite($obChild->getName()),
                'UF_CHILD_ID' => Utils::formatClassNameForWrite($obChild->getId()),
                'UF_ACTIVE' => 'Y'
            ]
        ];
        $rsUsers = lockModulesTable::getList($arFields);

        while ($arUser = $rsUsers->fetch())
        {
            $arUsers[] = $arUser;
        }

        return $arUsers;
    }

    /**
     * Проверка, может ли пользователь редактировать поля тренировки
     * @param $id_training
     * @param array $arFields
     * @return array|void
     */
    public function editTrainingFields($id_training, array $arFields)
    {
        $obTraining = $this->buildClass($id_training);

        if (empty($obTraining->getId())) return;

        $obPermission = $this->getPermissionForUser($obTraining);

        if ($obPermission->isCanEditTrainingFields()) {

            $arResult = $this->doEditTrainingFields($obTraining, $arFields);

        } else {
            $arResult['ERRORS'][] = GetMessage('PERMISSION_DEN');
        }

        return $arResult;
    }

    /**
     * Редактирование полей тренировки
     * @param ITraining $obTraining
     * @param array $arFields
     * @return array
     */
    private function doEditTrainingFields(ITraining $obTraining, array $arFields)
    {
        if (!empty($arFields['DESCRIPTION'])) {

            if ($arFields['NEED_NOTIFY_MEMBERS'] == 'Y')
                $obTraining->submitNote();

            $obTraining->editDescription($arFields['DESCRIPTION']);
        }
        $arResult['SUCCESS'] = 'Y';

        return $arResult;
    }

    /**
     * Завершение тренировки тренером (отправка отчета). Проверки и формирование ответа
     * @param $id_training
     * @return array
     */
    public function endTraining($id_training)
    {
        $obMedia = $this->getMediaInTrainings([$id_training]);
        $obTraining = $this->buildClass($id_training);
        $arObMembers = $obTraining->getMembers();

        if (empty($obMedia) && count($arObMembers) > 0) {
            $arResult['ERRORS'][] = GetMessage('NO_MEDIA');
            $arResult['ERRORS_CODE'][] = 'NO_MEDIA';
        }

        if (empty($obTraining->getId())) {
            $arResult['ERRORS'][] = GetMessage('TRAINING_IS_EMPTY');
            $arResult['ERRORS_CODE'][] = 'TRAINING_IS_EMPTY';
        }

        if (empty($arResult['ERRORS'])) {
            $obTraining->submitNote();
            $result = $obTraining->endTraining();
        }

        if ($result == false && empty($arResult['ERRORS'])) {
            $arResult['ERRORS'][] = GetMessage('TRAINING_IS_OPEN');
            $arResult['ERRORS_CODE'][] = 'TRAINING_IS_OPEN';
        }

        if (empty($arResult['ERRORS']))
            $arResult['SUCCESS'] = 'Y';

        return $arResult;
    }

    /**
     * Вернет медиа тренировки
     * @param ITraining $obTraining
     * @return array
     */
    public function getMediaInTraining(ITraining $obTraining)
    {
        $obMediator = self::getMediatorMedia();

        $arObMedia = $obMediator->getMediaInTraining($obTraining);

        if (!empty($arObMedia))
            foreach ($arObMedia as $obMedia)
                $arResult[] = $obMedia;

        return $arResult;
    }

    /**
     * Вернет все медиа по регулярной тренировке
     * @param IForMediator $obRgTraining
     */
    public function getAllMediaByRgTraining(IForMediator $obRgTraining)
    {
        $obTraining = $this->getTrainingObj();
        $arTrainings = $this->getElsChildByParent($obRgTraining, $obTraining, true);

        if (!empty($arTrainings))
            foreach ($arTrainings as $arTraining)
                $arId[] = $arTraining[$this->arFields['CHILD_ID']];

        if (!empty($arId))
            $arObMedia = $this->getMediaInTrainings($arId);

        return $arObMedia;
    }

    /**
     * Получить медиа в тренировках
     * @param $id_training
     * @return array
     */
    public function getMediaInTrainings(array $id_training)
    {
        $obMediator = self::getMediatorMedia();

        $arObMedia = $obMediator->getMediaInTrainings($id_training);

        if (!empty($arObMedia))
            foreach ($arObMedia as $obMedia)
                if ($obMedia->getId())
                    $arResult[] = $obMedia;

        return $arResult;
    }

    /**
     * Создание тренеровки по заданному шаблону
     *
     * @param $arParamsTemplate
     */
    public function createTraining($arParamsTemplate)
    {
        $arParams = [];
        $obTraining = $this->getTrainingObj();
        $arParams['CODE'] = $arParamsTemplate['CODE'];
        $arParams['NAME'] = $arParamsTemplate['NAME'];

        $dateNextEvent = self::findNearestDayOfWeek((int)$arParamsTemplate['PROPERTIES']['DAY']['VALUE'],'');
        $dateTimeNextEvent = $dateNextEvent .  ((count($arTime = explode(' ', $arParamsTemplate['PROPERTIES']['TIME']['VALUE'])) > 1) ? $arTime[1] : '');
        if(strtotime($dateTimeNextEvent) <= time()){
            $dateNextEvent = self::findNearestDayOfWeek((int)$arParamsTemplate['PROPERTIES']['DAY']['VALUE']);
        }

        if(
            (strtotime($arParamsTemplate['PROPERTIES']['DATA_FROM']['VALUE'])   <= strtotime($dateNextEvent)) &&
            (strtotime($dateNextEvent)                                          <= strtotime($arParamsTemplate['PROPERTIES']['DATA_TO']['VALUE']))
        ){
            $arParams['PROPERTY_VALUES']['DATETIME'] = $dateNextEvent . ' ' .  ((count($arTime = explode(' ', $arParamsTemplate['PROPERTIES']['TIME']['VALUE'])) > 1) ? $arTime[1] : '');
        } else {
            $arLog = [
                "SEVERITY" => "TRAINING",
                "AUDIT_TYPE_ID" => "CREATE_TRAINING_DATETIME",
                "MODULE_ID" => "git.module",
                "ITEM_ID" => $arParamsTemplate['ID'],
                "DESCRIPTION" => 'Training is end. Active to: ' . $arParamsTemplate['PROPERTIES']['DATA_TO']['VALUE'],
            ];
            \CEventLog::Add($arLog);
        }
        $arParams['PROPERTY_VALUES']['TYPE'] = 'COMMON';
        $arParams['PROPERTY_VALUES']['DESCRIPTION'] = $arParamsTemplate['PROPERTIES']['DESCRIPTION']['VALUE'];
        $arParams['PROPERTY_VALUES']['OPEN'] = 'Y';

        $arParams['PROPERTY_VALUES']['COUNT'] = $this->getCountTrainingRegular($arParamsTemplate['ID'])+1;

        $arParams['CODE'] = CUtil::translit($arParams['CODE'] . '_' . $arParams['PROPERTY_VALUES']['DATETIME'], "ru");
        $arParams['CODE'] .= '_' . time();

        return $obTraining->createTraining($arParams);
    }

    /**
     * Венет тренировку по медиа файлу
     * @param IForMediator $obMedia
     * @return array
     */
    public function getTrainingByMedia(IForMediator $obMedia)
    {
        $obTraining = $this->getTrainingObj();

        if (!empty($obMedia->getId()))
            $arObList = $this->getElParentByChild($obTraining, $obMedia);

        return $arObList;
    }

    /**
     * Получить все тренировки в регулярной по объекту медиа
     * @param IForMediator $obMedia
     * @return mixed
     */
    public function getAllTrainingsInRgTrainingByMedia(IForMediator $obMedia)
    {
        $obTrainingEmpty = $this->getTrainingObj();

        if (!empty($obMedia->getId()))
            $obTraining = $this->getElParentByChild($obTrainingEmpty, $obMedia)[0];

        if (!empty($obTraining->getId()))
            $obRgTraining = $this->getRgTrainingByTraining($obTraining);

        if (!empty($obRgTraining->getId()))
            $arObTrainings = $this->getElsChildByParent($obRgTraining, $obTraining);

        return $arObTrainings;
    }

    /**
     * Получение регулярной тренировки по объекту тренировки
     * @param IForMediator $obTraining
     * @return mixed
     */
    public function getRgTrainingByTraining(IForMediator $obTraining)
    {
        $rsMediator = new FactoryMediator('RegularTraining');
        $obMediator = $rsMediator->getClass();
        $obRgTraining = $obMediator->getRgByObjTraining($obTraining)[0];

        return $obRgTraining;
    }

    /**
     * Получение тренировок по их id и id участников
     * @param array $arId
     * @param array $arIdUsers
     * @return mixed
     */
    public function getTrainingsByIdAndIdUsers(array $arId, array $arIdUsers)
    {
        $rsMediator = new FactoryMediator('User');
        $obMediator = $rsMediator->getClass();

        $obEmptyUser = $obMediator->getUserObj();
        $obEmptyTraining = $this->getTrainingObj();

        $arFilter = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obEmptyTraining->getName()),
            $this->arFields['PARENT_ID'] => $arId,
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obEmptyUser->getName()),
            $this->arFields['CHILD_ID'] => $arIdUsers
        ];

        $arList = $this->get($arFilter, false, ['ID' => 'ASC']);

        if (!empty($arList))
            foreach ($arList as $item)
                $arIds[$item[$this->arFields['PARENT_ID']]] = $item[$this->arFields['PARENT_ID']];

        if (!empty($arIds))
            foreach ($arIds as $id)
                $obTrainings[] = $this->buildClass($id);

        return $obTrainings;
    }

    /**
     * Получение списка завершенных тренировок в таблице
     * @param array $arId
     * @return mixed
     */
    public function getTrainingsByIdRegularInTable(array $arId)
    {
        $rsMediator = new FactoryMediator('RegularTraining');
        $obMediator = $rsMediator->getClass();
        $obEmptyRgTraining = $obMediator->getRgTrainingObj();

        $obEmptyTraining = $this->getTrainingObj();

        $arFilter = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obEmptyRgTraining->getName()),
            $this->arFields['PARENT_ID'] => $arId,
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obEmptyTraining->getName())
        ];

        $arList = $this->get($arFilter, false, ['ID' => 'ASC']);

        unset($arId);

        if (!empty($arList))
            foreach ($arList as $item) {
                $arId[$item[$this->arFields['CHILD_ID']]] = $item[$this->arFields['CHILD_ID']];
            }

        return $arId;
    }

    /**
     * Вернет число дня на след, неделе
     * @param $dayOfWeek
     *
     * @return false|string
     * @todo Нужно вынести от сюда!!!
     * TODO: Уноси ноги функция, пока жива!!!
     */
    public function findNearestDayOfWeek(int $dayOfWeek, $strAddDay = '+1 week')
    {
        $daysOfWeek = array(
            1=>'Monday',
            2=>'Tuesday',
            3=>'Wednesday',
            4=>'Thursday',
            5=>'Friday',
            6=>'Saturday',
            7=>'Sunday',
        );
        return date('d.m.Y', strtotime( $daysOfWeek[$dayOfWeek] . ' this week ' . $strAddDay));
    }
}
?>