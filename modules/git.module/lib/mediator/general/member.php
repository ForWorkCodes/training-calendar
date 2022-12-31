<?php
namespace Git\Module\Mediator\General;

use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Mediator\Abstracts\Member as AMember;
use Git\Module\Training\Mediator\MediatorForTraining;
use Git\Module\User\Mediator\MediatorForUser;

class Member extends AMember
{
    protected static function getMediatorMedia()
    {
        $obFactory = new FactoryMediator('Media');
        return $obFactory->getClass();
    }

    protected static function getMediatorRegTraining()
    {
        $obFactory = new FactoryMediator('RegularTraining');
        return $obFactory->getClass();
    }

    /**
     * Получение посредника отвечающего за пользователей
     * @return MediatorForUser
     */
    protected static function getMediatorUser()
    {
        $obFactory = new FactoryMediator('User');
        return $obFactory->getClass();
    }

    /**
     * Получение посредника отвечающего за тренировки
     * @return MediatorForTraining
     */
    protected static function getMediatorTraining()
    {
        $obFactory = new FactoryMediator('Training');
        return $obFactory->getClass();
    }

    public function buildClass(int $id) {}
    public function getAll() {}
    public static function getAllStatic() {}
}

?>