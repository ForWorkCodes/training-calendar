<?php
namespace Git\Module\Modules\Calendar\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Helpers\Utils;
use Git\Module\Modules\Calendar\Abstracts\ACalendar;

/**
 * Класс для одиночного события в календаре
 * Class CalendarSingle
 * @package Git\Module\Modules\Calendar\General
 */
class CalendarSingle extends ACalendar
{
    const XML_ID_TYPE = "SINGLE";

    public function add($arFields)
    {
        $this->formatTime($arFields);
        $this->addEventInCalendar($arFields);
    }

    public function del()
    {
        $this->delEventFromCalendar();
    }

    public function update($arFields)
    {
        $this->formatTime($arFields);
        $this->updateEventInCalendar($arFields);
    }

    protected function formatTime(&$arFields)
    {
        if (!empty($arFields['DATE_FROM'])) {
            $timeFrom = ($arFields['TIME_FROM']) ? $arFields['TIME_FROM'] . ':00' : '00:00:00';
            $timeTo = ($arFields['TIME_TO']) ? $arFields['TIME_TO'] . ':00' : '00:00:00';
            $date_time_from = $arFields['DATE_FROM'] . ' ' . $timeFrom;
            $date_time_to = $arFields['DATE_FROM'] . ' ' . $timeTo;

            $arFields['DATE_TIME_FROM'] = $date_time_from;
            $arFields['DATE_TIME_TO'] = $date_time_to;
        }
    }

    public static function getListById(array $arId) {}

    /**
     * Инициализация свойств для выдачи на фронт страница создания/редактирования
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getInitFields()
    {
        parent::getInitFields();
    }
}
?>