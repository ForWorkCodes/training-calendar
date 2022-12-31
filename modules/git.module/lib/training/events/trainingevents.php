<?php
namespace Git\Module\Training\Events;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Modules\Events\General\Events;
use Git\Module\Modules\LockModule\General\TrainingLockTable;
use Git\Module\Modules\LockModule\Mediator\MediatorLockTable;
use Git\Module\Training\General\Training;

class TrainingEvents
{
    /**
     * Событие при создании тренировки
     * @param \Bitrix\Main\Event $event
     */
    public static function eventNewTraining(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();
        if (empty((int)$arParams['ID_TRAINING'])) return;

        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTraining = $obMediator->buildClass($arParams['ID_TRAINING']);

        if ($obTraining instanceof IForMediator) {
            if (empty($obTraining->getId())) return;

            self::addProcessCloseTraining($obTraining);
        }
    }

    /**
     * Добавить вызов функции закрытия записи на тренировку
     * @param IForMediator $obTraining
     * @throws \Bitrix\Main\ObjectException
     */
    public static function addProcessCloseTraining(IForMediator $obTraining)
    {
        if (empty($obTraining->getId())) return;
        $arTraining = $obTraining->getInfo();

        $objDateTime = new DateTime($arTraining['PROPERTIES']['DATETIME']['VALUE']);
        $objDateTime->add('60 minutes');

        $arFields = [
            'UF_TYPE' => 'FUNCTION',
            'UF_XML_ID' => 'CLOSE_TRAINING',
            'UF_ID_PARENT' => $obTraining->getId(),
            'UF_DATE_START' => $objDateTime->toString(),
            'UF_FUNCTION' => '\Git\Module\Training\Events\TrainingEvents::processCloseTraining',
            'UF_PARAMS' => [
                'ID_TRAINING' => $obTraining->getId()
            ],
        ];
        $obMediator = new Events();
        $obMediator->add($arFields);
    }

    /**
     * Функция закрытия записи на тренировку
     * @param $arParams
     */
    public static function processCloseTraining($arParams)
    {
        if (empty((int)$arParams['ID_TRAINING'])) return;

        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTraining = $obMediator->buildClass($arParams['ID_TRAINING']);

        $obTraining->closeTraining();
    }

    /**
     * Заранее добавить уведомление про опоздание по отправке отчета
     * @param IForMediator $obTraining
     */
    public static function newNoteByMissedReport(IForMediator $obTraining, $noteDate = '')
    {
        if (empty($obTraining->getId())) return;

        $obTrainer = $obTraining->getTrainer()[0];
        $obKurator = $obTraining->getKurator();
        $arTraining = $obTraining->getInfo();

        if ( ($obTrainer instanceof IForMediator) && ($obKurator instanceof IForMediator) ) {
            if (empty($obTrainer->getId()) || empty($obKurator->getId())) return;
            $site = \COption::GetOptionString("main", "server_name");
            $link = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $site . '/personal/training/#/list/'. $obTraining->getId();

            if ($noteDate == 'Y')
                $date = date('d.m.Y 23:59');
            else
                $date = Utils::getDateCustom($arTraining['PROPERTIES']['DATETIME']['VALUE'],"",'d.m.Y 23:59');

            $arReplace = [
                '#LINK#' => $link,
                '#NAME_TRAINING#' => $arTraining['NAME'],
                '#DATE#' => $arTraining['PROPERTIES']['DATETIME']['VALUE'],
            ];
            $arFields = [
                'UF_TYPE' => 'MESSAGE',
                'UF_XML_ID' => 'E0086',
                'UF_ID_PARENT' => $obTraining->getId(),
                'UF_DATE_START' => $date,
                'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                'UF_PARAMS' => [
                    'CODE' => 'E0086',
                    'USER_ID' => [$obKurator->getId(), $obTrainer->getId()],
                    'REPLACE_RULES' => $arReplace
                ],
            ];

            $obMediator = new Events();
            $obMediator->add($arFields);

            $objDateTime = new DateTime($date);
            $objDateTime->add('1 day');

            $arFields = [
                'UF_TYPE' => 'FUNCTION',
                'UF_XML_ID' => 'MISSED_REPORT',
                'UF_ID_PARENT' => $obTraining->getId(),
                'UF_DATE_START' => $objDateTime->toString(),
                'UF_FUNCTION' => '\Git\Module\Training\Events\TrainingEvents::processMissedReport',
                'UF_PARAMS' => [
                    'ID_TRAINING' => $obTraining->getId(),
                    'NOTE_DATE' => "Y",
                ],
            ];
            $obMediator = new Events();
            $obMediator->add($arFields);
        }
    }

    /**
     * Процесс для формирование уведомлений об пропуске подачи отчета
     * @param $arParams
     */
    public static function processMissedReport($arParams)
    {
        if (empty((int)$arParams['ID_TRAINING'])) return;

        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTraining = $obMediator->buildClass($arParams['ID_TRAINING']);

        self::newNoteByMissedReport($obTraining, $arParams['NOTE_DATE']);
    }

    /**
     * Событие при добавлении пользователя в тренировку тренером
     * @param \Bitrix\Main\Event $event
     */
    public static function eventTrainerAddMember(\Bitrix\Main\Event $event)
    {
        $arParam = $event->getParameters();
        $obTraining = $arParam['ENTITY'];
        $obUser = $arParam['USER'];

        if ( ($obTraining instanceof IForMediator) && ($obUser instanceof IForMediator) ) {
            if (empty($obTraining->getId()) || empty($obUser->getId())) return;

            $site = \COption::GetOptionString("main", "server_name");
            $link = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $site . '/personal/training/#/list/'. $obTraining->getId();
            $arReplace = [
                '#LINK#' => $link
            ];
            $arFields = [
                'UF_TYPE' => 'MESSAGE',
                'UF_XML_ID' => 'E0082',
                'UF_ID_PARENT' => '',
                'UF_TIMER' => '',
                'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                'UF_PARAMS' => [
                    'CODE' => 'E0082',
                    'USER_ID' => [$obUser->getId()],
                    'REPLACE_RULES' => $arReplace
                ],
            ];
            $obMediator = new Events();
            $obMediator->add($arFields);
        }
    }

    /**
     * Событие при отправке отчета по тренировке
     * @param \Bitrix\Main\Event $event
     */
    public static function eventReportSend(\Bitrix\Main\Event $event)
    {
        $arParam = $event->getParameters();
        $obTraining = $arParam['ENTITY'];
        self::addProcessToFinishTraining($obTraining);
        self::delNoteByMissedReport($obTraining);
    }

    /**
     * Событие при полном закрытии тренировки в 00:00
     * @param \Bitrix\Main\Event $event
     */
    public static function finishTraining(\Bitrix\Main\Event $event)
    {
        $arParam = $event->getParameters();
        $obTraining = $arParam['ENTITY'];
        self::updateUserDataInLockTable($obTraining);
    }

    /**
     * Добавить таймер на полное закрытие тренировки
     * @param IForMediator $obTraining
     */
    public static function addProcessToFinishTraining(IForMediator $obTraining)
    {
        if (empty($obTraining->getId())) return;

        $date = Utils::getDateCustom(date('d.m.Y'),"",'d.m.Y 23:59');

        $arFields = [
            'UF_TYPE' => 'FUNCTION',
            'UF_XML_ID' => 'FINISH_TRAINING',
            'UF_ID_PARENT' => $obTraining->getId(),
            'UF_DATE_START' => $date,
            'UF_TIMER' => '',
            'UF_FUNCTION' => '\Git\Module\Training\Events\TrainingEvents::processToFinishTraining',
            'UF_PARAMS' => [
                'ID_TRAINING' => $obTraining->getId()
            ],
        ];
        $obMediator = new Events();
        $obMediator->add($arFields);
    }

    /**
     * Полное закрытие тренировки (после отправки отчета)
     * @param $arParams
     */
    public static function processToFinishTraining($arParams)
    {
        if (empty((int)$arParams['ID_TRAINING'])) return;

        $rsMediator = new FactoryMediator('Training');
        $obMediator = $rsMediator->getClass();

        $obTraining = $obMediator->buildClass($arParams['ID_TRAINING']);
        $obTraining->finishTraining();
    }

    /**
     * Убрать уведомление об опоздании отправки отчета
     * @param IForMediator $obTraining
     */
    public static function delNoteByMissedReport(IForMediator $obTraining)
    {
        if (empty($obTraining->getId())) return;

        $arFilter = [
            'UF_ACTIVE' => true,
            'UF_TYPE' => 'FUNCTION',
            'UF_XML_ID' => 'MISSED_REPORT',
            'UF_ID_PARENT' => $obTraining->getId()
        ];

        $obMediator = new Events();
        $arList = $obMediator->get($arFilter);

        if (!empty($arList))
            foreach ($arList as $arData)
                $obMediator->update($arData['ID'], ['UF_ACTIVE' => false]);

        $arFilter = [
            'UF_ACTIVE' => true,
            'UF_TYPE' => 'MESSAGE',
            'UF_XML_ID' => 'E0086',
            'UF_ID_PARENT' => $obTraining->getId()
        ];
        $arList = $obMediator->get($arFilter);

        if (!empty($arList))
            foreach ($arList as $arData)
                $obMediator->update($arData['ID'], ['UF_ACTIVE' => false]);
    }

    /**
     * Проверить присутствие участником на тренировке, если их не было - отметить это в таблице блокировок
     * @param IForMediator $obTraining
     */
    public static function updateUserDataInLockTable(IForMediator $obTraining)
    {
        $lockModuleActive = (bool)\COption::GetOptionString('git.module', 'TRAINING_BLOCKED_ACTIVE', false);
        if (!$lockModuleActive) return;

        $obTraining->getMembers();
        $arTraining = $obTraining->getInfo();

        if (!empty($arTraining['MEMBERS'])) {

            $obTrainingLock = new TrainingLockTable();

            foreach ($arTraining['MEMBERS'] as $obMember) {
                if ( !($obMember instanceof IForMediator) ) continue;
                if (empty($obMember->getId())) continue;


                $arMember = $obMember->getInfo();
                $arMember['STATUS'] = $obMember->getStatus();

                if ($arMember['STATUS'] == 'Y') {
                    $obTrainingLock->delLock($obTraining, $obMember);
                } else {
                    $obTrainingLock->addLock($obTraining, $obMember);
                }

                $site = \COption::GetOptionString("main", "server_name");
                $link = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $site . '/personal/training/#/list/'. $obTraining->getId();

                // Получить текущее количество пропусков и разослать уведомления
                $arLockList = $obTrainingLock->getUserLockByParent($obTraining, $obMember);
                if (count($arLockList) > 0) {

                    $max = (int)\COption::GetOptionString('git.module', 'TRAINING_MAX_BLOCKED', '3');
                    $left = $max - count($arLockList);

                    if ($left > 0) {
                        $arReplace = [
                            '#COUNT#' => $left,
                            '#LINK#' => $link
                        ];
                        $arFields = [
                            'UF_TYPE' => 'MESSAGE',
                            'UF_XML_ID' => 'E0083',
                            'UF_TIMER' => '',
                            'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                            'UF_PARAMS' => [
                                'CODE' => 'E0083',
                                'USER_ID' => [$obMember->getId()],
                                'REPLACE_RULES' => $arReplace
                            ],
                        ];
                    } else {
                        $arReplace = [
                            '#COUNT#' => count($arLockList)
                        ];
                        $arFields = [
                            'UF_TYPE' => 'MESSAGE',
                            'UF_XML_ID' => 'E0084',
                            'UF_TIMER' => '',
                            'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                            'UF_PARAMS' => [
                                'CODE' => 'E0084',
                                'USER_ID' => [$obMember->getId()],
                                'REPLACE_RULES' => $arReplace
                            ],
                        ];
                    }

                    $obMediator = new Events();
                    $obMediator->add($arFields);
                }

            }
        }
    }

    /**
     * Событие при закрытии записи на тренировку
     * @param \Bitrix\Main\Event $event
     */
    public static function eventCloseTraining(\Bitrix\Main\Event $event)
    {
        $arParam = $event->getParameters();
        $obTraining = $arParam['ENTITY'];
        $obTrainer = $obTraining->getTrainer()[0];

        if ($obTrainer instanceof IForMediator) {
            if (empty($obTrainer->getId())) return;

            $site = \COption::GetOptionString("main", "server_name");
            $link = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $site . '/personal/training/#/list/'. $obTraining->getId();

            $arFields = [
                'UF_TYPE' => 'MESSAGE',
                'UF_XML_ID' => 'E0085',
                'UF_ID_PARENT' => '',
                'UF_TIMER' => '',
                'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                'UF_PARAMS' => [
                    'CODE' => 'E0085',
                    'USER_ID' => [$obTrainer->getId()],
                    'REPLACE_RULES' => ['#LINK#' => $link]
                ],
            ];
            $obMediator = new Events();
            $obMediator->add($arFields);
        }
    }

    /**
     * Событие изменения описания тренировки
     * @param \Bitrix\Main\Event $event
     */
    public function eventDescriptionEdit(\Bitrix\Main\Event $event)
    {
        $arParam = $event->getParameters();
        $obTraining = $arParam['ENTITY'];

        if ($obTraining->isSubmitNote()) {
            $obMembers = $obTraining->getMembers();
            $arTraining = $obTraining->getInfo();

            $site = \COption::GetOptionString("main", "server_name");
            $link = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $site . '/personal/training/#/list/'. $obTraining->getId();

            if (!empty($obMembers)) {
                foreach ($obMembers as $obMember) {
                    if ( !($obMember instanceof IForMediator) ) continue;
                    if (empty($obMember->getId())) continue;

                    $users[] = $obMember->getId();
                }

                $arReplace = [
                    '#MESSAGE#' => $arTraining['NAME'],
                    '#LINK#' => $link
                ];
                $arReplace['#MESSAGE#'] = $arTraining['NAME'];

                $arFields = [
                    'UF_TYPE' => 'MESSAGE',
                    'UF_XML_ID' => 'E0089',
                    'UF_ID_PARENT' => '',
                    'UF_TIMER' => '',
                    'UF_FUNCTION' => 'Git\Module\Modules\Events\General\Router::messageChat',
                    'UF_PARAMS' => [
                        'CODE' => 'E0089',
                        'USER_ID' => $users,
                        'REPLACE_RULES' => $arReplace
                    ],
                ];
                $obMediator = new Events();
                $obMediator->add($arFields);
            }
        }
    }
}
?>