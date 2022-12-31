<?php
namespace Git\Module\Builder\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Builder\Abstracts\BuilderTrainingModule;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;

/**
 * Страница отчета
 * Class ReportBuilder
 * @package Git\Module\Builder\General
 */
class ReportBuilder extends BuilderTrainingModule
{
    private $arTrainers;
    private $arRgTrainings;
    private $obTrainings;
    private $obMembers;
    private $obMediatorRgTraining;
    private $obMediatorTraining;
    private $obMediatorUser;
    private $obExcel;

    /**
     * Вернет стартовую страницу формирования отчета
     * @return array
     */
    public function getReportPage()
    {
        $this->getMediators();
        return $this->buildReportPage();
    }

    /**
     * Вернет отфильтрованную страницу формирования отчета
     * @return array
     */
    public function getReportPageWithFilter()
    {
        if (empty($this->arFilter)) {
            $arResult['ERROR'][] = 'А фильтр где?';
            return $arResult;
        }

        $this->getMediators();
        $this->needMembers = false;

        $obTrainings = $this->getList();

        if (!empty($obTrainings)) {
            foreach ($obTrainings as $obTraining) {
                if (empty($obTraining->getId())) continue;

                $obTraining->getRgTrainingByTraining();
                $arTraining = $obTraining->getInfo();
                $obRgTraining = $arTraining['REGULAR_TRAINING'];
                $obRgTraining->getTrainer();

                $arRgTraining = $obRgTraining->getInfo();

                $arTrainings[] = $this->formatDataTrainingFromObject($obTraining, $arRgTraining);
            }
        }

        return $arTrainings;
    }

    /**
     * Вернет список всех участников тренировок по строке поиска
     * @param string $string
     * @return array
     */
    public function getAllUsers(string $string)
    {
        $this->getMediators();

        $this->getMembers($string);

        if (!empty($this->obMembers))
            foreach ($this->obMembers as $obMember)
                if ($obMember instanceof IForMediator)
                    $arResult['MEMBERS'][] = $obMember->getInfo();

        return $arResult;
    }

    /**
     * Сформирует excel файл по тренировкам
     * @param array $arTrainings
     * @return array
     * @throws \Bitrix\Main\ObjectException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createExcelFromList(array $arTrainings)
    {
        $this->obExcel = new ExcelBuilder();
        $arResult['REGULAR'] = [];
        $arResult['TRAINING'] = [];

        $cur_row = 3;
        $last_row = 3;
        $site = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . \COption::GetOptionString("main", "server_name");


        foreach ($arTrainings as $id) {
            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();

            $obTraining = $obMediator->buildClass($id);

            if (empty($obTraining->getId())) continue;

            $obTraining->getRgTrainingByTraining();
            $obTraining->getMediaInTraining();
            $obTraining->getMembers();

            $arFullTraining = $obTraining->getInfo();

            if (!empty($arFullTraining['REGULAR_TRAINING'])) {
                $obRgTraining = $arFullTraining['REGULAR_TRAINING'];
                $obRgTraining->getTrainer();

                $arRgTraining = $obRgTraining->getInfo();

                if (empty($arResult['REGULAR'][$obRgTraining->getId()])) {

                    if (!empty($arRgTraining['TRAINER'])) {
                        $obTrainer = $arRgTraining['TRAINER'][0];
                        $arRgTraining['TRAINER_DATA'] = $obTrainer->getInfo();
                    }

                    $arResult['REGULAR'][$arRgTraining['ID']] = $arRgTraining;
                }

                $arFormatTraining = $this->formatDataTrainingFromObject($obTraining, $arRgTraining);

                $arFormatTraining['REGULAR_TRAINING'] = $arRgTraining;

                $arResult['TRAINING'][] = $arFormatTraining;
            }

        }

        if (!empty($arResult['TRAINING'])) {

            foreach ($arResult['REGULAR'] as $arRgTraining) {

                if (!empty($arRgTraining['TRAINER_DATA'])) {
                    $this->obExcel->setCellValue("A".$cur_row, $arRgTraining['TRAINER_DATA']['NAME']);
                    $this->obExcel->setCellValue("B".$cur_row, $arRgTraining['TRAINER_DATA']['LAST_NAME']);
                }

                $this->obExcel->setCellValue("C".$cur_row, $arRgTraining['NAME']);

                foreach ($arResult['TRAINING'] as $arTraining) {
                    if ($arTraining['REGULAR_TRAINING']['ID'] == $arRgTraining['ID']) {
                        $this->obExcel->setCellValue("D".$cur_row, $arTraining['PROPERTIES']['COUNT']['VALUE']?:1);
                        $this->obExcel->setCellValue("E".$cur_row, $arTraining['PROPERTIES']['DATETIME']['VALUE']);
                        if (!empty($arTraining['MEDIA'][0]['PICTURE']))
                            $this->obExcel->setCellValue("F".$cur_row, $site. $arTraining['MEDIA'][0]['PICTURE']);

                        if (!empty($arTraining['MEMBERS'])) {
                            foreach ($arTraining['MEMBERS'] as $keyMember => $arMember) {

                                if (!empty($arMember['PERSONAL_BIRTHDAY']))
                                    $birthday = $arMember['PERSONAL_BIRTHDAY']->format('d.m.Y');

                                $this->obExcel->setCellValue("G".$cur_row, (int)$keyMember + 1);
                                if (!empty($arMember['LAST_NAME']))
                                    $this->obExcel->setCellValue("H".$cur_row, $arMember['LAST_NAME']);
                                if (!empty($arMember['NAME']))
                                    $this->obExcel->setCellValue("I".$cur_row, $arMember['NAME']);
                                if (!empty($arMember['SECOND_NAME']))
                                    $this->obExcel->setCellValue("J".$cur_row, $arMember['SECOND_NAME']);

                                if (!empty($arMember['WORK_POSITION']))
                                    $this->obExcel->setCellValue("K".$cur_row, $arMember['WORK_POSITION']);

                                if (!empty($arMember['UF_DEPARTMENT']))
                                    $this->obExcel->setCellValue("L".$cur_row, $arMember['UF_DEPARTMENT']);

                                if (!empty($arMember['EMAIL']))
                                    $this->obExcel->setCellValue("M".$cur_row, $arMember['EMAIL']);

                                if (!empty($arMember['PERSONAL_PHONE']))
                                    $this->obExcel->setCellValue("N".$cur_row, $arMember['PERSONAL_PHONE']);

                                if (!empty($birthday))
                                    $this->obExcel->setCellValue("O".$cur_row, $birthday);

                                if (!empty($arMember['PUBLIC_LINK']))
                                    $this->obExcel->setCellValue("P".$cur_row, (string)$arMember['PUBLIC_LINK']);
                                $cur_row++;
                            }
                        }

                    }
                }

                $cur_row++;
            }
        }

        $this->setTitleExcel();
        $this->obExcel->setAlignment($cur_row);

        global $USER;
        $date = date('d-m-Y-H-i');

        $path = '/upload/tmp/' . $USER->GetLogin() . '_' . $date . '.xlsx';
        $pathSave = $_SERVER['DOCUMENT_ROOT'] . $path;
        $pathSubmit = $this->getPathForExcel($path);
        $result = $this->obExcel->saveExcel($pathSave);

        if ($result == true)
            $return['RESULT'] = $pathSubmit;
        else
            $return['ERRORS'] = $result;

        return $return;
    }

    private function getPathForExcel($path)
    {
        $site = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $path = $site . $path;

        return $path;
    }

    /**
     * Установка заголовков файла
     */
    private function setTitleExcel()
    {
        for ($i = 1; $i < 17; $i++) {
            $coord = $this->getLetter($i, 1, true);
            $letter = $this->getLetter($i, 1);

            $this->obExcel->setCellValue($coord, GetMessage('TITLE_'.$i));
            $this->obExcel->setFillType($coord, 'bold');
            $this->obExcel->setAutoSize($letter); // В конце
        }
    }

    /**
     * Получить букву по номеру
     * @param $num_letter
     * @param $num_collum
     * @return string
     */
    private function getLetter($num_letter, $num_collum, bool $withNum = false)
    {
        switch ($num_letter) {
            case 1:
                $return = "A";
                break;
            case 2:
                $return = "B";
                break;
            case 3:
                $return = "C";
                break;
            case 4:
                $return = "D";
                break;
            case 5:
                $return = "E";
                break;
            case 6:
                $return = "F";
                break;
            case 7:
                $return = "G";
                break;
            case 8:
                $return = "H";
                break;
            case 9:
                $return = "I";
                break;
            case 10:
                $return = "J";
                break;
            case 11:
                $return = "K";
                break;
            case 12:
                $return = "L";
                break;
            case 13:
                $return = "M";
                break;
            case 14:
                $return = "N";
                break;
            case 15:
                $return = "O";
                break;
            case 16:
                $return = "P";
                break;
            case 17:
                $return = "Q";
                break;
            case 18:
                $return = "R";
                break;
        }
        if ($withNum)
            $return .= $num_collum;

        return $return;
    }

    private function buildReportPage()
    {
        $arResult['TRAINERS'] = $this->getTrainers();
        $arResult['TRAININGS'] = $this->getTrainings();

        return $arResult;
    }

    /**
     * Получить список всех тренеров
     * @return mixed
     */
    private function getTrainers()
    {
        return $this->arTrainers = $this->obMediatorUser->getAllTrainers();
    }

    private function getTrainings()
    {
        $arRgTrainings = $this->obMediatorRgTraining->getAllD();
        if (!empty($arRgTrainings))
            foreach ($arRgTrainings as $arRgTraining)
                $this->arRgTrainings[] = $this->fastFormatRgTraining($arRgTraining);

        return $this->arRgTrainings;
    }

    /**
     * Получить список всех участников тренировок по строке поиска
     * @param string $string
     * @return mixed
     */
    private function getMembers(string $string)
    {
        return $this->obMembers = $this->obMediatorUser->getAllMembersByString($string);
    }

    /**
     * Получение тренировок по фильтру
     * @return mixed
     */
    private function getList()
    {
        if (!empty($this->arFilter['TRAININGS']) || !empty($this->arFilter['TRAINERS']))
            $arId = $this->getRgIdByFilter();
        else {
            $arRgs = $this->obMediatorRgTraining->getAllD();
            foreach ($arRgs as $arRg) $arId[] = $arRg['ID'];
        }

        if (!empty($arId)) {
            $arIdTrainingsByRegular = $this->obMediatorTraining->getTrainingsByIdRegularInTable($arId);
            $arId = $this->arFilter['TRAININGS_ID'] = $arIdTrainingsByRegular;
        }

        if (!empty($arIdTrainingsByRegular) && ( !empty($this->arFilter['DATE_FROM']) || !empty($this->arFilter['DATE_TO'])) ) {
            $arId = $this->getTrainingsIdByFilter();
        }

        if (!empty($arId) && !empty($this->arFilter['MEMBERS'])) {
            $obTrainings = $this->obMediatorTraining->getTrainingsByIdAndIdUsers($arId, $this->arFilter['MEMBERS']);
        } elseif (!empty($arId)) {
            foreach ($arId as $id) {
                $obTrainings[] = $this->obMediatorTraining->buildClass($id);
            }
        }

        return $obTrainings;
    }

    /**
     * Получить ID регулярных тренировок по их ID либо тренеру
     * @return array
     */
    private function getRgIdByFilter()
    {
        $obEmptyRgTraining = $this->obMediatorRgTraining->getRgTrainingObj();
        $iblockRgTraining = $obEmptyRgTraining::getIblockId();

        $tmpFilter = [
            'ID' => $this->arFilter['TRAININGS'],
            'PROPERTY_TRAINER' => $this->arFilter['TRAINERS'],
            'IBLOCK_ID' => $iblockRgTraining
        ];
        $obEls = \CIBlockElement::GetList(
            [],
            $tmpFilter,
            false,
            ['nTopCount' => '99'],
            []
        );

        while ($arEl = $obEls->GetNext()) {
            $arId[] = $arEl['ID'];
        }

        return $arId;
    }

    /**
     * Фильтр тренировок по свойствам инфоблока
     * @return array
     */
    private function getTrainingsIdByFilter()
    {
        $obEmptyTraining = $this->obMediatorTraining->getTrainingObj();
        $iblockTraining = $obEmptyTraining::getIblockId();
        $from = ConvertDateTime($this->arFilter['DATE_FROM'], 'YYYY-MM-DD')." 00:00:00";
        $to = ConvertDateTime($this->arFilter['DATE_TO'], 'YYYY-MM-DD')." 00:00:00";

        $tmpFilter = [
            'ID' => $this->arFilter['TRAININGS_ID'],
            'IBLOCK_ID' => $iblockTraining,
            'PROPERTY_OPEN' => 'N'
        ];

        if (!empty($this->arFilter['DATE_FROM']) && !empty($this->arFilter['DATE_TO'])) {
            $dop = [
                'LOGIC' => 'AND',
                ['>=PROPERTY_DATETIME' => $from],
                ['<=PROPERTY_DATETIME' => $to]
            ];
        } elseif (!empty($this->arFilter['DATE_FROM'])) {
            $tmpFilter['>=PROPERTY_DATETIME'] = $from;
        } elseif (!empty($this->arFilter['DATE_TO'])) {
            $tmpFilter['<=PROPERTY_DATETIME'] = $to;
        }

        if (!empty($dop)) {
            $tmpFilter[] = $dop;
        }

        $obEls = \CIBlockElement::GetList(
            ['PROPERTY_DATETIME' => 'ASC'],
            $tmpFilter,
            false,
            ['nTopCount' => '99'],
            []
        );

        while ($arEl = $obEls->GetNext()) {
            $arId[] = $arEl['ID'];
        }

        return $arId;
    }

    /**
     * Получение всех посредников которые понадобятся
     */
    private function getMediators()
    {
        $obMediator = new FactoryMediator('RegularTraining');
        $this->obMediatorRgTraining = $obMediator->getClass();
        $obMediator = new FactoryMediator('Training');
        $this->obMediatorTraining = $obMediator->getClass();
        $obMediator = new FactoryMediator('User');
        $this->obMediatorUser = $obMediator->getClass();
    }

    /**
     * Установка фильтра для получения списка
     * @param array $arFilter
     */
    public function setFilter(array $arFilter)
    {
        if (!empty($arFilter))
            $this->arFilter = $arFilter;
    }

    /**
     * Небольшой формат данных для фильтра
     * @param array $arInfo
     * @return array
     */
    protected function fastFormatRgTraining(array $arInfo)
    {
        if (!empty($arInfo['PROPERTIES']['DAY']['VALUE'])) {
            $arInfo['DAY_SHORT'] = Utils::getShortLangNameDayByNum($arInfo['PROPERTIES']['DAY']['VALUE']);
        }
        if (!empty($arInfo['PROPERTIES']['DATA_FROM']['VALUE']) && !empty($arInfo['PROPERTIES']['DATA_TO']['VALUE'])) {
            $arInfo['DATE_FULL'] = $this->formatDurationTraining($arInfo["PROPERTIES"]['DATA_FROM']['VALUE'], $arInfo["PROPERTIES"]['DATA_TO']['VALUE']);
        }

        return $arInfo;
    }
}
?>