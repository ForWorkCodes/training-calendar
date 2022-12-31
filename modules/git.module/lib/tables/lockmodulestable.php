<?php
namespace Git\Module\Tables;

use Bitrix\Main\Entity;

class lockModulesTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'itrack_lock_modules';
    }

    public static function getUfId()
    {
        return 'ITRACK_LOCK_MODULES';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\StringField('UF_PARENT_ENTITY', array(
                'required' => true
            )),
            new Entity\StringField('UF_PARENT_ID', array(
                'required' => true
            )),
            new Entity\StringField('UF_CHILD_ENTITY', array(
                'required' => true
            )),
            new Entity\StringField('UF_CHILD_ID', array(
                'required' => true
            )),
            new Entity\StringField('UF_ACTIVE', array(
                'required' => true
            )),
            new Entity\DatetimeField('UF_DATE_CREATE', array(
                'required' => true
            )),
        );
    }
}

?>