<?php
namespace Git\Module\Builder\Abstracts;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Training\Interfaces\ITraining;

abstract class BuilderTrainingModule
{
    protected $arFilter;
    protected $arUser;
    protected $obUser;
    protected $needBtnType = false;
    protected $needMedia;
    protected $needMembers = true;

    /**
     * Формат массива медиа для вывода на фронт
     * @param IForMediator $obMedia
     * @return array
     */
    public function formatDataMedia(IForMediator $obMedia)
    {
        $arMedia = $obMedia->getInfo();
        
        $arResult = [
            'ID' => $arMedia['ID'],
            'NAME' => $arMedia['NAME'],
            'PREVIEW_TEXT' => $arMedia['PREVIEW_TEXT'],
            'PREVIEW' => (!empty($arMedia['PREVIEW_PICTURE'])) ? \CFile::GetPath($arMedia['PREVIEW_PICTURE']) : VIDEO_DEFAULT_PIC,
            'PICTURE' => (!empty($arMedia['PROPERTIES']['FILE']['VALUE'])) ? \CFile::GetPath($arMedia['PROPERTIES']['FILE']['VALUE']) : VIDEO_DEFAULT_PIC,
            'DATE' => $arMedia['CREATED_DATE']
        ];

        if ($arMedia['PROPERTIES']['IS_VIDEO']['VALUE'] == 'Y')
            $arResult['PREVIEW'] = (!empty($arMedia['PREVIEW_PICTURE'])) ? \CFile::GetPath($arMedia['PREVIEW_PICTURE']) : VIDEO_DEFAULT_PIC;
        else
            $arResult['PREVIEW'] = $arResult['PICTURE'];

        if (!empty($arMedia['BEARS'])) {
            $arResult['BEARS'] = $arMedia['BEARS'];
        }

        return $arResult;
    }

    /**
     * Получение данных активного пользователя
     */
    protected function getMyUserData()
    {
        global $USER;
        $id = $USER->GetID();

        if (!empty($id)) {
            $rsMediator = new FactoryMediator('User');
            $obMediator = $rsMediator->getClass();

            $obUser = $this->obUser = $obMediator->buildClass($id);
            $arUser = $obUser->getInfo();
            $this->arUser = $this->formatDataUser($arUser);
        }
    }

    /**
     * Формирование массива типа пользователь
     * @param array $arUser
     * @return array
     */
    protected function formatDataUser(array $arUser)
    {
        $uAge = getYears($arUser["PERSONAL_BIRTHDAY"]);
        $propIblock = \Git\Module\Helpers\Utils::getIblockFromUfProp('UF_DEPARTMENT');
        $arElsProp = \Git\Module\Helpers\Utils::getElemIblock($propIblock);
        $departament = $arElsProp[$arUser['UF_DEPARTMENT']]['NAME'];

        $arResult = [
            'ID' => $arUser['ID'],
            'NAME' => $arUser['NAME'],
            'LAST_NAME' => $arUser['LAST_NAME'],
            'SECOND_NAME' => $arUser['SECOND_NAME'],
            'FORMAT_NAME' => $arUser['LAST_NAME'] . ' ' . $arUser['NAME'],
            'AGE' => $uAge,
            'EMAIL' => $arUser['EMAIL'],
            'PERSONAL_PHOTO' => (!empty($arUser['PERSONAL_PHOTO'])) ? \CFile::ResizeImageGet($arUser['PERSONAL_PHOTO'], ['width' => 200, 'height' => 200])['src'] : USER_DEFAULT_PIC,
            'PERSONAL_PHONE' => $arUser['PERSONAL_PHONE'],
            'UF_DEPARTMENT' => $departament,
            'WORK_POSITION' => $arUser['WORK_POSITION'],
            'WORK_DEPARTMENT' => $arUser['WORK_DEPARTMENT'],
            'PERSONAL_GENDER' => $arUser['PERSONAL_GENDER'],
            'PERSONAL_BIRTHDAY' => $arUser['PERSONAL_BIRTHDAY'],
            'STATUS' => ($arUser['STATUS'] == 'Y') ? 'COMPLETE' : 'MISSED',
            'TYPE' => $arUser['TYPE'],
            'PUBLIC_LINK' => $arUser['PUBLIC_LINK']
        ];

        return $arResult;
    }

    /**
     * Прохожу каждую тренировку и получаю именно те данные которые мне нужны
     * @param ITraining $obTraining
     * @param array $arRgTraining
     * @throws \Bitrix\Main\ObjectException
     */
    protected function formatDataTrainingFromObject(ITraining $obTraining, array $arRgTraining)
    {
        $arTrainer = [];
        $arMembers = [];

        if ($this->needMembers)
            $obTraining->getMembers();
        if ($this->needBtnType)
            $obTraining->whatCanUser();
        if ($this->needMedia)
            $obTraining->getMediaInTraining();

        $arInfoTraining = $obTraining->getInfo();

        if (!empty($arInfoTraining['MEMBERS']))
        {
            foreach ($arInfoTraining['MEMBERS'] as $obMember)
            {
                if (!($obMember instanceof IForMediator)) continue;
                $tmpMember = $obMember->getInfo();
                $tmpMember['STATUS'] = $obMember->getStatus();
                $arMembers[] = $this->formatDataUser($tmpMember);
            }
            if (!empty($arMembers))
                $arTraining['MEMBERS'] = $arMembers;
        }

        if (!empty($arRgTraining['TRAINER']))
        {
            foreach ($arRgTraining['TRAINER'] as $obTrainer)
            {
                if (!($obTrainer instanceof IForMediator)) continue;
                $tmpTrainer = $obTrainer->getInfo();
                $arTrainer[] = $this->formatDataUser($tmpTrainer);
            }
            if (!empty($arTrainer))
                $arTraining['TRAINER'] = $arTrainer;
        }

        if (!empty($arInfoTraining['MEDIA']))
        {
            foreach ($arInfoTraining['MEDIA'] as $obMedia)
            {
                if (!($obMedia instanceof IForMediator)) continue;
                $arMedia[] = $this->formatDataMedia($obMedia);
            }
            if (!empty($arMedia))
                $arTraining['MEDIA'] = $arMedia;
        }
        $arTraining['PROPERTIES'] = $arInfoTraining['PROPERTIES'];
        $arTraining['ID_ALBUM'] = $arRgTraining['ID'];

        $arTraining = array_merge($arTraining, $this->pickUpMainDataTraining($arInfoTraining, $arRgTraining));

        return $arTraining;
    }

    /**
     * Формат даты для фронта
     * @param string $date
     * @return string
     * @throws \Bitrix\Main\ObjectException
     */
    protected function formatDateStartTraining(string $date)
    {
        $dateN = new DateTime($date);

        $return = FormatDate('d F', $dateN->getTimestamp());

        return $return;
    }

    protected function formatDurationTraining(string $dateFrom, string $dateTo)
    {
        $dateF = new DateTime($dateFrom);
        $dateT = new DateTime($dateTo);

        $return = FormatDate('m.Y', $dateF->getTimestamp()) . '-' .FormatDate('m.Y', $dateT->getTimestamp());

        return $return;
    }

    /**
     * Формат времени для фронта
     * @param string $date
     * @param string $duration
     * @return string
     * @throws \Bitrix\Main\ObjectException
     */
    protected function formatTimeTraining(string $date, string $duration)
    {
        $dateN = new DateTime($date);
        $return = $dateN->format('H:i') . ' - ' . $dateN->add($duration.' minutes')->format('H:i');

        return $return;
    }

    /**
     * Формирование основных данных тренировки
     * @param array $arTraining
     * @param array $arRgTraining
     * @return array
     */
    protected function pickUpMainDataTraining(array $arTraining, array $arRgTraining)
    {
        $pic = (!empty($arRgTraining['PREVIEW_PICTURE'])) ? \CFile::ResizeImageGet($arRgTraining['PREVIEW_PICTURE'], ['width' => 200, 'height' => 200])['src'] : 'default.jpg';
        $picD = (!empty($arRgTraining['DETAIL_PICTURE'])) ? \CFile::ResizeImageGet($arRgTraining['DETAIL_PICTURE'], ['width' => 5000, 'height' => 5000])['src'] : 'default.jpg';

        if ($arTraining['PROPERTIES']['SUBMIT']['VALUE'] == 'Y'
            && $arTraining['PROPERTIES']['FINISH']['VALUE'] != 'Y'
            && $arTraining['PROPERTIES']['OPEN']['VALUE'] == 'N')
            $btnEditTo = true;

        $arResult = [
            'DATE' => $this->formatDateStartTraining($arTraining["PROPERTIES"]['DATETIME']['VALUE']),
            'TIME' => $this->formatTimeTraining($arTraining["PROPERTIES"]['DATETIME']['VALUE'], $arRgTraining["PROPERTIES"]['DURATION']['VALUE']),
            'ID' => $arTraining['ID'],
            'NAME' => $arTraining['NAME'],
            'TRAINING_IS_END' => ($arTraining['PROPERTIES']['OPEN']['VALUE'] != 'Y') ? true : false, // Открыта ли запись
            'REPORT_IS_SUBMIT' => ($arTraining['PROPERTIES']['SUBMIT']['VALUE'] == 'Y') ? true : false, // Отправлен ли отчет
            'TRAINING_IS_FINISH' => ($arTraining['PROPERTIES']['FINISH']['VALUE'] == 'Y') ? true : false,
            'SHOW_TIME_EDIT_BTN' => $btnEditTo, // Показывать ли кнопку "Редактировать до"
            'DESCRIPTION' => $arTraining['PROPERTIES']['DESCRIPTION']['~VALUE']['TEXT'],
            'PREVIEW_PICTURE' => $pic,
            'DETAIL_PICTURE' => $picD,
            'NUMBER_OF_PLACES' => [
                'MAX' => $arRgTraining['PROPERTIES']['NUMBER_OF_PLACES']['VALUE']
            ],
            'PERIOD' => $this->getPeriodDate($arRgTraining['PROPERTIES']['DATA_FROM']['VALUE'], $arRgTraining['PROPERTIES']['DATA_TO']['VALUE']),
            'DAY' => Utils::getLangNameDay($arTraining['PROPERTIES']['DATETIME']['VALUE']),
            'LOCATION' => $arRgTraining['PROPERTIES']['LOCATION']['~VALUE'],
            'LOCATION_DATA' => $arRgTraining['PROPERTIES']['LOCATION_DATA']['~VALUE']['TEXT'],
            'I_TRAINER' => ($arRgTraining['TRAINER'][0]->getId() == $this->arUser['ID'] || $this->arUser['TYPE'] == 'kurator') ? true : false,
            'WHAT_CAN' => $arTraining['WHAT_CAN'] // Код вывода кнопки для пользователя,
        ];

        if ($arResult['I_TRAINER']) {
            $arResult['WEIGHT'] = 900;
        } else {

            if ($arResult['WHAT_CAN'] == 'ALREADY') {
                $arResult['WEIGHT'] = 100;
            } elseif ($arResult['WHAT_CAN'] == 'READY') {
                $arResult['WEIGHT'] = 200;
            } elseif ($arResult['TRAINING_IS_END']) {
                $arResult['WEIGHT'] = 300;
            } else {
                $arResult['WEIGHT'] = 800;
            }

        }

        if (count($arTraining['MEMBERS']) > 0)
            $arResult['NUMBER_OF_PLACES']['REAL'] = count($arTraining['MEMBERS']);

        if ( (int)$arResult['NUMBER_OF_PLACES']['REAL'] >= (int)$arResult['NUMBER_OF_PLACES']['MAX'] )
            $arResult['IS_FULL'] = 'Y';

        return $arResult;
    }

    protected function getPeriodDate($from, $to)
    {
        if (empty($from) || empty($to)) return;

        $dateFrom = new DateTime($from);
        $dateTo = new DateTime($to);

        $return = FormatDate('f ‘d', $dateFrom->getTimestamp()) . ' - ' . FormatDate('f ‘d', $dateTo->getTimestamp());

        return $return;
    }

    /**
     * Сортировка тренировок
     * @param $arTrainings
     * @return mixed
     */
    protected function sortTrainingsList($arTrainings)
    {
        usort($arTrainings, array($this, "customTrainingSort"));

        return $arTrainings;
    }

    /**
     * Кастомная сортировка
     */
    protected function customTrainingSort($a, $b)
    {
        if ($a['WEIGHT'] == $b['WEIGHT']) {
            $result = (strtotime($a["PROPERTIES"]['DATETIME']['VALUE']) < strtotime($b["PROPERTIES"]['DATETIME']['VALUE'])) ? -1 : 1;
        } else {
            $result = ($a['WEIGHT'] < $b['WEIGHT']) ? -1 : 1;
        }

        return $result;
    }
}
?>