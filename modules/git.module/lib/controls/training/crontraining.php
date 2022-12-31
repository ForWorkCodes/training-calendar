<?php

namespace Git\Module\Controls\Training;

use Git\Module\Controllers\Training;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Modules\Events\mediator\MediatorForEvents;
use Git\Module\RegularTraining\Mediator\MediatorForRegularTraining;
use Git\Module\Training\Events\TrainingEvents;
use Git\Module\Training\Mediator\MediatorForTraining;

IncludeModuleLangFile(__FILE__);

/**
 * Class CronTraining Для крона
 */
class CronTraining
{
    /**
     * Закрытие всех тренеровок недели
     */
    public static function closeTraining()
    {
        $obMediatorTraining = new MediatorForTraining();
        $obMediatorTraining->closeActiveTrainingsInModuleTable();

    }

    /**
     * Создать новые тренировки по шаблону (регулярных тренировок)
     */
    public static function creatTraining2Template()
    {
        $obMediatorRegularTraining = new MediatorForRegularTraining();
        $obMediatorTraining = new MediatorForTraining();

        $arTrainingTemplates = $obMediatorRegularTraining->getAll();

        foreach ($arTrainingTemplates as $arTrainingTemplate) {
            $newElementId = $obMediatorTraining->createTraining($arTrainingTemplate);
            if($newElementId){
                self::addEvent($newElementId);
                $obMediator = new FactoryMediator('RegularTraining');
                $obRgMediator = $obMediator->getClass();
                $obRgMediator->addTrainingInRegularById((int)$arTrainingTemplate['ID'], [$newElementId]);
                TrainingEvents::processMissedReport(['ID_TRAINING' => $newElementId]);
            }
        }
    }

    public static function serviceCreatTraining()
    {
        self::closeTraining();
        self::creatTraining2Template();

    }

    /**
     * @param int $newElementId
     */
    private static function addEvent(int $newElementId)
    {
        $obMediatorTraining = new MediatorForTraining();
        $obTmp = new MediatorForEvents();

        $obTraining = $obMediatorTraining->getTrainingObj($newElementId);
        $obTraining->getRgTrainingByTraining();

        if(!empty($obTraining)){
            $arInfoTraining = $obTraining->getInfo();

            if (!empty($arInfoTraining['REGULAR_TRAINING']) && ($arInfoTraining['REGULAR_TRAINING'] instanceof IForMediator)) {
                $arRgInfo = $arInfoTraining['REGULAR_TRAINING']->getInfo();
                $place = $arRgInfo['PROPERTIES']['LOCATION']['VALUE'];
            }

            $arParamEvent = [
                'UF_XML_ID'=>MediatorForEvents::EVENT_MESSAGE_72H_CODE,
                'UF_FUNCTION'=>'Git\Module\Modules\Events\General\Router::messageTraining',
                'UF_ID_PARENT'=>$newElementId,
                'UF_DATE_START'=> Utils::getDateCustom($arInfoTraining['PROPERTIES']['DATETIME']['VALUE'],"-72 hour"),
            ];

            $arParamEvent['UF_PARAMS'] = [
                'ID_TRAINIGN'=>$newElementId,

                'UF_DATE_START'=> Utils::getDateCustom($arInfoTraining['PROPERTIES']['DATETIME']['VALUE'],"-72 hour"),
                'CODE'=>MediatorForEvents::EVENT_MESSAGE_72H_CODE,
                'REPLACE_RULES'=>[
                    '#NAME_TRAINING#'=> $arInfoTraining['NAME'],
                    '#DATE#'=>$arInfoTraining['PROPERTIES']['DATETIME']['VALUE'],
                    '#POINT#' => $place
                ]
            ];

            $obTmp->add($arParamEvent);

            $arParamEvent['UF_XML_ID'] = $arParamEvent['UF_PARAMS']['CODE'] = MediatorForEvents::EVENT_MESSAGE_21H_CODE;
            $arParamEvent['UF_DATE_START'] = Utils::getDateCustom($arInfoTraining['PROPERTIES']['DATETIME']['VALUE'],"-24 hour",'d.m.Y 21:00');
            $obTmp->add($arParamEvent);

            $arParamEvent['UF_XML_ID'] = $arParamEvent['UF_PARAMS']['CODE'] = MediatorForEvents::EVENT_MESSAGE_3H_CODE;
            $arParamEvent['UF_DATE_START'] = Utils::getDateCustom($arInfoTraining['PROPERTIES']['DATETIME']['VALUE'],"-3 hour");
            $obTmp->add($arParamEvent);
        }
    }
}