<?php
namespace Git\Module\RegularTraining\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Mediator\Interfaces\IMember;
use \Git\Module\Helpers\Utils;
use Git\Module\RegularTraining\Abstracts\RegularTraining as ARegularTraining;
use Git\Module\RegularTraining\Mediator\MediatorForRegularTraining;

class RegularTraining extends ARegularTraining
{
    /**
     * Тренировка знает только своего посредника
     */
    protected function setMediator()
    {
        if (empty($this->mediator))
        {
            $mediator = new MediatorForRegularTraining();
            if ($mediator instanceof IMember)
                $this->mediator = $mediator;
            else
                throw new \Bitrix\Main\LoaderException('Call invalid mediator');
        }
    }

    /**
     * Получить массив данных элементов из инфоблока по их ID
     * @param array $arId
     * @return array
     */
    public static function getListById(array $arId)
    {
        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ID' => $arId,
            'ACTIVE' => 'Y'
        ];
        return Utils::getListElementsByFilter($arFilter);
    }

    /**
     * Получить массив данных всех элементов из инфоблока
     * @return array
     */
    public function getAll()
    {
        return self::getAllStatic();
    }

    /**
     * Получить массив данных всех активных элементов из инфоблока
     * @return array
     */
    public static function getAllStatic()
    {
        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ACTIVE' => 'Y'
        ];

        return Utils::getListElementsByFilter($arFilter);
    }

    /**
     * Получить массив данных всех элементов из инфоблока
     * @return array
     */
    public static function getAllStaticD()
    {
        $arFilter = [
            'IBLOCK_ID' => self::getIblockId()
        ];

        return Utils::getListElementsByFilter($arFilter);
    }

    /**
     * Вернуть текстовое представление поля WAVE
     * @param $id
     * @return array|mixed|string|string[]
     */
    public function formatWaveData($id)
    {
        $ar = Utils::getProperyIblockEnum(['ID' => $id]);

        return end($ar)['VALUE'];
    }

    /**
     * Получить массив ID всех элементов из инфоблока
     * @return array
     */
    public static function getAllIdStatic(array $arFilter = [])
    {
        $arFilter['IBLOCK_ID'] = self::getIblockId();
        if (!isset($arFilter['ACTIVE']))
            $arFilter['ACTIVE'] = 'Y';

        return Utils::getListJustIdInIblockByFilter($arFilter);
    }

    /**
     * Вернет все медиа внутри тренировок данной регулярки
     * @return mixed
     */
    public function getAllMedia()
    {
        $this->getMediator();
        return $this->info['MEDIA'] = $this->mediator->getAllMediaInRgTraining($this);
    }
}
?>