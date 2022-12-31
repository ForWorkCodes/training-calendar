<?php
namespace Git\Module\Mediator\Interfaces;

/**
 * Костяк для посредника, нужны только те методы которые использует посредник для записи в бд
 * Interface IForMediator
 * @package Git\Module\Mediator\Interfaces
 */
interface IForMediator
{
    public function getInfo();
    public function getStatus();
    public function setStatus(string $status);
    public function getId();
    public function getName();
    public function getMediator();
    public static function getListById(array $arId);
}