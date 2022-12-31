<?
namespace Git\Module\Builder\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Error;
use Git\Module\Modules\Calendar\Abstracts\ACalendar;
use Git\Module\Modules\Calendar\Factory\FactoryCalendar;

class CalendarBuilder
{
    public static function getPageCalendarInit($idCalendar = null)
    {
        if ($idCalendar != null) {
            $obFactory = new FactoryCalendar($idCalendar);
            $obCalendar = $obFactory->getClass();
        } else {
            $obCalendar = FactoryCalendar::getByType('SINGLE');
        }

        $obCalendar->getInitFields();

        return $obCalendar;
    }

    public static function getCalendarInit()
    {
        $obCalendar = FactoryCalendar::getByType('LIST');
        $obCalendar->getInitFields();

        return $obCalendar;
    }

    public static function getCalendarList($arFilter)
    {
        $obCalendar = FactoryCalendar::getByType('LIST');
        $obCalendar->get($arFilter);

        return $obCalendar;
    }

    public static function addInCalendar($arFields)
    {
        $type = self::getType($arFields);
        if ($type == false) {
            $result = new Result();
            $result->addError(new Error('Need type event'));

            return $result;
        }

        $ObCalendar = FactoryCalendar::getByType($type);
        $ObCalendar->needCheckPermission();
        $ObCalendar->add($arFields);

        return $ObCalendar;
    }

    public static function updateCalendar($arFields)
    {
        $type = self::getType($arFields);
        if ($type == false) {
            $result = new Result();
            $result->addError(new Error('Need type event'));

            return $result;
        }

        $obCalendar = FactoryCalendar::getByType($type);
        $obCalendar->needCheckPermission();
        $obCalendar->update($arFields);

        return $obCalendar;
    }

    public static function delFromCalendar($id)
    {
        $obFactory = new FactoryCalendar($id);
        $obCalendar = $obFactory->getClass();
        $obCalendar->needCheckPermission();
        $obCalendar->del();

        return $obCalendar;
    }

    /**
     * @param $id
     * @return IcsBuilder
     */
    public static function downloadIcs($id)
    {
        $ics = new IcsBuilder();
        $ics->buildIcsByIdCalendar($id);

        return $ics;
    }

    /**
     * Получить тип события календаря
     * @param $arFields
     * @return false|string
     */
    protected static function getType($arFields) {

        $arTypeList = ACalendar::getTypeListStatic();

        if (!empty($arTypeList)) {
            foreach ($arTypeList as $arType) {
                if ($arFields['TYPE'] == $arType['ID']) {
                    $type = $arType['XML'];
                }
            }
        }

        if (empty($type))
            $type = false;

        return $type;
    }
}
?>