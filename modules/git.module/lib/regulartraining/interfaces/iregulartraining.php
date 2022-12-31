<?php
namespace Git\Module\RegularTraining\Interfaces;

use Git\Module\Mediator\Interfaces\IForMediator;

/**
 * Описывает любую регулярную тренировку
 * Interface IRegularTraining
 * @package Git\Module\Training\Interfaces
 */
interface IRegularTraining extends IForMediator
{
    /**
     * Вся инфа, скорее всего будут ключи для детализации информации
     * @return mixed
     */
    public function getInfo();

    /**
     * Какие тренировки входят в данную регулярную тренировку
     * @return mixed
     */
    public function getTrainings(bool $convert_in_array = true);

    /**
     * Участники
     * @return mixed
     */
    public function getMembers();

    /**
     * Модератор
     * @return mixed
     */
    public function getKurator();

    /**
     * Дата проведения
     * @return mixed
     */
    public function getDate();

    /**
     * Вид спорта регулярной тренировки
     * @return mixed
     */
    public function getWave();
}
?>