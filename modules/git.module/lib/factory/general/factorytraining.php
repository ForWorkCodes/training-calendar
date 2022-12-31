<?php
namespace Git\Module\Factory\General;

use Git\Module\Factory\Abstracts\FactoryModules;
use Git\Module\Training\General\Training;

class FactoryTraining extends FactoryModules
{
    public function __construct($id)
    {
        $this->iblock = Training::getIblockId();
        $this->codeType = Training::getCodeTypeProp();
        $type = $this->getType($id);

        switch ($type)
        {
            case 'COMMON':
                $this->entity = new Training($id);
                break;

            default:
                break;
        }

    }
}
?>