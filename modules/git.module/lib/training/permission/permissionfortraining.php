<?php
namespace Git\Module\Training\Permission;

use Bitrix\Main\Diag\Debug;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Training\Mediator\MediatorForTraining;

/**
 * Класс собирающий права отдельного пользователя по отношению к тренировке
 * Class PermissionForTraining
 * @package Git\Module\Training\Permission
 */
class PermissionForTraining
{
    private $user_id;
    public $detail;
    private $arPermission;
    private $obTraining;
    private $arTraining;
    private $obChild;
    private $arChild;
    private $statusUser;
    private $arStatusUser = [
        'READY' => 'READY', // Не записан и могу записаться
        'ALREADY' => 'ALREADY', // Уже записан
        'MISSED' => 'MISSED', // Записан но пропустил
        'LATE' => 'LATE', // Прошла
        'COMPLETE' => 'COMPLETE', // Участвовал
        'BANNED' => 'BANNED', // Бан, ничего не может
        'IS_FULL' => 'IS_FULL', // Мест нет
        'LOCK' => 'LOCK'
    ];
    private $arStatusTrainer = [
        'WAIT_USERS' => 'WAIT_USERS', // Запись открыта
        'IS_FULL' => 'IS_FULL', // Мест нет
        'CLOSE' => 'CLOSE', // Закрыл
        'NO_CLOSE' => 'NO_CLOSE' // Не закрыл
    ];

    public function __construct(IForMediator $obTraining, IForMediator $obChild)
    {
        $this->obTraining = $obTraining;
        $this->obChild = $obChild;

        if (empty($obChild->getId()) || empty($obTraining->getId()))
            return new \Exception('obUser or obTraining is empty');

        $this->user_id = $this->obChild->getId();
        $this->arTraining = $this->obTraining->getInfo();
        $this->arChild = $this->obChild->getInfo();
    }

    public function isCanSetTrainer()
    {
        return true;
    }

    public function isCanAddTrainigInRegular()
    {
        return true;
    }

    public function isCanDelTraining()
    {
        return true;
    }

    public function isCanAddTraining()
    {
        return true;
    }

    /**
     * Может ли пользователь редактировать медиа тренировки
     * @return bool|string
     */
    public function isCanEditPicture()
    {
        $return = false;

        if ($this->arChild['TYPE'] == 'kurator')
            $return = true;
        elseif ($this->arChild['TYPE'] == 'trainer') {
            if ($this->isTrainingActive() && $this->arTraining['PROPERTIES']['FINISH']['VALUE'] != 'Y')
                $return = true;
        }

        return $return;
    }

    public function getAllPermissions()
    {
        $this->isCanJoin();

        return $this->arPermission;
    }

    /**
     * Может ли пользователь менять статус участника тренировки
     * @return bool
     */
    public function canChangeStatusMember()
    {
        $return = false;
        if (
            $this->arTraining['PROPERTIES']['OPEN']['VALUE'] == 'N'
            && ($this->arChild['TYPE'] == 'trainer'
                || $this->arChild['TYPE'] == 'kurator')
        )
        {
            $return = true;
        }

        return $return;
    }

    /**
     * Текстовое представление возможности пользователя
     * @return string|null
     */
    public function whatCanUser()
    {
        $return = null;

        if ($this->arChild['TYPE'] == 'trainer' || $this->arChild['TYPE'] == 'kurator') {

            if ($this->isTrainingOpen())
            {
                if ($this->isTrainingFull())
                    $return = $this->arStatusTrainer['IS_FULL'];
                else
                    $return = $this->arStatusTrainer['WAIT_USERS'];

            } else {
                if ($this->isTrainingSubmit())
                    $return = $this->arStatusTrainer['CLOSE'];
                else
                    $return = $this->arStatusTrainer['NO_CLOSE'];
            }

        } else {

            if ($this->isTrainingOpen()) {

                if ($this->isIIssetInTraining())
                    $return = $this->arStatusUser['ALREADY'];
                elseif ($this->isTrainingFull())
                    $return = $this->arStatusUser['IS_FULL'];
                else
                    $return = $this->arStatusUser['READY'];

            } else {

                if ($this->isIIssetInTraining()) {
                    if ($this->statusUser == 'Y') // Участвовал
                        $return = $this->arStatusUser['COMPLETE'];
                    elseif ($this->statusUser == 'N') // Записан но пропустил
                        $return = $this->arStatusUser['MISSED'];
                } else {
                    $return = $this->arStatusUser['LATE'];
                }
            }
        }

        return $return;
    }

    /**
     * Можно ли покинуть тренировку
     * @return bool
     */
    public function isCanLeave()
    {
        $return = false;
        if (
            $this->isTrainingActive() // Проверка на активность тренировки
            && $this->isTrainingOpen() // Открыта ли еще тренировка
        )
        {
            $return = true;
        }

        return $return;
    }

    /**
     * Может ли пользователь участвовать в тренировке
     * @return bool
     */
    public function isCanJoin()
    {
        $this->detail['TYPE'] = $this->arChild['TYPE'];
        $return = false;
        if (
            $this->isTrainingActive() // Проверка на активность тренировки
            && $this->arChild['TYPE'] != 'trainer' // Тренер не может участвовать в своей тренировке
            && !$this->isLockedUserInTable() // Проверка на запрет к участию
            && !$this->isTrainingFull() // Проверка пустых мест
            && $this->isTrainingOpen() // Открыта ли еще тренировка
        )
        {
            $this->arPermission['JOIN'] = 'Y';
            $return = true;
        }

        return $return;
    }

    /**
     * Отправил ли тренер отчет
     * @return bool
     */
    private function isTrainingSubmit()
    {
        if ($this->arTraining['PROPERTIES']['SUBMIT']['VALUE'] == 'Y')
            $return = true;
        else
            $return = false;

        return $return;
    }

    /**
     * Открыта ли тренировка
     * @return bool
     */
    private function isTrainingOpen()
    {
        if ($this->arTraining['PROPERTIES']['OPEN']['VALUE'] == 'Y')
            $return = true;
        else
            $return = false;
        $this->detail['OPEN'] = $this->arTraining['PROPERTIES']['OPEN']['VALUE'];
        return $return;
    }

    /**
     * Записан ли я на тренировку, вернет символ статуса
     * @return bool
     */
    private function isIIssetInTraining()
    {
        $return = false;

        $obMediator = new MediatorForTraining();
        $this->statusUser = $obMediator->checkStatusUserInTraining($this->obTraining, $this->obChild);

        if (!empty($this->statusUser))
            $return = true;

        return $return;
    }

    /**
     * Есть ли место в тренировке для нового участника
     * @return bool
     */
    private function isTrainingFull()
    {
        $return = true;
        $max_count = $this->getMaxMembersInTraining();

        if (count($this->arTraining['MEMBERS']) < (int)$max_count)
            $return = false;
        $this->detail['FULL'] = $return;
        return $return;
    }

    /**
     * Проверка на активность тренировки в таблице связей модулей
     * @return bool
     */
    private function isTrainingActive()
    {
        $return = false;

        $this->detail['ACTIVE'] = $this->arTraining['STATUS_ACTIVE'];
        if ($this->arTraining['STATUS_ACTIVE'] == 'Y')
            $return = true;
        $this->detail['ACTIVE_R'] = $return;
        return $return;
    }

    /**
     * Заблокирован ли пользователь в тренировках
     * @return bool
     */
    public function isLockedUserInTable()
    {
        $return = false;

        $obMediator = new MediatorForTraining();
        $result = $obMediator->getDataFromLockTable($this->obTraining, $this->obChild);
        $max = \COption::GetOptionString('git.module', 'TRAINING_MAX_BLOCKED', '3');
        if (count($result) >= (int)$max)
            $return = true;

        $this->detail['LOCK'] = $return;

        return $return;
    }

    /**
     * Может ли пользователь менять данные тренировки
     * @return bool
     */
    public function isCanEditTrainingFields()
    {
        $return = false;
        if (
            $this->arChild['TYPE'] == 'trainer'
            || $this->arChild['TYPE'] == 'kurator'
        )
        {
            $return = true;
        }

        return $return;
    }

    /**
     * Получение макс кол участников
     */
    private function getMaxMembersInTraining()
    {
        $count = MediatorForTraining::getMaxMemberInTraining($this->obTraining);

        return $count;
    }
}

?>