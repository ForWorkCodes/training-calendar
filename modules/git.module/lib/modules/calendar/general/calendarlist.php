<?php
namespace Git\Module\Modules\Calendar\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use google\protobuf\FieldDescriptorProto\Type;
use Git\Module\Helpers\Utils;
use Git\Module\Highloadblock\HlbForWs;
use Git\Module\Highloadblock\HLBWrap;
use Git\Module\Modules\Calendar\Abstracts\ACalendar;
use Git\Module\Modules\Calendar\Factory\FactoryCalendar;

/**
 * Класс для формирования списка событий и карточки календаря
 * Class CalendarList
 * @package Git\Module\Modules\Calendar\General
 */
class CalendarList extends ACalendar
{
    /**
     * Как сортировать события
     * @var
     */
    protected $sortItems;

    /**
     * Фильтровать карточки без учета даты проведения
     * @var
     */
    protected $not_need_date_cards;

    /**
     * Фильтр который пришел с фронта
     * @var
     */
    protected $userFilter;

    protected $innerData;
    protected $query;

    /**
     * Инициализация данных для передачи на фронт
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getInitFields()
    {
        $this->getPlaceHbData();
        $this->getDirectionsList();
//        $this->getDateTypeList();
//        $this->getTypeList();
        if ($this->isCanGetDraft()) {
            $this->getTypeDraft();
            $this->addDataToInfo('SHOW_CREATE_BTN', 'Y');
        }
        $this->getTimeList();
        $this->getDayList();

    }

    /**
     * Установка объекта Query
     * @throws \Bitrix\Main\LoaderException
     */
    protected function setQuery()
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        $element = \Bitrix\Iblock\Elements\ElementCalendarTable::getEntity();
        $query = new \Bitrix\Main\Entity\Query($element);
        $this->query = $query;
    }

    /**
     * Получение событий по фильтру для фронта
     * @param $arFilter
     * @throws \Bitrix\Main\ObjectException
     */
    public function get($arFilter)
    {
        $this->setQuery();
        $this->userFilter = $arFilter;
        $this->createFilter($arFilter);

        if (!empty($this->getErrors()))
            return;

        $this->getItems();

        $this->formatCustomProps();

        if (empty($arFilter['DATE']))
            $this->buildEventsInCalendar();

        $this->addDataToInfo('SQL', $this->query->getQuery());
        $this->buildCardsInCalendar(); // ITEMS изменяем
    }

    /**
     * Преобразование входного фильтра в битрикс формат. Работает для получение карточек событий
     */
    protected function createFilterByCards()
    {
        if (!empty($this->userFilter['DATE'])) {

            try {
                $this->getFilterByOneDay($this->userFilter['DATE']);
            } catch (\Bitrix\Main\ObjectException $e) {
                $this->addError($e->getMessage());
            }

        } else {

            $this->checkNeedToGetCardsByProps($this->userFilter);

            if (true) { // !$this->not_need_date_cards
                $obCurDate = new DateTime();
                $curDate = $obCurDate->format('Y-m-d H:i:s');

                if (empty($this->userFilter['ACTUAL']) || $this->userFilter['ACTUAL'] == 'Y') { // Только грядущие
                    $this->sortItems['DATE'] = 'ASC';
                    if ($this->not_need_date_cards)
                        $obToDate = new \DateTime('700 days');
                    else
                        $obToDate = new \DateTime('35 days');
                    $toDate = $obToDate->format('Y-m-d H:i:s');
                    $this->userFilter['NEED_PERIOD_DATE'] = $toDate;

                    $this->query->where(
                        \Bitrix\Main\Entity\Query::filter()->logic('or')
                            ->where([
                                ["DATE_TIME_TO.VALUE", '>', $curDate],
                                ["DATE_TIME_TO.VALUE", $obCurDate->format('Y-m-d') . ' 00:00:00'],
                            ])
                    );
                    $this->query->where("DATE_TIME_FROM.VALUE", '<', $toDate);

                } elseif ($this->userFilter['ACTUAL'] == 'N') { // Только прошедшие
                    $this->sortItems['DATE'] = 'DESC';

                    if ($this->not_need_date_cards)
                        $obBeforeDate = new \DateTime('-700 days');
                    else
                        $obBeforeDate = new \DateTime('-35 days');

                    $beforeDate = $obBeforeDate->format('Y-m-d H:i:s');
                    $this->userFilter['NEED_PERIOD_DATE'] = $beforeDate;

                    $this->query->where("DATE_TIME_TO.VALUE", '<', $curDate);
                    $this->query->where("DATE_TIME_TO.VALUE", '!=', $obCurDate->format('Y-m-d') . ' 00:00:00');
                    $this->query->where("DATE_TIME_TO.VALUE", '>', $beforeDate);
                }
            }

        }

        $this->formatFilter($this->userFilter);
        $this->addDefaultFilter();
    }

    /**
     * Преобразование входного фильтра в битрикс формат
     * @param $arFilter
     * @throws \Bitrix\Main\ObjectException
     */
    protected function createFilter($arFilter)
    {
        if (!empty($arFilter['DATE'])) {

            try {
                $this->getFilterByOneDay($arFilter['DATE']);
            } catch (\Bitrix\Main\ObjectException $e) {
                $this->addError($e->getMessage());
            }

        } elseif (!empty($arFilter['DATE_FROM']) && !empty($arFilter['DATE_TO'])) {

            try {
                $date_from = new DateTime($arFilter['DATE_FROM']);
                $date_from_format = $date_from->format('Y-m-d');
                $date_to = new DateTime($arFilter['DATE_TO']);
                $date_to_format = $date_to->format('Y-m-d');

                $this->query->where("DATE_TIME_TO.VALUE", '>=', $date_from_format . ' 00:00:00');
                $this->query->where("DATE_TIME_FROM.VALUE", '<=', $date_to_format . ' 23:59:59');

                // Построить список дней (вынести в отдельный метод)
                $from = new \DateTime($date_from_format);
                $to = new \DateTime($date_to_format);
                $to->add(new \DateInterval('P1D')); // Период не включает в себя последний день, поэтому добавляем еще 1

                $period = new \DatePeriod($from, new \DateInterval('P1D'), $to);

                $arrayOfDates = array_map(
                    function ($item) {
                        return $item->format('Y.m.d');
                    },
                    iterator_to_array($period)
                );
                $this->innerData['PERIOD'] = $arrayOfDates;

            } catch (\Bitrix\Main\ObjectException $e) {
                $this->addError($e->getMessage());
            }

        } else {
            $this->addError('Need DATE or DATE_FROM with DATE_TO');
            return;
        }

        $this->formatFilter($arFilter);
        $this->addDefaultFilter();
    }

    /**
     * Добавить стандартные поля для фильтрации
     */
    protected function addDefaultFilter()
    {
        $this->query->setSelect(['ID']);

        if (!$this->isCanGetDraft())
            $this->query->addFilter('ACTIVE', 'Y');

    }

    /**
     * Добавить в фильтр выборку только одного дня
     * @param $date
     * @throws \Bitrix\Main\ObjectException
     */
    protected function getFilterByOneDay($date)
    {
        $date = new DateTime($date);
        $date_from_format = $date->format('Y-m-d');
        $date_to_format = $date->format('Y-m-d');

        $singleId = $this->getSingleEventId();
        $contId = $this->getContinEventId();
        $weekId = $this->getWeekDayType();

        $logicSingle = [
            \Bitrix\Main\Entity\Query::filter()
                ->logic('and')
                ->where([
                    ['TYPE.VALUE', $singleId],
                    ["DATE_TIME_FROM.VALUE", '>=', $date_from_format . " 00:00:00"],
                    ["DATE_TIME_TO.VALUE", '<=', $date_to_format . " 23:59:59"]
                ])
        ];

        if ((date('N', strtotime($date_from_format)) >= 6)) { // Этот день выходной
            $logicCont = [
                \Bitrix\Main\Entity\Query::filter()
                    ->logic('and')
                    ->where([
                        ['TYPE.VALUE', $contId],
                        ['DATE_TYPE.VALUE', '!=', $weekId],
                        ["DATE_TIME_FROM.VALUE", '<=', $date_from_format . " 00:00:00"],
                        ["DATE_TIME_TO.VALUE", '>=', $date_from_format . " 00:00:00"],
                    ])
            ];
        } else {
            $logicCont = [
                \Bitrix\Main\Entity\Query::filter()
                    ->logic('and')
                    ->where([
                        ['TYPE.VALUE', $contId],
                        ["DATE_TIME_FROM.VALUE", '<=', $date_from_format . " 00:00:00"],
                        ["DATE_TIME_TO.VALUE", '>=', $date_from_format . " 00:00:00"],
                    ])
            ];
        }

        $this->query->where(
            \Bitrix\Main\Entity\Query::filter()
                ->logic('or')->where([$logicSingle, $logicCont])
        );

        $this->innerData['CURRENT_DATE'] = $date->format('Y.m.d');
    }

    /**
     * Получение элементов ИБ
     */
    protected function getItems()
    {
        $this->query->setOrder(['DATE_TIME_FROM.VALUE' => (!empty($this->sortItems['DATE']) ? $this->sortItems['DATE'] : 'ASC')]);
        $arEls = $this->query->exec()->fetchAll();
        if (!empty($arEls)) {
            foreach ($arEls as $arEl) {
                if (!empty($arEl['ID'])) {
                    $obFactory = new FactoryCalendar((int)$arEl['ID']);
                    $obEl = $obFactory->getClass();
                    $obElems[] = $obEl;
                }
            }
        }

        $this->innerData['ITEMS'] = $obElems;
    }

    protected function formatFilter($arFilter)
    {
        foreach ($arFilter as $field => $value) {
            switch ($field) {
                case "DAYS":
                    if (is_array($value)) {
                        unset($logicDay);

                        foreach ($value as $day)
                            $logicDay[] = [new \Bitrix\Main\Entity\ExpressionField('DAYOFWEEK', 'DAYOFWEEK(%s)', 'DATE_TIME_FROM.VALUE'), $day];

                        $singleId = $this->getSingleEventId();
                        $contId = $this->getContinEventId();
                        $weekId = $this->getWeekDayType();

                        if (!empty($singleId))
                            $logicSingle = [
                                \Bitrix\Main\Entity\Query::filter()
                                    ->logic('and')
                                    ->where([
                                        ["TYPE.VALUE", $singleId],
                                        [
                                            \Bitrix\Main\Entity\Query::filter()
                                                ->logic('or')
                                                ->where($logicDay)
                                        ]
                                    ])
                            ];

                        if (in_array(2, $value)
                            || in_array(3, $value)
                            || in_array(4, $value)
                            || in_array(5, $value)
                            || in_array(6, $value)) {

                            if (!empty($contId))
                                $logicCont = [
                                    \Bitrix\Main\Entity\Query::filter()
                                        ->logic('and')
                                        ->where([
                                            ["TYPE.VALUE", $contId]
                                        ])
                                ];
                        } else {
                            if (!empty($contId) && !empty($weekId))
                                $logicCont = [
                                    \Bitrix\Main\Entity\Query::filter()
                                        ->logic('and')
                                        ->where([
                                            ["TYPE.VALUE", $contId],
                                            ['DATE_TYPE.VALUE', '!=', $weekId]
                                        ])
                                ];
                        }

                        if (!empty($logicSingle) || !empty($logicCont))
                            $this->query->where(\Bitrix\Main\Entity\Query::filter()->logic('or')
                                ->where([$logicSingle, $logicCont])
                            );
                    }
                    break;
                case "TIME":
                    if (is_array($value)) {
                        unset($logicTime);
                        foreach ($value as $time) {

                            switch ($time) {
                                case "MORNING":
                                    $logicTime[] = [new \Bitrix\Main\Entity\ExpressionField('HOUR', 'HOUR(%s)', 'DATE_TIME_FROM.VALUE'), '<', 12];
                                    break;
                                case "LUNCH":
                                    $logicTime[] = [
                                        \Bitrix\Main\Entity\Query::filter()
                                            ->logic('and')
                                            ->where([
                                                [new \Bitrix\Main\Entity\ExpressionField('HOUR', 'HOUR(%s)', 'DATE_TIME_FROM.VALUE'), '>=', 12],
                                                [new \Bitrix\Main\Entity\ExpressionField('HOUR', 'HOUR(%s)', 'DATE_TIME_FROM.VALUE'), '<', 18]
                                            ])
                                    ];
                                    break;
                                case "DINNER":
                                    $logicTime[] = [new \Bitrix\Main\Entity\ExpressionField('HOUR', 'HOUR(%s)', 'DATE_TIME_FROM.VALUE'), '>=', 18];
                                    break;
                            }

                        }
                        $logicTime[] = [new \Bitrix\Main\Entity\ExpressionField('HOUR', 'HOUR(%s)', 'DATE_TIME_FROM.VALUE'), '=', 0];

                        $this->query->where(\Bitrix\Main\Entity\Query::filter()->logic('or')->where($logicTime));
                    }
                    break;
                case "SEARCH":
                    if ($value != '' && !empty($value)) {
                        $this->query->addFilter('?SEARCHABLE_CONTENT', $value);
                    }
                    break;
                case "ACTIVE":
                    $this->query->addFilter($field, $value);
                    break;
                case "DIRECTIONS":
                case "PLACE":
                    $this->query->addFilter($field . '.VALUE', $value);
                    break;
                case "IMPORTANT":
                    if ($value == 'Y')
                        $this->query->addFilter($field . '.VALUE', $value);
                    break;
            }
        }
    }

    /**
     * Формирование массива событий с привязкой к дням
     */
    protected function buildEventsInCalendar()
    {
        $arCalendar = [];

        if (empty($this->innerData['PERIOD'])) {
            $this->addError("Can't find period");
            return;
        }

        if (!empty($this->innerData['ITEMS'])) {
            foreach ($this->innerData['ITEMS'] as $obItem) {
                $arItem = $obItem->getInfo();

                foreach ($this->innerData['PERIOD'] as $date) {
                    if (is_array($arItem['DATE'])) { // Продолжительное событие

                        foreach ($arItem['DATE'] as $key => $tmpDate) {
                            if ($date == $tmpDate) { // В переменной две даты, начало и конец. Занести в переменную значение для фронта
                                if ($key == 0)
                                    $arItem['DATE_STATUS'] = 'START';
                                else
                                    $arItem['DATE_STATUS'] = 'END';

                                $arCalendar[$date][] = $arItem;
                            }
                        }

                    } else { // Одиночное событие
                        if ($date == $arItem['DATE']) {
                            $arItem['DATE_STATUS'] = 'END';
                            $arCalendar[$date][] = $arItem;
                        }
                    }
                }
            }
        }

        $this->sortCalendarEvents($arCalendar);
        $this->addDataToInfo('CALENDAR', $arCalendar);
    }

    protected function sortCalendarEvents(&$arCalendar)
    {
        foreach ($arCalendar as $key => $arDate) {
            foreach ($arDate as $key2 => $arItem) {

                if (is_array($arItem['DATE'])) {

                    $arCalendar[$key][$key2]['SORT'] = (int)$arCalendar[$key][$key2]['SORT'] + (100 - (int)$key2);
                    if ($arItem['IMPORTANT'] == 'Y') {
                        $arCalendar[$key][$key2]['SORT'] = (int)$arCalendar[$key][$key2]['SORT'] + 150;
                    }

                } else {

                    $arCalendar[$key][$key2]['SORT'] = (int)$arCalendar[$key][$key2]['SORT'] + (40 - (int)$key2);
                    if ($arItem['IMPORTANT'] == 'Y') {
                        $arCalendar[$key][$key2]['SORT'] = (int)$arCalendar[$key][$key2]['SORT'] + 50;
                    }

                }

            }

            usort($arCalendar[$key], function ($item1, $item2) {
                return $item1['SORT'] < $item2['SORT'];
            });

        }
    }

    /**
     * Формирование массива карточек в зависимости от фильтра
     */
    protected function buildCardsInCalendar()
    {
        $arItems = [];
        if (empty($this->innerData['CURRENT_DATE']) && empty($this->innerData['PERIOD'])) {
            $this->addError("Can't find date");
            return;
        }

        $this->getCardsItems();

        if (!empty($this->innerData['ITEMS'])) {
            foreach ($this->innerData['ITEMS'] as $obItem) {
                $arItem = $obItem->getInfo();

                if (is_array($arItem['DATE'])) { // Продолжительное событие

                    foreach ($arItem['DATE'] as &$tmpDate) {
                        $forFormat = new DateTime($tmpDate, 'Y.m.d');
                        $tmpDate = $forFormat->format('d.m.y');
                    }

                } else { // Одиночное событие
                    $forFormat = new DateTime($arItem['DATE'], 'Y.m.d');
                    $arItem['DATE'] = $forFormat->format('d.m.y');
                }

                $arItems[] = $arItem;
            }
        }

        $this->sortCalendarCards($arItems);
        $this->addDataToInfo('CARDS', $arItems);
    }

    /**
     * Добавление сортировки для карточек событий
     * @param $arItems
     */
    protected function sortCalendarCards(&$arItems)
    {
        foreach ($arItems as $key => $arItem) {

            $arItems[$key]['TYPE_SORT'] = (!empty($this->sortItems['DATE']) ? $this->sortItems['DATE'] : 'ASC');

            if (!empty($this->userFilter['DATE'])) { // Выбран только один день

                $date = new DateTime($this->userFilter['DATE']);
                $date_format = $date->format('d.m.y');

                if (is_array($arItem['DATE'])) { // Продолжительное события

                    if ($arItem['IMPORTANT'] == 'Y') {
                        if (in_array($date_format, $arItem['DATE']))
                            $arItems[$key]['SORT'] = 700; // Потом карточки важных продолжительных событий в дни их начала/завершения
                        else
                            $arItems[$key]['SORT'] = 400; // затем карточки важных продолжительных событий в промежуточные дни
                    } else {
                        if (in_array($date_format, $arItem['DATE']))
                            $arItems[$key]['SORT'] = 500; // затем карточки продолжительных событий не являющихся важными в дни их начала/завершения
                        else
                            $arItems[$key]['SORT'] = 300; // затем карточки не важных продолжительных событий в промежуточные дни
                    }

                } else { // Одиночное событие
                    if ($arItem['IMPORTANT'] == 'Y')
                        $arItems[$key]['SORT'] = 800; // Сначала карточки одиночных важных событий
                    else
                        $arItems[$key]['SORT'] = 600; // затем карточки одиночных не важных событий
                }

            } else { // Выбран промежуток

                if (is_array($arItem['DATE'])) { // Продолжительное события

                    if ($arItem['IMPORTANT'] == 'Y')
                        $arItems[$key]['SORT'] = 700; // Потом карточки важных продолжительных событий
                    else
                        $arItems[$key]['SORT'] = 500; // затем карточки продолжительных событий не являющихся важными

                } else { // Одиночное событие
                    if ($arItem['IMPORTANT'] == 'Y')
                        $arItems[$key]['SORT'] = 800; // Сначала карточки одиночных важных событий
                    else
                        $arItems[$key]['SORT'] = 600; // затем карточки одиночных не важных событий
                }

            }

            if (empty($arResult['TIME_FROM']) && empty($arResult['TIME_TO']))
                $arItems[$key]['SORT'] -= 50;
        }

        if (!empty($this->userFilter['DATE'])) {
            usort($arItems, function ($item1, $item2) {
                return $item1['SORT'] < $item2['SORT'];
            });
        } else {
            usort($arItems, [$this, 'usort_object_by_time_ms']);
        }
    }

    public static function usort_object_by_time_ms($a, $b){
        // поля по которым сортировать
        $array = array( 'DATE_START'=>$a['TYPE_SORT'], 'SORT'=>'DESC' );

        $res = 0;
        foreach( $array as $k=>$v ){
            if( $a[$k] == $b[$k] ) continue;

            $res = ( $a[$k] < $b[$k] ) ? -1 : 1;
            if( $v=='DESC' ) $res= -$res;
            break;
        }

        return $res;
    }

    /**
     * Получить список времени события (для фильтра)
     */
    protected function getTimeList()
    {
        $arTime = [
            [
                'NAME' => GetMessage('TIME_MORNING'),
                'ID' => 'MORNING'
            ],
            [
                'NAME' => GetMessage('TIME_LUNCH'),
                'ID' => 'LUNCH'
            ],
            [
                'NAME' => GetMessage('TIME_DINNER'),
                'ID' => 'DINNER'
            ]
        ];

        $this->addDataToInfo('TIMES', $arTime);
    }

    /**
     * Массив для фильтра, опубликованные или черновики
     */
    protected function getTypeDraft()
    {
        $arTime = [
            [
                'NAME' => GetMessage('DRAFT_Y'),
                'ID' => 'Y'
            ],
            [
                'NAME' => GetMessage('DRAFT_N'),
                'ID' => 'N'
            ]
        ];

        $this->addDataToInfo('ACTIVE', $arTime);
    }

    /**
     * Сформировать список дней для фильтра()
     */
    protected function getDayList()
    {
        for ($i = 1; $i < 8; $i++) {
            $arDay[] = [
                'NAME' => Utils::getLangNameDayByNumToDB($i),
                'ID' => $i
            ];
        }
        $arDay[6] = array_shift($arDay);
        $this->addDataToInfo('DAY', $arDay);
    }

    /**
     * Форматируем свойства событий для фронта
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function formatCustomProps()
    {
        if (empty($this->innerData['ITEMS'])) {
//            $this->addError("Can't find events");
            return [];
        }

        $this->getDirectionsList();
        $arPlaces = $this->getPlaceHbData();

        foreach ($this->innerData['ITEMS'] as $obItem) {
            $arItem = $obItem->getInfo();

            if (!empty($arItem['PLACE'])) {
                unset($placeInfo);
                foreach ($arPlaces as $arPlace) {
                    if ($arPlace['ID'] == $arItem['PLACE'])
                        $placeInfo = $arPlace;
                }
                if (!empty($placeInfo))
                    $obItem->addDataToInfo('PLACE', $placeInfo);
            }

            if (!empty($arItem['DIRECTIONS']) && !empty($this->info['DIRECTIONS_CHILD'])) {
                unset($directionInfo);
                foreach ($this->info['DIRECTIONS_CHILD'] as $arDirect) {
                    if ($arDirect['ID'] == $arItem['DIRECTIONS']) {

                        if ($arItem['ACTIVE'] == 'N') {
                            $arDirect['COLOR_BG'] = '#989FB4';
                            $arDirect['COLOR_TEXT'] = '#fff';
                        }
                        else
                            $arDirect['COLOR_BG'] = $arDirect['COLOR'];

                        $directionInfo = $arDirect;
                    }
                }
                if (!empty($directionInfo))
                    $obItem->addDataToInfo('DIRECTIONS', $directionInfo);
            }
        }
    }

    /**
     * Проверить необходимость получения списка карточек событий без учета дат проведения, а только по фильтрам
     * @param $arFilter
     */
    protected function checkNeedToGetCardsByProps($arFilter)
    {
        $filter_isset = false;

        foreach ($arFilter as $key => $filter)
            switch ($key) {
                case "TIME":
                case "DAYS":
                case "PLACE":
                case "SEARCH":
                case "DIRECTIONS":
                    if (!empty($filter))
                        $filter_isset = true;
                    break;
            }

        $this->not_need_date_cards = $filter_isset;
    }

    /**
     * Получение карточек событий.
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getCardsItems()
    {
        $this->setQuery();
        $this->createFilterByCards();
        $this->getItems();
        $this->formatCustomProps();
    }

    public function del(){}
    public function update($arFields){}
    public static function getListById(array $arId){}
    public function add($arFields){}
    public static function getType($id){}
    protected function getDetailInfo(){}
}
?>