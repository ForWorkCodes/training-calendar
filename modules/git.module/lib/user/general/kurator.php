<?php
namespace Git\Module\User\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Mediator\Interfaces\IMember;
use Git\Module\User\Abstracts\User;
use Git\Module\User\Mediator\MediatorForUser;

class Kurator extends User
{
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
        $this->info['TYPE'] = 'kurator';
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

    /**
     * Получить куратора
     * @return mixed
     */
    public function getMainKurator()
    {
        $arSelect = $this->defaultFields;
        $arData = [
            'filter' => [
                'ACTIVE' => 'Y',
                "Bitrix\Main\UserGroupTable:USER.GROUP_ID" => KURATOR_GROUP
            ],
            'select' => $arSelect
        ];
        $arUser = \Bitrix\Main\UserTable::getList($arData)->fetch();

        if (!empty($arUser))
            $arUser['PUBLIC_LINK'] = \GModuleUser::getPublicUserLink($arUser['ID']);

        return $arUser;
    }
}
?>