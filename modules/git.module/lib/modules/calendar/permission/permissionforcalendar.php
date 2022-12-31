<?php
namespace Git\Module\Modules\Calendar\Permission;

use Bitrix\Main\Diag\Debug;

class PermissionForCalendar
{
    protected $arUser;
    protected $idGroupModerator;
    protected $idGroupSuperModerator;
    /**
     * Может ли получить список черновиков
     * @var
     */
    protected $isCanGetDraft;
    /**
     * Может ли задать IMPORTANT для события
     * @var
     */
    protected $isCanSetImportant;
    /**
     * Может ли пользователь добавлять новые события
     * @var
     */
    protected $isCanAdd;
    /**
     * Может ли пользователь удалить событие
     * @var
     */
    protected $isCanDel;
    /**
     * Может ли пользователь обновить событие
     * @var
     */
    protected $isCanUpdate;

    public function __construct()
    {
        global $USER;

        if ($USER->isAuthorized()) {

            $rsUsers = \Bitrix\Main\UserTable::getList([
                'filter' => [
                    'ID' => $USER->GetID()
                ],
                'select' => ['*', 'UF_*']
            ]);

            while ($arUser = $rsUsers->fetch())
            {
                $this->arUser = $arUser;
            }

            $this->arUser['GROUPS'] = $USER->GetUserGroupArray();

            $this->getUserAccess();
        }

    }

    /**
     * Получение ID группы модератора календаря
     * @return mixed
     */
    protected function getGroupIdModerator()
    {
        if (empty($this->idGroupModerator)) {
            $rsGroups = \CGroup::GetList ($by = "c_sort", $order = "asc", Array ("STRING_ID" => 'moderator_calendar'));
            $arGroup = $rsGroups->Fetch();
        } else {
            $arGroup['ID'] = $this->idGroupModerator;
        }

        return $this->idGroupModerator = $arGroup['ID'];
    }

    /**
     * Получение ID группы супер модератора календаря
     * @return mixed
     */
    protected function getGroupIdSuperModerator()
    {
        if (empty($this->idGroupSuperModerator)) {
            $rsGroups = \CGroup::GetList ($by = "c_sort", $order = "asc", Array ("STRING_ID" => 'super_moderator_calendar'));
            $arGroup = $rsGroups->Fetch();
        } else {
            $arGroup['ID'] = $this->idGroupSuperModerator;
        }

        return $this->idGroupSuperModerator = $arGroup['ID'];
    }

    protected function getUserAccess()
    {
        $this->getGroupIdModerator();
        $this->getGroupIdSuperModerator();

        if (in_array($this->idGroupModerator, $this->arUser['GROUPS'])) {
            $this->arUser['IS_MODERATOR'] = true;
        }
        if (in_array($this->idGroupSuperModerator, $this->arUser['GROUPS'])) {
            $this->arUser['IS_SUPER_MODERATOR'] = true;
        }
    }

    /**
     * Может ли получить список черновиков
     * @return bool
     */
    public function isCanGetDraft()
    {
        if (empty($this->arUser)) return false;
        if ($this->arUser['IS_MODERATOR'] == true || $this->arUser['IS_SUPER_MODERATOR'] == true)
            $can = true;
        else
            $can = false;

        return $this->isCanGetDraft = $can;
    }

    /**
     * Может ли пользователь добавлять новые события
     * @return bool
     */
    public function isCanAdd()
    {
        if (empty($this->arUser)) return false;
        if ($this->arUser['IS_MODERATOR'] == true || $this->arUser['IS_SUPER_MODERATOR'] == true)
            $can = true;
        else
            $can = false;

        return $this->isCanAdd = $can;
    }

    /**
     * Может ли пользователь удалить событие
     * @return bool
     */
    public function isCanDel()
    {
        if (empty($this->arUser)) return false;
        if ($this->arUser['IS_MODERATOR'] == true || $this->arUser['IS_SUPER_MODERATOR'] == true)
            $can = true;
        else
            $can = false;

        return $this->isCanDel = $can;
    }

    /**
     * Может ли пользователь обновить событие
     * @return bool
     */
    public function isCanUpdate()
    {
        if (empty($this->arUser)) return false;
        if ($this->arUser['IS_MODERATOR'] == true || $this->arUser['IS_SUPER_MODERATOR'] == true)
            $can = true;
        else
            $can = false;

        return $this->isCanUpdate = $can;
    }

    /**
     * Может ли задать IMPORTANT для события
     * @return bool
     */
    public function isCanSetImportant()
    {
        if (empty($this->arUser)) return false;
        if ($this->arUser['IS_SUPER_MODERATOR'] == true)
            $can = true;
        else
            $can = false;

        return $this->isCanSetImportant = $can;
    }
}
?>