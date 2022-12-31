<?php
namespace Git\Module\Factory\General;

use Git\Module\Media\Mediator\MediatorForMedia;
use Git\Module\Modules\Calendar\Mediator\MediatorForCalendar;
use Git\Module\RegularTraining\Mediator\MediatorForRegularTraining;
use Git\Module\Training\Mediator\MediatorForTraining;
use Git\Module\User\Mediator\MediatorForUser;

class FactoryMediator
{
    private $entity;

    public function __construct($type)
    {
        switch ($type)
        {
            case 'RegularTraining':
                $this->entity = new MediatorForRegularTraining();
                break;
            case 'Training':
                $this->entity = new MediatorForTraining();
                break;
            case 'User':
                $this->entity = new MediatorForUser();
                break;
            case 'Media':
                $this->entity = new MediatorForMedia();
                break;
            case 'Calendar':
                $this->entity = new MediatorForCalendar();
                break;
        }
    }

    public function getClass()
    {
        return $this->entity;
    }
}
?>