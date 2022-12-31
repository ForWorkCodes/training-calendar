<?php
namespace Git\Module\Controllers;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main;
use Git\Module\Builder\General\CalendarBuilder;

class Calendar extends Controller
{
    public function configureActions() {
        return [
            'initCalendar' => ['prefilters' => [], 'postfilters' => []],
            'getCalendar' => ['prefilters' => [], 'postfilters' => []],
            'initPageCreateCalendar' => ['prefilters' => [], 'postfilters' => []],
            'addCalendar' => ['prefilters' => [], 'postfilters' => []],
            'updateCalendar' => ['prefilters' => [], 'postfilters' => []],
            'delCalendar' => ['prefilters' => [], 'postfilters' => []],
            'downloadCalendar' => ['prefilters' => [], 'postfilters' => []]
        ];
    }

    /**
     * Инициализация данных для фильтров и др
     * @return array
     */
    public function initCalendarAction()
    {
        try {
            $start = microtime(true);

            $obCalendar = CalendarBuilder::getCalendarInit();

            if (empty($obCalendar->getErrors())) {
                $return = [
                    'status' => 'success',
                    'result' => $obCalendar->getInfo(),
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obCalendar->getErrors(),
                ];
            }
            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Получить события календаря
     * @param array $arFilter
     * @return array
     */
    public function getCalendarAction($arFilter)
    {
        try {
            $start = microtime(true);

            $obCalendar = CalendarBuilder::getCalendarList($arFilter);

            if (empty($obCalendar->getErrors())) {
                $return = [
                    'status' => 'success',
                    'result' => $obCalendar->getInfo(),
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obCalendar->getErrors(),
                ];
            }
            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        } catch (Main\SystemException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getTraceAsString()
            ];
        }

        return $return;
    }

    /**
     * Получение массива для страницы создания/редактирования события
     * @param null $idCalendar
     * @return array
     */
    public function initPageCreateCalendarAction($idCalendar = null)
    {
        try {
            $start = microtime(true);

            $obCalendar = CalendarBuilder::getPageCalendarInit($idCalendar);

            if (empty($obCalendar->getErrors())) {
                $return = [
                    'status' => 'success',
                    'result' => $obCalendar->getInfo(),
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obCalendar->getErrors(),
                ];
            }
            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        } catch (Main\SystemException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getTraceAsString()
            ];
        }

        return $return;
    }

    /**
     * Добавить событие календаря
     * @param FormData
     * @return array
     */
    public function addCalendarAction()
    {
        try {
            $start = microtime(true);

            $arFields = array_merge($_REQUEST, $_FILES);

            $obCalendar = CalendarBuilder::addInCalendar($arFields);

            if ($obCalendar->idNewCalendar != false) {
                $return = [
                    'status' => 'success',
                    'result' => $obCalendar->idNewCalendar
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obCalendar->getErrors()
                ];
            }

            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => [['message' => $e->getMessage()]]
            ];
        }

        return $return;
    }

    /**
     * Изменить событие календаря
     * @param FormData
     * @return array
     */
    public function updateCalendarAction()
    {
        try {
            $start = microtime(true);

            $arFields = array_merge($_REQUEST, $_FILES);

            $obCalendar = CalendarBuilder::updateCalendar($arFields);

            if ($obCalendar->endUpdate == true) {
                $return = [
                    'status' => 'success',
                    'result' => 'Y'
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obCalendar->getErrors()
                ];
            }

            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
        } catch (\Exception $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Удалить событие календаря
     * @param $id
     */
    public function delCalendarAction($id)
    {
        try {
            $start = microtime(true);

            $obCalendar = CalendarBuilder::delFromCalendar($id);

            if ($obCalendar->isDel == true) {
                $return = [
                    'status' => 'success',
                    'result' => 'Y'
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obCalendar->getErrors()
                ];
            }

            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Скачать событие календаря
     * @param $id
     */
    public function downloadCalendarAction($id)
    {
        try {
            $start = microtime(true);

            $obIcs = CalendarBuilder::downloadIcs($id);

            if (empty($obIcs->getErrors())) {
                $return = [
                    'status' => 'success',
                    'result' => $obIcs->getText()
                ];
            } else {
                $return = [
                    'status' => 'error',
                    'errors' => $obIcs->getErrors()
                ];
            }

            $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }
}
?>