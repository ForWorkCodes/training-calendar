<?php
namespace Git\Module\Factory\General;


use Git\Module\Factory\Abstracts\FactoryModules;
use Git\Module\Helpers\Utils;
use Git\Module\RegularTraining\General\RegularTraining;

class FactoryRegularTraining extends FactoryModules
{
    /**
     * Вернет нужный тип регулярной тренировки по свойству
     * FactoryRegularTraining constructor.
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->iblock = RegularTraining::getIblockId();
        $this->codeType = RegularTraining::getCodeTypeProp();
        $type = $this->getType($id);

        switch ($type)
        {
            case 'COMMON':
                $this->entity = new RegularTraining($id);
                break;

            default:
                break;
        }

    }
}
?>