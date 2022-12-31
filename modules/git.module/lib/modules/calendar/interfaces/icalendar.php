<?php
namespace Git\Module\Modules\Calendar\Interfaces;

interface ICalendar
{
    public function getInitFields();
    public function add($arFields);
    public function update($arFields);
    public function del();
    public function getInfo();
    public function getId();
    public function getErrors();
    public function needCheckPermission(bool $bool = true);
    public static function getListById(array $arId);
}
?>