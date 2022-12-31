<?php
namespace Git\Module\Media\Factory;

use Git\Module\Factory\Abstracts\FactoryModules;
use Git\Module\Media\General\MediaTraining;

class FactoryMedia extends FactoryModules
{
    public function __construct(int $id, string $need_type = null)
    {
        if (empty($need_type))
            $type = $this->getType($id); // Нужно переделать, тренера возвращать не по полю, а фактически выбирать тренера если если нужно

        switch ($type)
        {
            default:
                $this->entity = new MediaTraining($id);
                break;
        }
    }

    /**
     * Получение типа пользователя
     * В этой фабрике другой тип, выборка из инфоблока не подходит
     * @param $id
     * @return mixed|void
     */
    protected function getType($id)
    {
       return '';
    }
}
?>