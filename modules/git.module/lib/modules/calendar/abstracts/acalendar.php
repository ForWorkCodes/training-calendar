<?php
namespace Git\Module\Modules\Calendar\Abstracts;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Git\Module\Helpers\Utils;
use Git\Module\Highloadblock\HlbForWs;
use Git\Module\Highloadblock\HLBWrap;
use Git\Module\Modules\Calendar\Interfaces\ICalendar;
use Git\Module\Modules\Calendar\Permission\PermissionForCalendar;

/**
 * Класс для работы с календарем
 * Class ACalendar
 * @package Git\Module\Modules\Calendar\Abstracts
 */
abstract class ACalendar implements ICalendar
{
    public $errors = [];
    public $idNewCalendar = false;
    public $isDel = false;
    protected $id;
    /**
     * @var PermissionForCalendar
     */
    protected $permission;
    protected $info;
    protected $needCheckPermission = false;
    protected $arFilter = [];
    protected $arFields = [
        'NAME',
        'ACTIVE',
        'PREVIEW_PICTURE',
        'DETAIL_PICTURE',
        'PREVIEW_TEXT',
        'DETAIL_TEXT'
    ];
    protected $arProps = [
        'VIDEO',
        'TYPE',
        'DIRECTIONS',
        'IMPORTANT',
        'PLACE',
        'ADDRESS',
        'DATE_TIME_FROM',
        'DATE_TIME_TO',
        'DATE_TYPE',
        'PERSON_ID',
        'PERSON_NAME',
        'NOT_SHOW_TIME',
        'PERSON_PICTURE',
        'PERSON_ROLE',
        'PERSON_EMAIL',
        'PERSON_PHONE',
    ];
    protected $arRequired = [
        'DATE_TIME_FROM' => 'DATE_TIME_FROM',
        'DATE_TIME_TO' => 'DATE_TIME_TO',
        'NAME' => 'NAME',
        'DETAIL_TEXT' => 'DETAIL_TEXT',
        'PREVIEW_TEXT' => 'PREVIEW_TEXT',
        'DIRECTIONS' => 'DIRECTIONS',
        'PLACE' => 'PLACE',
        'PERSON_ROLE' => 'PERSON_ROLE',
        'PERSON_EMAIL' => 'PERSON_EMAIL'
    ];
    protected $newFields = [];
    protected $newProps = [];

    const CODE_TYPE = "TYPE";

    public function __construct($id = null)
    {
        $this->setId(false);

        if ($id != null)
            $this->setInfo($id);
    }

    protected function setInfo($id)
    {
        $this->setId($id);

        $this->info = $this->getDetailInfo();

        $this->info['ID'] = $this->id;
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public static function getCodeTypeProp()
    {
        return self::CODE_TYPE;
    }

    protected function getDetailInfo()
    {
        if ($this->id == false) return;

        $arFilter = [
            'IBLOCK_ID' => self::getIblockId(),
            'PROPERTY_' . self::CODE_TYPE => self::getTypeId($this->id),
            '=ID' => $this->id
        ];

        $obDatas = \CIBlockElement::GetList(
            [],
            $arFilter,
            false,
            false,
            []
        );
        while ($obData = $obDatas->GetNextElement())
        {
            $arResult = $obData->GetFields();
            $arResult['PROPERTIES'] = $obData->GetProperties();
        }

        if (empty($arResult))
            $this->id = false;

        $arResult = $this->formatDataForFront($arResult);

        return $arResult;
    }

    public static function getIblockId()
    {
        $iblock_id = Utils::getIdByCode('calendar');
        return $iblock_id;
    }

    protected function addEventInCalendar($arFields)
    {
        if ($this->needCheckPermission) {
            if (!$this->isCanAdd()) {
                $this->addError('Need access');
                return;
            }
        }

        if (empty($arFields['IMPORTANT']))
            $arFields['IMPORTANT'] = 'N';
        if (empty($arFields['ACTIVE']))
            $arFields['ACTIVE'] = 'N';

        $this->checkFieldsForAdd($arFields);
        $this->formatFields($arFields);

        $DT = new \DateTime();

//        if (empty($arFields['TIME_FROM']) && empty($arFields['TIME_TO'])) {
//            $this->newProps['NOT_SHOW_TIME'] = 'Y';
//        }

        $arFieldsNew = $this->newFields;
        $arFieldsNew['CODE'] = str_replace(' ', '', $arFields['DATE_TIME_FROM']) . $DT->format('d.m.Y-H:i');
        $arFieldsNew['PROPERTY_VALUES'] = $this->newProps;
        $arFieldsNew['IBLOCK_ID'] = self::getIblockId();

        $obEl = new \CIBlockElement();

        if ($id = $obEl->Add($arFieldsNew))
            $this->idNewCalendar = $id;
        else
            $this->addError($obEl->LAST_ERROR);
    }

    protected function updateEventInCalendar($arFields)
    {
        if ($this->needCheckPermission) {
            if (!$this->isCanUpdate()) {
                $this->addError('Need access');
                return;
            }
        }

        if (empty($arFields['IMPORTANT']))
            $arFields['IMPORTANT'] = 'N';
        if (empty($arFields['ACTIVE']))
            $arFields['ACTIVE'] = 'N';

        $this->checkFieldsForAdd($arFields);
        $this->formatFields($arFields);
        $arFields['IBLOCK_ID'] = self::getIblockId();

//        if (empty($arFields['TIME_FROM']) && empty($arFields['TIME_TO'])) {
//            $this->newProps['NOT_SHOW_TIME'] = 'Y';
//        }

        $obEl = new \CIBlockElement();

        if (!empty($this->newFields))
            $obEl->Update($arFields['ID'], $this->newFields);

        if (!empty($this->newProps))
            foreach ($this->newProps as $key => $prop)
                \CIBlockElement::SetPropertyValuesEx($arFields['ID'], $arFields['IBLOCK_ID'], array($key => $prop));

        $this->endUpdate = true;
    }

    /**
     * Проверка приходящих свойств на обязательные
     * @param $arFields
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function checkFieldsForAdd($arFields)
    {
        $result = array_diff_key($this->arRequired, $arFields);
        $str = 'Need fields: ';
        foreach ($result as $key => $res)
            $keys .= $key . '; ';

        if (!empty($keys))
            throw new \Bitrix\Main\ArgumentException($str.$keys);

        foreach ($this->arRequired as $required)
            if (empty($arFields[$required]))
                throw new \Bitrix\Main\ArgumentException('Field ' . $required . ' is empty');
    }

    protected function delEventFromCalendar()
    {
        if ($this->id == false) {
            $this->addError('Need select ID');
            return;
        }

        if ($this->needCheckPermission) {
            if (!$this->isCanDel()) {
                $this->addError('Need access');
                return;
            }
        }

        $result = \CIBlockElement::Delete($this->id);

        if ($result == true) {
            if (!empty($this->info['VIDEO_FULL']['ID'])) {
                $obMedia = new \GModuleNewMedia();
                $obMedia->delMedia($this->info['VIDEO_FULL']['ID']);
            }
        }

        $this->isDel = $result;
    }

    /**
     * Проверять ли права пользователя при работе с данным объектом
     * @param bool $bool
     */
    public function needCheckPermission(bool $bool = true)
    {
        $this->needCheckPermission = $bool;
    }

    public function isCanAdd()
    {
        if (!$this->needCheckPermission) return true;

        $this->getPermissionObj();

        if ($this->permission->isCanAdd())
            $can = true;
        else
            $can = false;

        return $can;
    }

    public function isCanDel()
    {
        if (!$this->needCheckPermission) return true;

        $this->getPermissionObj();

        if ($this->permission->isCanDel())
            $can = true;
        else
            $can = false;

        return $can;
    }

    public function isCanUpdate()
    {
        if (!$this->needCheckPermission) return true;

        $this->getPermissionObj();

        if ($this->permission->isCanUpdate())
            $can = true;
        else
            $can = false;

        return $can;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function formatFields($arFields) {
        foreach ($arFields as $field => $value) {
            if (in_array($field, $this->arProps)) {
                switch ($field) {
                    case "IMPORTANT":
                        if ($this->needCheckPermission)
                            if ($this->isCanSetImportant())
                                $this->newProps[$field] = $value;

                        break;
                    default:
                        $this->newProps[$field] = $value;
                }
            } elseif (in_array($field, $this->arFields)) {
                switch ($field) {
                    default:
                        $this->newFields[$field] = $value;
                }
            }
        }

    }

    public function addError($error)
    {
        $this->errors[] = ['message' => $error];
    }

    protected function addDataToInfo($key, $arData)
    {
        $this->info[$key] = $arData;
    }

    /**
     * Форматируем данные для фронта
     * @param $arInfo
     * @return array|void
     */
    public function formatDataForFront($arInfo)
    {
        if (!$this->id) return;

        $arResult = [
            'NAME' => $arInfo['~NAME'],
            'ACTIVE' => $arInfo['ACTIVE'],
            'PREVIEW_TEXT' => $arInfo['~PREVIEW_TEXT'],
            'NO_FORMAT_PREVIEW_TEXT' => $arInfo['PREVIEW_TEXT'],
            'DETAIL_TEXT' => $arInfo['~DETAIL_TEXT'],
            'PREVIEW_PICTURE' => ($arInfo['PREVIEW_PICTURE']) ? \CFile::GetPath($arInfo['PREVIEW_PICTURE']) : '',
            'DETAIL_PICTURE' => ($arInfo['DETAIL_PICTURE']) ? \CFile::GetPath($arInfo['DETAIL_PICTURE']) : '',
            'TYPE' => [
                'NAME' => $arInfo['PROPERTIES']['TYPE']['VALUE'],
                'ID' => $arInfo['PROPERTIES']['TYPE']['VALUE_ENUM_ID'],
                'XML' => $arInfo['PROPERTIES']['TYPE']['VALUE_XML_ID']
            ],
            'TYPE_CHECK' => $arInfo['PROPERTIES']['TYPE']['VALUE_ENUM_ID'],
            'DATE_TYPE_CHECK' => $arInfo['PROPERTIES']['DATE_TYPE']['VALUE_ENUM_ID'],
            'IMPORTANT' => $arInfo['PROPERTIES']['IMPORTANT']['VALUE'],
            'PERSON_ID' => $arInfo['PROPERTIES']['PERSON_ID']['VALUE'],
            'PERSON_NAME' => $arInfo['PROPERTIES']['PERSON_NAME']['VALUE'],
            'PERSON_PICTURE' => ($arInfo['PROPERTIES']['PERSON_PICTURE']['VALUE']) ? \CFile::GetPath($arInfo['PROPERTIES']['PERSON_PICTURE']['VALUE']) : USER_DEFAULT_PIC,
            'PERSON_ROLE' => $arInfo['PROPERTIES']['PERSON_ROLE']['~VALUE'],
            'PERSON_LINK' => (!empty($arInfo['PROPERTIES']['PERSON_ID']['VALUE']) ? \GModuleUser::getPublicUserLink($arInfo['PROPERTIES']['PERSON_ID']['VALUE']) : ''),
            'PERSON_EMAIL' => $arInfo['PROPERTIES']['PERSON_EMAIL']['VALUE'],
            'PERSON_PHONE' => $arInfo['PROPERTIES']['PERSON_PHONE']['VALUE'],
            'DIRECTIONS' => $arInfo['PROPERTIES']['DIRECTIONS']['VALUE'],
            'DIRECTIONS_CHECK' => $arInfo['PROPERTIES']['DIRECTIONS']['VALUE'],
            'PLACE' => $arInfo['PROPERTIES']['PLACE']['VALUE'],
            'PLACE_CHECK' => $arInfo['PROPERTIES']['PLACE']['VALUE'],
            'NOT_SHOW_TIME' => $arInfo['PROPERTIES']['NOT_SHOW_TIME']['VALUE'],
            'ADDRESS' => $arInfo['PROPERTIES']['ADDRESS']['VALUE'],
            'NO_FORMAT_DATE_FROM' => $arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE'],
            'NO_FORMAT_DATE_TO' => $arInfo['PROPERTIES']['DATE_TIME_TO']['VALUE'],
        ];

        if (!empty($arInfo['PROPERTIES']['VIDEO']['VALUE'])) {
            $obMedia = new \GModuleNewMedia();
            $return = $obMedia->getMediaByFilter(['=ID' => $arInfo['PROPERTIES']['VIDEO']['VALUE']], false);
            if (!empty($return['result']) && count($return['result']) > 0 && $return['status'] == 'success') {
                $arResult['VIDEO_FULL'] = $return['result'][0];
                $arResult['VIDEO'] = \CFile::GetPath($return['result'][0]['PROPERTIES']['FILE']['VALUE']);
                $arResult['videoId'] = $arResult['VIDEO_FULL']['ID'];
            }
        }

        $DTF = new DateTime($arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE']);
        $tmpDTF = new DateTime($arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE']);
        $DTT = new DateTime($arInfo['PROPERTIES']['DATE_TIME_TO']['VALUE']);
        $DTN = new DateTime();

        $arResult['TIME_FROM'] = $DTF->format('H:i');
        $arResult['TIME_TO'] = $DTT->format('H:i');

        if ($arResult['TIME_TO'] == '00:00')
            $DTT->add('23 hours 59 minutes');


        // Ввести время для дальнейшей сортировки
        if ($arResult['TIME_FROM'] == '00:00')
            $tmpDTF->add('23 hours 59 minutes');
        // ---

        if ($DTN > $tmpDTF)
            $arResult['DATE_START'] = $DTT->format('Y.m.d');
        else
            $arResult['DATE_START'] = $DTF->format('Y.m.d');

        if ($DTN > $DTT)
            $arResult['IS_END'] = true;

        if ($arResult['TIME_FROM'] == '00:00' && $arResult['TIME_TO'] == '00:00')
            $arResult['TIME_NAME'] = GetMessage('ALL_DAY');

        if ($arResult['TYPE']['XML'] == 'SINGLE') {

            $arResult['DATE'] = $DTF->format('Y.m.d');
            $arResult['DAY_NAME'] = Utils::getLangNameDay($arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE']);
            $arResult['DAY_SHORT'] = Utils::getSortLangNameDay($arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE']);

        } elseif ($arResult['TYPE']['XML'] == 'CONTINUOUS') {

            $arResult['DATE'] = [$DTF->format('Y.m.d'), $DTT->format('Y.m.d')];
            $arResult['DATE_TYPE'] = [
                'NAME' => $arInfo['PROPERTIES']['DATE_TYPE']['VALUE'],
                'ID' => $arInfo['PROPERTIES']['DATE_TYPE']['VALUE_ENUM_ID'],
                'XML' => $arInfo['PROPERTIES']['DATE_TYPE']['VALUE_XML_ID'],
            ];
            $arResult['DAY_FROM_NAME'] = Utils::getLangNameDay($arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE']);
            $arResult['DAY_FROM_SHORT'] = Utils::getSortLangNameDay($arInfo['PROPERTIES']['DATE_TIME_FROM']['VALUE']);
            $arResult['DAY_TO_NAME'] = Utils::getLangNameDay($arInfo['PROPERTIES']['DATE_TIME_TO']['VALUE']);
            $arResult['DAY_TO_SHORT'] = Utils::getSortLangNameDay($arInfo['PROPERTIES']['DATE_TIME_TO']['VALUE']);

        }

        return $arResult;
    }

    public static function getType($id)
    {
        $arData = \CIBlockElement::GetProperty(self::getIblockId(), $id, $by="sort", $order="asc",['CODE' => self::CODE_TYPE])->GetNext();
        return $arData['VALUE_XML_ID'];
    }

    public static function getTypeId($id)
    {
        $arData = \CIBlockElement::GetProperty(self::getIblockId(), $id, $by="sort", $order="asc",['CODE' => self::CODE_TYPE])->GetNext();
        return $arData['VALUE'];
    }

    /**
     * Получить объект отвечающий за обработку прав
     */
    protected function getPermissionObj()
    {
        if (!is_object($this->permission))
            $this->permission = new PermissionForCalendar();
    }

    /**
     * Может ли пользователь получить события черновики
     * @return bool
     */
    protected function isCanGetDraft()
    {
        $this->getPermissionObj();

        return $this->permission->isCanGetDraft();
    }

    /**
     * Может ли задать IMPORTANT для события
     * @return bool
     */
    protected function isCanSetImportant()
    {
        $this->getPermissionObj();

        return $this->permission->isCanSetImportant();
    }

    /**
     * Получить все элементы хайлоад "Место"
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getPlaceHbData()
    {
        $hlb = new HLBWrap('b_hlbd_placement');
        $rsPlace = $hlb->getList([]);

        while ($arPlace = $rsPlace->fetch()) {
            $tmp = [
                'NAME' => $arPlace['UF_NAME'],
                'ID' => $arPlace['UF_XML_ID']
            ];

            if (!empty($this->info['PLACE_CHECK']) && $this->info['PLACE_CHECK'] == $tmp['ID'])
                $tmp['ACTIVE'] = true;

            $arPlaces[] = $tmp;
        }

        $this->addDataToInfo('PLACE', $arPlaces);

        return $arPlaces;
    }

    /**
     * Получить список значений хайлоад "Направления"
     */
    protected function getDirectionsList()
    {
        $hlb = new HLBWrap('b_hlbd_directionsmain');
        $param = [
            'order' => ['UF_SORT' => 'DESC']
        ];
        $rsDirects = $hlb->getList($param);

        while ($arDirect = $rsDirects->fetch()) {
            $arDirectsMain[] = [
                'NAME' => $arDirect['UF_NAME'],
                'ID' => $arDirect['UF_XML_ID'],
                'COLOR' => $arDirect['UF_COLOR'],
                'TRANSFER' => $arDirect['ID']
            ];
        }

        $this->addDataToInfo('DIRECTIONS_MAIN', $arDirectsMain);

        $hlb = new HLBWrap('b_hlbd_directions');
        $rsDirects = $hlb->getList($param);

        while ($arDirect = $rsDirects->fetch()) {
            $arDirectChild = [
                'NAME' => $arDirect['UF_NAME'],
                'ID' => $arDirect['UF_XML_ID'],
                'PARENT' => $arDirect['UF_DIRECTION_MAIN']
            ];

            if (!empty($this->info['DIRECTIONS_CHECK']) && $this->info['DIRECTIONS_CHECK'] == $arDirectChild['ID'])
                $arDirectChild['ACTIVE'] = true;

            foreach ($arDirectsMain as $arDirectMain)
                if ($arDirect['UF_DIRECTION_MAIN'] == $arDirectMain['TRANSFER']) {
                    $arDirectChild['PARENT_NAME'] = $arDirectMain['NAME'];
                    $arDirectChild['COLOR'] = $arDirectMain['COLOR'];
                }

            $arDirectsChild[] = $arDirectChild;
        }

        unset($arDirectChild);
        $this->addDataToInfo('DIRECTIONS_CHILD', $arDirectsChild);

        foreach ($arDirectsMain as &$arDirectMain) {
            foreach ($arDirectsChild as $arDirectChild) {
                if ($arDirectMain['TRANSFER'] == $arDirectChild['PARENT']) {
                    $arDirectMain['CHILD'][] = $arDirectChild;
                }
            }
        }

        $this->addDataToInfo('DIRECTIONS', $arDirectsMain);
    }

    /**
     * Получить список значений свойства "Дни события"
     */
    protected function getDateTypeList()
    {
        $obProps = \CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => self::getIblockId(),
                'CODE' => 'DATE_TYPE'
            ]
        );
        while ($arProp = $obProps->fetch()) {
            $tmp = [
                'NAME' => $arProp['VALUE'],
                'XML' => $arProp['XML_ID'],
                'ID' => $arProp['ID']
            ];

            if (!empty($this->info['DATE_TYPE_CHECK']) && $this->info['DATE_TYPE_CHECK'] == $tmp['ID'])
                $tmp['ACTIVE'] = true;

            $arProps[] = $tmp;
        }

        $this->addDataToInfo('DATE_TYPE', $arProps);
    }

    /**
     * Получить ID свойство события 'Каждый день'
     * @return mixed
     */
    protected function getEveryDayType()
    {
        $this->getDateTypeList();

        if (!empty($this->info['DATE_TYPE']))
            foreach ($this->info['DATE_TYPE'] as $arType)
                if ($arType['XML'] == 'EVERY')
                    return $arType['ID'];
    }

    /**
     * Получить ID свойство события 'Будние дни'
     * @return mixed
     */
    protected function getWeekDayType()
    {
        $this->getDateTypeList();

        if (!empty($this->info['DATE_TYPE']))
            foreach ($this->info['DATE_TYPE'] as $arType)
                if ($arType['XML'] == 'WEEKDAY')
                    return $arType['ID'];
    }

    /**
     * Получить список значений свойства "Тип"
     */
    protected function getTypeList()
    {
        $obProps = \CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => self::getIblockId(),
                'CODE' => 'TYPE'
            ]
        );
        while ($arProp = $obProps->fetch()) {
            $tmp = [
                'NAME' => $arProp['VALUE'],
                'XML' => $arProp['XML_ID'],
                'ID' => $arProp['ID']
            ];

            if (!empty($this->info['TYPE_CHECK']) && $this->info['TYPE_CHECK'] == $tmp['ID'])
                $tmp['ACTIVE'] = true;

            $arProps[] = $tmp;
        }

        $this->addDataToInfo('TYPE', $arProps);
    }

    public static function getTypeListStatic()
    {
        $obProps = \CIBlockPropertyEnum::GetList(
            [],
            [
                'IBLOCK_ID' => self::getIblockId(),
                'CODE' => 'TYPE'
            ]
        );
        while ($arProp = $obProps->fetch()) {
            $arProps[] = [
                'NAME' => $arProp['VALUE'],
                'XML' => $arProp['XML_ID'],
                'ID' => $arProp['ID']
            ];
        }

        return $arProps;
    }

    /**
     * Получить ID свойство одиночного события
     * @return mixed
     */
    protected function getSingleEventId()
    {
        $this->getTypeList();

        if (!empty($this->info['TYPE']))
            foreach ($this->info['TYPE'] as $arType)
                if ($arType['XML'] == 'SINGLE')
                    return $arType['ID'];
    }

    /**
     * Получить ID свойство длинного события
     * @return mixed
     */
    protected function getContinEventId()
    {
        $this->getTypeList();

        if (!empty($this->info['TYPE']))
            foreach ($this->info['TYPE'] as $arType)
                if ($arType['XML'] == 'CONTINUOUS')
                    return $arType['ID'];
    }

    public function getInitFields()
    {
        $this->getDirectionsList();
        $this->getPlaceHbData();
        $this->getTypeList();
        $this->getDateTypeList();

        if ($this->isCanSetImportant())
            $this->addDataToInfo('CAN_SET_IMPORTANT', 'Y');
    }

    abstract public function add($arFields);
    abstract public function del();
    abstract public function update($arFields);
    abstract public static function getListById(array $arId);
}
?>