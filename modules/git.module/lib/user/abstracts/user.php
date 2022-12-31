<?php
namespace Git\Module\User\Abstracts;

use Git\Module\Mediator\Interfaces\IForMediator;

abstract class User implements IForMediator
{
    public $idNew = false;
    protected $errors;
    protected $name;
    protected $id;
    protected $info;
    protected $mediator;
    protected $defaultFields;
    protected $status;

    public function __construct(int $id = null)
    {
        $this->setName();
        $this->defaultFields = [
            'ID',
            'UF_DEPARTMENT',
            'WORK_DEPARTMENT',
            'WORK_POSITION',
            'PERSONAL_PHONE',
            'PERSONAL_GENDER',
            'PERSONAL_BIRTHDAY',
            'PERSONAL_PHOTO',
            'NAME',
            'LAST_NAME',
            'SECOND_NAME',
            'EMAIL',
            'UF_TYPE'
        ];

        if ($id != null)
            $this->setInfo($id);
    }

    protected function setInfo(int $id)
    {
        $this->setId($id);

        $this->info = $this->getDetailInfo();

        $this->info['ID'] = $this->id;
        $this->info['CLASS'] = $this->name;

        $this->setTypeUser();
    }

    public function getName()
    {
        return $this->name;
    }

    protected function setName()
    {
        $this->name = get_class($this);
    }

    public function getId()
    {
        return $this->id;
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @deprecated
     * @param array $arId
     * @return mixed
     */
    public static function getListById(array $arId)
    {
        $arData = [
            'filter' => [
                '=ID' => $arId,
                'ACTIVE' => 'Y'
            ]
        ];
        $obUsers = \Bitrix\Main\UserTable::getList($arData);

        return $obUsers->fetchAll();
    }

    public function getMediator()
    {
        $this->setMediator();
        return $this->mediator;
    }

    /**
     * Установка статуса объекта в таблице связей
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Вернет статус объекта в таблице связей
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function addError($error)
    {
        $this->errors[] = ['message' => $error];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Реализующий класс будет знать какой посредник с ним контактирует
     * @return mixed
     */
    abstract protected function setMediator();
    abstract protected function getDetailInfo();
    abstract protected function setTypeUser();
}
?>