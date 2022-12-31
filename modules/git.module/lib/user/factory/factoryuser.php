<?php
namespace Git\Module\User\Factory;

use Bitrix\Main\Diag\Debug;
use Git\Module\Factory\Abstracts\FactoryModules;
use Git\Module\User\General\Kurator;
use Git\Module\User\General\Trainer;
use Git\Module\User\General\User;

class FactoryUser extends FactoryModules
{
    public function __construct(int $id, string $need_type = null)
    {
        if ($need_type == null)
            $type = $this->getType($id); // Нужно переделать, тренера возвращать не по полю, а фактически выбирать тренера если если нужно
        else
            $type = $need_type;

        switch ($type)
        {
            case 'kurator':
                $this->entity = new Kurator($id);
                break;

            case 'trainer':
                $this->entity = new Trainer($id);
                break;

            default:
                $this->entity = new User($id);
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
        $type = '';
        $arGroups = \CUser::GetUserGroup($id);

        if ( in_array(TRAINER_GROUP, $arGroups) )
            $type = 'trainer';
        if ( in_array(KURATOR_GROUP, $arGroups) )
            $type = 'kurator';

        return $type;
    }
}
?>