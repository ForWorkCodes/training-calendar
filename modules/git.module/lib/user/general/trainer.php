<?php
namespace Git\Module\User\General;

use Git\Module\Mediator\Interfaces\IMember;
use Git\Module\User\Abstracts\User;
use Git\Module\User\Mediator\MediatorForUser;

class Trainer extends User
{
    public function getAll()
    {
        $arSelect = $this->defaultFields;
        $arData = [
            'filter' => [
                'ACTIVE' => 'Y',
                "Bitrix\Main\UserGroupTable:USER.GROUP_ID" => TRAINER_GROUP
            ],
            'select' => $arSelect
        ];
        $arUsers = \Bitrix\Main\UserTable::getList($arData)->fetchAll();

        if (!empty($arUsers))
            foreach ($arUsers as &$item)
                $item['PUBLIC_LINK'] = \GModuleUser::getPublicUserLink($item['ID']);

        return $arUsers;
    }

    protected function getDetailInfo()
    {
        $arSelect = $this->defaultFields;
        $arData = [
            'filter' => [
                '=ID' => $this->id,
                'ACTIVE' => 'Y'
            ],
            'select' => $arSelect
        ];
        $arUser = \Bitrix\Main\UserTable::getList($arData)->fetch();

        if (empty($arUser))
            $this->id = '';

        $arUser['PUBLIC_LINK'] = \GModuleUser::getPublicUserLink($arUser['ID']);

        return $arUser;
    }

    protected function setTypeUser()
    {
        $this->info['TYPE'] = 'trainer';
    }

    /**
     * Только на 4 модуле подумал, а зачем setMediator выносить в объект?
     * пусть остается в абстракции, ведь у одного модуля будет только один посредник.
     * Ну ладно, пока буду придерживаться стратегии))
     * @return mixed|void
     * @throws \Bitrix\Main\LoaderException
     */
    protected function setMediator()
    {
        $mediator = new MediatorForUser();
        if ($mediator instanceof IMember)
            $this->mediator = $mediator;
        else
            throw new \Bitrix\Main\LoaderException('Call invalid mediator');
    }
}
?>