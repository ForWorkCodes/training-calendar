<?php
namespace Git\Module\Training\Interfaces;

use Git\Module\Mediator\Interfaces\IForMediator;

/**
 * Описывает любую тренировку
 * Interface ITraining
 * @package Git\Module\Training\Interfaces
 */
interface ITraining extends IForMediator
{
    /**
     * Вся инфа, скорее всего будут ключи для детализации информации
     * @return mixed
     */
    public function getInfo();

    /**
     * Участники
     * @return mixed
     */
    public function getMembers();

    /**
     * Тренер
     * @return mixed
     */
    public function getTrainer();

    /**
     * Куратор
     * @return mixed
     */
    public function getKurator();

    /**
     * Дата проведения
     * @return mixed
     */
    public function getDate();

    /**
     * Вид спорта тренировки
     * @return mixed
     */
    public function getWave();
}

?>