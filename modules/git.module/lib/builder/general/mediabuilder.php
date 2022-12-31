<?php
namespace Git\Module\Builder\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Builder\Abstracts\BuilderTrainingModule;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;

/**
 * Страницы медиа
 * Class MediaBuilder
 * @package Git\Module\Builder\General
 */
class MediaBuilder extends BuilderTrainingModule
{
    private $id_media;
    private $mainMedia;
    private $obRgTraining;
    private $need_other_media;
    private $need_training;

    /**
     * Возвращаем детальную страницу медиа
     * @param $id_media
     * @return array
     */
    public function getMediaPage($id_media)
    {
        $this->id_media = $id_media;
        return $this->buildMediaPage();
    }

    /**
     * Получение списка медиа по массиву ID
     * @return array
     */
    public function getAlbumPage($id_regular_training)
    {
        $this->getRgTraining($id_regular_training);

        if (empty($this->obRgTraining->getId())) return;

        $this->obRgTraining->getAllMedia();
        $arRgTraining = $this->formatRgTraining();

        return $arRgTraining;
    }

    /**
     * Работа с регулярной тренировкой
     * @return array|void
     * @throws \Bitrix\Main\ObjectException
     */
    private function formatRgTraining()
    {
        if (empty($this->obRgTraining->getId())) return;

        $this->obRgTraining->getTrainer();
        $arRgTrainingTmp = $this->obRgTraining->getInfo();

        if (!empty($arRgTrainingTmp['MEDIA'])) {
            $bears = 0;
            $arRgTraining['MEDIA'] = $this->buildAlbum($arRgTrainingTmp['MEDIA']);
            foreach ($arRgTraining['MEDIA'] as $arTmpMedia)
                $bears += $arTmpMedia['BEARS'];
        }

        if (!empty($arRgTrainingTmp['TRAINER']))
        {
            foreach ($arRgTrainingTmp['TRAINER'] as $obTrainer)
            {
                if (!($obTrainer instanceof IForMediator)) continue;
                $tmpTrainer = $obTrainer->getInfo();
                $arTrainer[] = $this->formatDataUser($tmpTrainer);
            }
            if (!empty($arTrainer))
                $arRgTraining['TRAINER'] = $arTrainer;
        }

        $arRgTraining['INFO'] = $this->pickUpMainDataRgTraining($arRgTrainingTmp);
        $arRgTraining['INFO']['BEARS'] = $bears;
        $arRgTraining['INFO']['MOVIES'] = count($arRgTraining['MEDIA']);

        return $arRgTraining;
    }

    /**
     * Формат основных данный регулярной тренировки
     * @param array $arRgTraining
     * @return array
     * @throws \Bitrix\Main\ObjectException
     */
    private function pickUpMainDataRgTraining(array $arRgTraining)
    {
        $pic = (!empty($arRgTraining['PREVIEW_PICTURE'])) ? \CFile::ResizeImageGet($arRgTraining['PREVIEW_PICTURE'], ['width' => 200, 'height' => 200])['src'] : 'default.jpg';

        $arResult = [
            'TIME' => $this->formatTimeTraining($arRgTraining["PROPERTIES"]['TIME']['VALUE'], $arRgTraining["PROPERTIES"]['DURATION']['VALUE']),
            'ID' => $arRgTraining['ID'],
            'NAME' => $arRgTraining['NAME'],
            'PICTURE' => $pic,
            'CREATED_DATE' => $arRgTraining['CREATED_DATE'],
            'NUMBER_OF_PLACES' => $arRgTraining['PROPERTIES']['NUMBER_OF_PLACES']['VALUE'],
            'PERIOD' => $this->getPeriodDate($arRgTraining['PROPERTIES']['DATA_FROM']['VALUE'], $arRgTraining['PROPERTIES']['DATA_TO']['VALUE']),
            'DAY' => Utils::getLangNameDayByNum($arRgTraining['PROPERTIES']['DAY']['VALUE'])
        ];

        return $arResult;
    }

    private function getRgTraining($id_regular_training)
    {
        $rsMediator = new FactoryMediator('RegularTraining');
        $obMediator = $rsMediator->getClass();

        $obRgTraining = $obMediator->buildClass($id_regular_training);

        return $this->obRgTraining = $obRgTraining;
    }

    /**
     * Построение массива медиа файлов по массиву объектов медиа
     * @param array $arObMedia
     * @return array
     */
    private function buildAlbum(array $arObMedia)
    {
        foreach ($arObMedia as $obMedia) {
            if (!($obMedia instanceof IForMediator)) continue;
            if ($obMedia->getId() == $this->id_media) continue;

            $obMedia->getBears();
            $arResult[] = $this->formatDataMedia($obMedia);
        }
        return $arResult;
    }

    /**
     * Формируем детальную страницу медиа
     * @return array
     */
    private function buildMediaPage()
    {
        $this->getMyUserData();
        $this->mainMedia = $this->getListMedia([$this->id_media])[0];
        $this->mainMedia->getBears();

        if ($this->need_other_media)
            $this->getOtherMedia();
        if ($this->need_training)
            $this->getMainTraining();

        $arResult = $this->formatDataMedia($this->mainMedia);
        $arFullDataMedia = $this->mainMedia->getInfo();

        if (!empty($this->arUser))
            $arResult['USER'] = $this->arUser;

        if (!empty($arFullDataMedia['TRAINING'][0])) {
            $obRgTraining = $arFullDataMedia['TRAINING'][0]->getRgTrainingByTraining();
            $obRgTraining->getTrainer();
            $arResult['TRAINING'] = $this->formatDataTrainingFromObject($arFullDataMedia['TRAINING'][0], $obRgTraining->getInfo());
        }

        if (!empty($arResult['TRAINING']['TRAINER'][0]))
            $arResult['AUTHOR'] = $arResult['TRAINING']['TRAINER'][0];

        if (!empty($arFullDataMedia['ALBUM'])) {
            $arResult['ALBUM'] = $this->buildAlbum($arFullDataMedia['ALBUM']);
            $arResult['ID_ALBUM'] = $arResult['TRAINING']['ID_ALBUM'];
        }

        return $arResult;
    }

    /**
     * Получить объекты медиа по массиву из ID
     * @param array $arIdMedia
     * @return array
     */
    public function getListMedia(array $arIdMedia)
    {
        $rsMediator = new FactoryMediator('Media');
        $obMediator = $rsMediator->getClass();

        foreach ($arIdMedia as $id) {
            $arResult[] = $obMediator->buildClass($id);
        }

        return $arResult;
    }

    /**
     * Получить соседние медиа по регулярной тренировке
     */
    private function getOtherMedia()
    {
        $this->mainMedia->getOtherMediaInRgTraining();
    }

    /**
     * Получить тренировку к которой принадлежит медиа
     */
    private function getMainTraining()
    {
        $this->mainMedia->getTrainingWhereMedia();
    }

    /**
     * Получать ли соседние медиа по регулярной тренировке
     */
    public function needOtherMedia()
    {
        $this->need_other_media = true;
    }

    /**
     * Получать ли тренировку к которой принадлежит медиа
     */
    public function needTraining()
    {
        $this->need_training = true;
    }
}
?>