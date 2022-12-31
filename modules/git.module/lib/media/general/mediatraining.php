<?php
namespace Git\Module\Media\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Helpers\Utils;
use \Git\Module\Media\Abstracts\Media;
use Git\Module\Media\Mediator\MediatorForMedia;
use Git\Module\Mediator\Interfaces\IMember;

class MediaTraining extends Media
{
    protected function setMediator()
    {
        $mediator = new MediatorForMedia();
        if ($mediator instanceof IMember)
            $this->mediator = $mediator;
        else
            throw new \Bitrix\Main\LoaderException('Call invalid mediator');
    }

    protected function getDetailInfo()
    {
        if (empty($this->id)) return;

        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ACTIVE' => 'Y',
            '=ID' => $this->id
        ];
        $obDatas = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            false,
            []
        );
        while ($obData = $obDatas->GetNextElement())
        {
            $arResult = $obData->GetFields();
            $arResult['PROPERTIES'] = $obData->GetProperties();
        }

        if (empty($arResult))
            $this->id = '';

        return $arResult;
    }

    public static function getAllStatic()
    {
        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ACTIVE' => 'Y'
        ];
        return Utils::getListElementsByFilter($arFilter);
    }

    public static function getIblockId()
    {
        $iblock_id = VIDEO_IBLOCK;
        return $iblock_id;
    }

    /**
     * Получить тренировку к которой принадлежит медиа
     * @return mixed
     */
    public function getTrainingWhereMedia()
    {
        $this->getMediator();
        return $this->info['TRAINING'] = $this->mediator->getTrainingWhereMedia($this);
    }

    /**
     * Получить соседние медиа по регулярной тренировке
     * @return mixed
     */
    public function getOtherMediaInRgTraining()
    {
        $this->getMediator();
        return $this->info['ALBUM'] = $this->mediator->getOtherMediaInRgTraining($this);
    }
}
?>