<?
namespace Git\Module\Builder\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Entity\Result;
use Bitrix\Sale\Delivery\Restrictions\ByWeight;
use Git\Module\Modules\Calendar\Factory\FactoryCalendar;
use Git\Module\Modules\Calendar\Interfaces\ICalendar;

class IcsBuilder
{
    /**
     * @var ICalendar
     */
    protected $obCalendar;
    protected $arInfo;
    protected $result;
    protected $text;
    protected $errors;

    public function buildIcsByIdCalendar($id)
    {
        $this->result = new Result();
        $this->getCalendarEvent($id);

        if (empty($this->obCalendar->getErrors())) {
            $ics = new \Git\Module\Modules\Calendar\Ics\GenerateIcs(array(
                'dtstart' => $this->arInfo['NO_FORMAT_DATE_FROM'],
                'dtend' => $this->arInfo['NO_FORMAT_DATE_TO'],
                'location' => $this->arInfo['ADDRESS'],
                'description' => $this->arInfo['NO_FORMAT_PREVIEW_TEXT'],
                'SUMMARY;LANGUAGE=ru' => $this->arInfo['NAME']
//                'url' => '',
            ));

            $this->text = $ics->to_string($this->arInfo['NAME']);
        } else {
            $this->errors = $this->obCalendar->getErrors();
        }
    }

    public function getText()
    {
        return $this->text;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function getCalendarEvent($id)
    {
        $obFactory = new FactoryCalendar($id);
        $this->obCalendar = $obFactory->getClass();
        $this->arInfo = $this->obCalendar->getInfo();
    }
}
?>