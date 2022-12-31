<?php
namespace Git\Module\Factory\Abstracts;

use Git\Module\Modules\Calendar\Interfaces\ICalendar;

class FactoryModules
{
    protected $entity;
    protected $iblock;
    protected $codeType;

    /**
     * Получит тип текущей элемента ИБ
     * @param $id
     * @return mixed
     */
    protected function getType($id)
    {
        $arData = \CIBlockElement::GetProperty($this->iblock, $id, $by="sort", $order="asc",['CODE'=>$this->codeType])->GetNext();
        return $arData['VALUE_XML_ID'];
    }

    /**
     * Вернет найденный объект
     * @return ICalendar
     */
    public function getClass()
    {
        return $this->entity;
    }
}
?>