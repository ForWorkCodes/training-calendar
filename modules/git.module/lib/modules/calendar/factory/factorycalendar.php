<?php
namespace Git\Module\Modules\Calendar\Factory;

use Git\Module\Factory\Abstracts\FactoryModules;
use Git\Module\Modules\Calendar\Abstracts\ACalendar;
use Git\Module\Modules\Calendar\General\CalendarContinuous;
use Git\Module\Modules\Calendar\General\CalendarList;
use Git\Module\Modules\Calendar\General\CalendarSingle;

class FactoryCalendar extends FactoryModules
{
    public function __construct(int $id, $need_type = null)
    {
        $this->iblock = ACalendar::getIblockId();
        $this->codeType = ACalendar::getCodeTypeProp();
        if ($need_type == null)
            $type = $this->getType($id);
        else
            $type = $need_type;

        switch ($type)
        {
            case 'SINGLE':
                $this->entity = new CalendarSingle($id);
                break;

            case 'CONTINUOUS':
                $this->entity = new CalendarContinuous($id);
                break;

            default:
                $this->entity = new CalendarSingle($id);
                break;
        }

    }

    public static function getByType($need_type = '') {
        switch ($need_type)
        {
            case 'SINGLE':
                $entity = new CalendarSingle();
                break;

            case 'CONTINUOUS':
                $entity = new CalendarContinuous();
                break;

            case 'LIST':
                $entity = new CalendarList();
                break;

            default:
                $entity = new CalendarSingle();
                break;
        }

        return $entity;
    }
}
?>