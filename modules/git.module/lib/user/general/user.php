<?php
namespace Git\Module\User\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Git\Module\Helpers\Utils;
use Git\Module\User\Abstracts\User as AUser;
use Git\Module\Mediator\Interfaces\IMember;
use Git\Module\User\Mediator\MediatorForUser;

/**
 * Класс для работы с пользователями которые записаны в таблицу "Связи модулей"
 * Class User
 * @package Git\Module\User\General
 */
class User extends AUser
{
    /**
     * Получить список всех участников тренировок по строке поиска
     * @param string $string
     * @return mixed
     */
    public function getAllByString(string $string)
    {
        $arFieldsAdditional = [
            'UF_DEPARTMENT',
            'WORK_POSITION',
            'PERSONAL_BIRTHDAY'
        ];
        $arSelect = array_merge($this->defaultFields, $arFieldsAdditional);
        $arFilter = [
            [
                'LOGIC' => 'OR',
                ['NAME' => '%' . $string . '%'],
                ['LAST_NAME' => '%' . $string . '%'],
                ['SECOND_NAME' => '%' . $string . '%'],
                ['UF_NICKNAME' => '%' . $string . '%'],
            ],
            'ACTIVE' => 'Y'
        ];

        global $USER;
        $user_id = $USER->GetID();
        if (!empty($user_id))
            $arFilter['!ID'] = $user_id;

        $arData = [
            'order' => ['NAME' => 'ASC'],
            'filter' => $arFilter,
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
        $arFieldsAdditional = [
            'UF_DEPARTMENT',
            'WORK_POSITION',
            'PERSONAL_BIRTHDAY'
        ];
        $arSelect = array_merge($this->defaultFields, $arFieldsAdditional);

        $arData = [
            'filter' => [
                '=ID' => $this->id,
                'ACTIVE' => 'Y'
            ],
            'select' => $arSelect
        ];
        $arUser = \Bitrix\Main\UserTable::getList($arData)->fetch();

        if (empty($arUser)) {

            // Если пользователь не найден то попробовать найти его в инфоблоке для неавторизованных пользователей
            $arUser = $this->getNoAuthUser();

            if (empty($arUser))
                $this->id = '';
        }
        else {
            $arUser['PUBLIC_LINK'] = \GModuleUser::getPublicUserLink($arUser['ID']);
        }

        $arUser['STATUS'] = $this->status;

        return $arUser;
    }

    /**
     * Надстройка позволяющая получать пользователей которых нет на сайте. Добавлены только для одной конкретной тренировки
     * @return array
     * @throws \Bitrix\Main\ObjectException
     */
    protected function getNoAuthUser()
    {
        $arUser = [];

        $arSelect = $this->defaultFields;

        foreach ($arSelect as $key => $arSel) {
            if ($arSel == 'ID') continue;

            $arSelect[$key] = 'PROPERTY_'.$arSel;
        }
        $arSelect[] = 'PREVIEW_PICTURE';

        $rsUser = \CIBlockElement::GetList(
            [],
            ['ACTIVE' => 'Y', 'ID' => $this->id, 'IBLOCK_ID' => self::getIblockId()],
            false,
            false,
            $arSelect
        );

        while ($arUserTmp = $rsUser->GetNext()) {
            foreach ($arUserTmp as $keyField => $arField) {
                if ($keyField == 'ID')
                    $arUser['ID'] = $arField;
                elseif ($keyField == 'PREVIEW_PICTURE')
                    $arUser['PERSONAL_PHOTO'] = $arField;
                elseif ($keyField == 'PROPERTY_PERSONAL_BIRTHDAY_VALUE')
                    $arUser['PERSONAL_BIRTHDAY'] = new Date($arField);
                else {
                    foreach ($this->defaultFields as $field)
                        if ($keyField == 'PROPERTY_'.$field.'_VALUE')
                            $arUser[$field] = $arField;
                }
            }
        }

        return $arUser;
    }

    /**
     * Добавить пользователя который не зарегистрирован
     * @param $arFields
     */
    public function addUnregisterUser($arFields)
    {
        foreach ($arFields as $key => $arField) {
            if ($key == 'EMAIL')
                $arNewField['NAME'] = $arField;

            $arNewField['PROPERTY_VALUES'][$key] = $arField;
        }
        $arNewField['IBLOCK_ID'] = self::getIblockId();
        $obEl = new \CIBlockElement();

        if ($id = $obEl->Add($arNewField))
            $this->idNew = $id;
        else
            $this->addError($obEl->LAST_ERROR);
    }

    protected function setTypeUser()
    {
        $this->info['TYPE'] = 'user';
    }

    /**
     * Получение инфоблока для пользователей которые не авторизованы (кастом)
     * @return false|mixed
     */
    public static function getIblockId()
    {
        $iblock_id = Utils::getIdByCode('noauthuser');
        return $iblock_id;
    }

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