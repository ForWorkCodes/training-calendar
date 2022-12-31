<?php
namespace Git\Module\Modules\Calendar\General;

use Bitrix\Main\Diag\Debug;
use Git\Module\Modules\Calendar\Abstracts\ACalendar;

class CalendarContinuous extends ACalendar
{
    const XML_ID_TYPE = "CONTINUOUS";

    public function getInitFields()
    {
        parent::getInitFields();
    }

    public function add($arFields)
    {
        $this->formatTime($arFields);
        $this->addEventInCalendar($arFields);
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

        if (!empty($arFields['DATE_TO'])) {
            $date_time_to = $arFields['DATE_TO'] . ' ' . $timeTo;

            $arFields['DATE_TIME_TO'] = $date_time_to;
        }
    }

    public function del()
    {
        $this->delEventFromCalendar();
    }

    public static function getListById(array $arId) {}

    public function update($arFields)
    {
        $this->formatTime($arFields);
        $this->updateEventInCalendar($arFields);
    }
}
?>