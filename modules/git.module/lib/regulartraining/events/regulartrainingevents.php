<?php
namespace Git\Module\RegularTraining\Events;

use Bitrix\Main\Diag\Debug;
use Git\Module\Factory\General\FactoryMediator;

class RegularTrainingEvents
{
    /**
     * Проверка какой элемент обновился, если регулярная тренировка то ок
     * @param $arFields
     */
    public static function beforeElementUpdate($arFields)
    {
        $rsMediator = new FactoryMediator('RegularTraining');
        $obMediator = $rsMediator->getClass();

        $obEmptyRg = $obMediator->getRgTrainingObj();
        $iblock = $obEmptyRg::getIblockId();

        if ((int)$iblock == (int)$arFields['IBLOCK_ID']) {
            $obRgTraining = $obMediator->buildClass($arFields['ID']);
            $arRgTraining = $obRgTraining->getInfo();

            $id_prop = $arRgTraining['PROPERTIES']['TRAINER']['ID'];
            $id_val_prop = $arRgTraining['PROPERTIES']['TRAINER']['PROPERTY_VALUE_ID'];

            if ((int)$arRgTraining['PROPERTIES']['TRAINER']['VALUE'] != $arFields['PROPERTY_VALUES'][$id_prop][$id_val_prop]['VALUE'])
                $arResult = $obMediator->updateTrainerById($obRgTraining, $arFields['PROPERTY_VALUES'][$id_prop][$id_val_prop]['VALUE']);
        }

        return $arResult;
    }

    /**
     * Установка тренера для рг тренировки
     * @param $arFields
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function afterElementAdd($arFields)
    {
        $rsMediator = new FactoryMediator('RegularTraining');
        $obMediator = $rsMediator->getClass();

        $obEmptyRg = $obMediator->getRgTrainingObj();
        $iblock = $obEmptyRg::getIblockId();

        if ((int)$iblock == (int)$arFields['IBLOCK_ID']) {
            $obRgTraining = $obMediator->buildClass($arFields['ID']);
            $arRgTraining = $obRgTraining->getInfo();

            $obMediator->setTrainer($obRgTraining, $arRgTraining['PROPERTIES']['TRAINER']['VALUE']);
        }
    }
}
?>