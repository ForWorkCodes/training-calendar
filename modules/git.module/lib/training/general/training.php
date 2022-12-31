<?php
namespace Git\Module\Training\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Event;
use CIBlockProperty;
use CIBlockPropertyEnum;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IMember;
use Git\Module\Training\Abstracts\Training as ATraining;
use Git\Module\Training\Mediator\MediatorForTraining;

class Training extends ATraining
{
    protected $submitNote = false;
    /**
     * Вернет все обязательные Свойства для создания Элемента Тренировки
     */
    private static function getRequeryPropIblock()
    {
        $arResult = [];
        $properties = CIBlockProperty::GetList([], [
            "ACTIVE"=>"Y",
            "IBLOCK_ID"=> self::getIblockId(),
            'IS_REQUIRED' => 'Y'
            ]
        );
        while ($prop_fields = $properties->GetNext())
        {
            $arResult[] = $prop_fields['CODE'];
        }

        return $arResult;
    }

    /**
     * Проверка заполненности обязательных полей
     * @param $arParams
     * @param array $arKeyRequery
     *
     * @return bool
     */
    private static function validationParams($arParams, array $arKeyRequery)
    {
        foreach ($arKeyRequery as $item) {

            if(empty($arParams['PROPERTY_VALUES'][$item])){
                return $item;
            }
        }
        return true;
    }

    private static function getIdTypeTraining(string $strType)
    {
        $property_enums = CIBlockPropertyEnum::GetList([],[
            'XML_ID'=>$strType,
            "IBLOCK_ID"=>self::getIblockId(),
            "CODE"=>"TYPE"
            ]
        );
        if($enum_fields = $property_enums->GetNext()){
          return $enum_fields['ID'];
        }
         return false;
    }

    /**
     * Создание Элемента Тренировки
     * @param $arParams
     */
    public function createTraining($arParams)
    {
        $arParams['IBLOCK_ID'] = self::getIblockId();
        $arRequeryProp = self::getRequeryPropIblock();
        $newElementId = false;

        $check = self::validationParams($arParams, $arRequeryProp);
        if ($check === true) {
            $arParams['PROPERTY_VALUES']['TYPE'] = self::getIdTypeTraining($arParams['PROPERTY_VALUES']['TYPE']);

            $obElement = new \CIBlockElement();

            $bxEvent = new Event('git.module', 'onBeforeTrainingAdd', []);
            $bxEvent->send();

            if(!$newElementId = $obElement->Add($arParams)){
                $arLog = [
                    "SEVERITY" => "TRAINING",
                    "AUDIT_TYPE_ID" => "CREATE_TRAINING",
                    "MODULE_ID" => "git.module",
                    "DESCRIPTION" => 'Error create training: ' . $obElement->LAST_ERROR,
                ];
                \CEventLog::Add($arLog);
            } else {
                $bxEvent = new Event('git.module', 'onAfterTrainingAdd', [
                    'ID_TRAINING' => $newElementId
                ]);
                $bxEvent->send();
            }
        } else {
            $arLog = [
                "SEVERITY" => "TRAINING",
                "AUDIT_TYPE_ID" => "CREATE_TRAINING",
                "MODULE_ID" => "git.module",
                "DESCRIPTION" => 'Error create training. Need field: ' . $check,
            ];
            \CEventLog::Add($arLog);
        }

        return $newElementId;
    }

    /**
     * Тренировка знает только своего посредника
     */
    protected function setMediator()
    {
        if (empty($this->mediator))
        {
            $mediator = new MediatorForTraining();
            if ($mediator instanceof IMember)
                $this->mediator = $mediator;
            else
                throw new \Bitrix\Main\LoaderException('Call invalid mediator');
        }
    }

    public static function getListById(array $arId)
    {
        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ID' => $arId,
            'ACTIVE' => 'Y'
        ];
        return Utils::getListElementsByFilter($arFilter);
    }

    public static function getAllStatic()
    {
        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'ACTIVE' => 'Y'
        ];
        return Utils::getListElementsByFilter($arFilter);
    }

    /**
     * Получить значение для вывода кнопки в тренировке
     * @param null $id_user
     * @return mixed
     */
    public function whatCanUser($id_user = null)
    {
        $this->getMediator();
        return $this->info['WHAT_CAN'] = $this->mediator->getWhatCanUser($this, $id_user);
    }

    /**
     * Вернет медиа тренировки
     * @return mixed
     */
    public function getMediaInTraining()
    {
        $this->getMediator();
        return $this->info['MEDIA'] = $this->mediator->getMediaInTraining($this);
    }

    /**
     * Обновление полей тренировки
     * @param array $arFields
     * @return bool|string
     */
    public function editFields(array $arFields)
    {
        if (empty($this->getId())) return;

        $bxEvent = new Event('git.module', 'onBeforeTrainingFieldsUpdate', []);
        $bxEvent->send();

        $obEl = new \CIBlockElement();
        $result = $obEl->Update($this->getId(), $arFields);

        if ($result != true)
            $result = $obEl->LAST_ERROR;

        $bxEvent = new Event('git.module', 'onAfterTrainingFieldsUpdate', []);
        $bxEvent->send();

        return $result;
    }

    /**
     * Обновить описание тренировки
     * @param $text
     */
    public function editDescription($text)
    {
        if (empty($this->getId())) return;

        \CIBlockElement::SetPropertyValuesEx($this->getId(), self::getIblockId(), ['DESCRIPTION' => array('VALUE'=>array('TYPE'=>'HTML', 'TEXT'=>$text))]);

        $bxEvent = new Event('git.module', 'onAfterTrainingDescriptionUpdate', ['ENTITY' => $this]);
        $bxEvent->send();
    }

    /**
     * Закрытие записи на тренировку
     */
    public function closeTraining()
    {
        \CIBlockElement::SetPropertyValuesEx($this->getId(), self::getIblockId(), ['OPEN' => 'N']);

        $bxEvent = new Event('git.module', 'onAfterCloseTraining', ['ENTITY' => $this]);
        $bxEvent->send();
    }

    /**
     * Флаг отправки отчета у тренировки
     * @return bool
     */
    public function endTraining()
    {
        if ($this->info['PROPERTIES']['OPEN']['VALUE'] == 'Y') return false;

        \CIBlockElement::SetPropertyValuesEx($this->getId(), self::getIblockId(), ['SUBMIT' => 'Y']);

        $bxEvent = new Event('git.module', 'onAfterReportSend', ['ENTITY' => $this]);
        $bxEvent->send();

        return true;
    }

    /**
     * Полное закрытие тренировки
     * @param $id
     * @return bool
     */
    public function finishTraining()
    {
        if ($this->info['PROPERTIES']['SUBMIT']['VALUE'] != 'Y') return false;

        \CIBlockElement::SetPropertyValuesEx($this->getId(), self::getIblockId(), ['FINISH' => 'Y']);

        $bxEvent = new Event('git.module', 'onAfterFinishTraining', ['ENTITY' => $this]);
        $bxEvent->send();

        return true;
    }

    /**
     * Получение регулярной тренировки по данной тренировке
     * @return mixed
     */
    public function getRgTrainingByTraining()
    {
        $this->getMediator();

        return $this->info['REGULAR_TRAINING'] = $this->mediator->getRgTrainingByTraining($this);
    }

    /**
     * Ключ для отправки уведомлений
     */
    public function submitNote()
    {
        $this->submitNote = true;
    }

    public function isSubmitNote()
    {
        return $this->submitNote;
    }
}
?>