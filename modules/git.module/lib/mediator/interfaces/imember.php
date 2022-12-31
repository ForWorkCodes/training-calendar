<?php
namespace Git\Module\Mediator\Interfaces;

/**
 * Интерфейс для соединения таблицы участников с другими модулями
 * Interface IMember
 * @package Git\Module\Mediator\Interfaces
 */
interface IMember
{
    /**
     * Получение участников связанных с объектом
     * @param IForMediator $parent
     * @param IForMediator $child
     * @return mixed
     */
    public function get(array $params);

    /**
     * Запись участников связанных с объектом
     * @param IForMediator $parent
     * @param IForMediator $child
     * @return mixed
     */
    public function add(IForMediator $parent, IForMediator $child);

    /**
     * Удаление участников связанных с объектом
     * @param IForMediator $parent
     * @param IForMediator $child
     * @return mixed
     */
    public function del(array $arId);

    /**
     * Изменить элемент в таблице
     * @param int $id
     * @param array $arParam
     * @return mixed
     */
    public function update(int $id, array $arParam);

    /**
     * Вернет все активные элементы своего типа (например посредник тренировок вернет все тренировки)
     * @return mixed
     */
    public function getAll();

    /**
     * Формирование объекта через фабрику
     * @return mixed
     */
    public function buildClass(int $id);
    public static function getAllStatic();
}