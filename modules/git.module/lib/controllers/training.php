<?php
namespace Git\Module\Controllers;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Request;
use Bitrix\Main;
use Bitrix\Main\Engine\ActionFilter;
use Git\Module\Builder\General\ReportBuilder;
use Git\Module\Builder\General\TrainingBuilder;
use Git\Module\Factory\General\FactoryMediator;

class Training extends Engine\Controller
{
    private $arReqFields = [
        'NAME' => 'NAME',
        'LAST_NAME' => 'LAST_NAME',
        'SECOND_NAME' => 'SECOND_NAME',
        'PERSONAL_BIRTHDAY' => 'PERSONAL_BIRTHDAY',
        'EMAIL' => 'EMAIL',
        'PERSONAL_PHONE' => 'PERSONAL_PHONE',
        'UF_DEPARTMENT' => 'UF_DEPARTMENT',
        'WORK_POSITION' => 'WORK_POSITION',
        'UF_TRAINING_CONF' => 'UF_TRAINING_CONF'
    ];

    public function __construct(Request $request = null)
    {
        parent::__construct($request);


        try {
            $this->checkModules();
            global $USER;


            if ($USER->IsAuthorized()) {
                $this->user = $USER;
                $this->uid = $USER->GetID();
            } else {
                throw new Main\SystemException('not Authorized');
            }

        } catch (\Exception $e) {
            $this->addError(new Error($e->getMessage()));
        }
    }

    public function configureActions()
    {
        return [
            'getTrainingPage' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'getTrainingsPage' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'addTrainingInRegular' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'setTrainer' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'setStatusMemberInTraining' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'editTraining' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'addMemberInTrainingByTrainer' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'addMeInTraining' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'delMemberInTrainingByTrainer' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'delMeInTrainingByTrainer' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'doEndTraining' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'getIsIBlock' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'getReportPage' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'getMembersTrainingByStr' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'getMembersToTrainingByStr' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'createXmlFromList' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'getKurator' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'createExcelFromList' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
            'addUnregisterMember' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
                'postfilters' => []
            ],
        ];
    }

    /**
     * @throws Main\LoaderException
     */
    protected function checkModules()
    {

        if (!Loader::includeModule('iblock')) {
            throw new Main\LoaderException('not install module iblock');
        }

        if (!Loader::includeModule('socialnetwork')) {
            throw new Main\LoaderException('not install module socialnetwork');
        }

    }

    /**
     * Задать тренера для тренировки. Пока только для админа т.к нет защиты
     * @param $id_rg_training
     * @param $id_trainer
     * @return array
     */
    public function setTrainerAction($id_rg_training, $id_trainer)
    {
        return $this->setTrainer($id_rg_training, $id_trainer);
    }

    /**
     * Задать тренера для тренировки. Пока только для админа т.к нет защиты
     * @param $id_rg_training
     * @param $id_trainer
     * @return array
     */
    private function setTrainer($id_rg_training, $id_trainer)
    {
        try {
            $start = microtime(true);
            $obMediator = new FactoryMediator('RegularTraining');
            $obRgMediator = $obMediator->getClass();
            $arResult = $obRgMediator->setTrainerFromId((int)$id_rg_training, (int)$id_trainer);
            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'status' => 'success',
                'result' => $arResult,
            ];
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Добавление тренировок в регулярные тренировки. Пока только для админа т.к нет защиты
     * @param $id_regular
     * @param $arTrainings
     * @return array
     */
    public function addTrainingInRegularAction($id_regular, $arTrainings)
    {
        if (!is_array($arTrainings))
            $arTrainings[] = $arTrainings;

        try {
            $start = microtime(true);
            $obMediator = new FactoryMediator('RegularTraining');
            $obRgMediator = $obMediator->getClass();
            $arResult = $obRgMediator->addTrainingInRegularById((int)$id_regular, $arTrainings);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
            ];
            if (empty($arResult['SUCCESS']))
            {
                $return['status'] = 'error';
            }
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Получение страницы тренировки, контроллер
     * @param $id_training
     * @return array
     */
    public function getTrainingPageAction($id_training)
    {
        return $this->getTrainingPage($id_training);
    }

    /**
     * Получение страницы тренировки
     * @param $id_training
     * @return array
     */
    public function getTrainingPage($id_training)
    {
        try {
            $start = microtime(true);
            $obTrainingBuilder = new TrainingBuilder();
            $obTrainingBuilder->needButtonType();

            $obTrainingBuilder->needMedia();

            $arTrainings = $obTrainingBuilder->getTrainingPage($id_training);
            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'status' => 'success',
                'result' => $arTrainings,
            ];
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Получение списка всех тренировок, страница со списком тренировок + фильтр, контроллер
     * @return array
     */
    public function getTrainingsPageAction(array $arFilter = [])
    {
        return $this->getTrainingsPage($arFilter);
    }

    /**
     * Получение списка всех тренировок, страница со списком тренировок + фильтр
     * Переименую на getDataForPageTrainings
     * @return array
     */
    private function getTrainingsPage(array $arFilter = [])
    {

        try {
            $start = microtime(true);
            $obTrainingBulder = new TrainingBuilder();
            $obTrainingBulder->needButtonType();
            $obTrainingBulder->needGetFilterData();

            if (!empty($arFilter))
                $obTrainingBulder->setFilter($arFilter);

            $arTrainings = $obTrainingBulder->getListTrainingsPage();
            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'status' => 'success',
                'result' => $arTrainings,
            ];
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Изменит статус участника тренировки, контроллер
     * @param $id_member
     * @param $id_training
     * @param $status
     * @return array
     */
    public function setStatusMemberInTrainingAction(array $arMembers, $id_training)
    {
        return $this->setStatusMemberInTraining($arMembers, $id_training);
    }

    /**
     * Изменит статус участника тренировки
     * @param $id_member
     * @param $id_training
     * @param $status
     * @return array
     */
    public function setStatusMemberInTraining(array $arMembers, $id_training)
    {
        try {
            $start = microtime(true);
            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();
            $arErrors = [];

            foreach ($arMembers as $arMember) {
                $arResult = $obMediator->changeStatusMember($arMember['ID'], $id_training, $arMember['STATUS']);

                if (!empty($arResult['SUCCESS']))
                    $success = true;

                if (!empty($arResult['ERRORS'])) {
                    $arErrors = array_merge($arErrors, $arResult['ERRORS']);
                }
            }

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arErrors,
            ];
            if (empty($success))
                $return['status'] = 'error';
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Редактирование полей тренировки, контроллер
     * @param $id_training
     * @param array $arFields
     * @return array
     */
    public function editTrainingAction($id_training, array $arFields)
    {
        return $this->editTraining($id_training, $arFields);
    }

    /**
     * Редактирование полей тренировки
     * @param $id_training
     * @param array $arFields
     * @return array
     */
    private function editTraining($id_training, array $arFields)
    {
        try {
            $start = microtime(true);
            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();

            $arResult = $obMediator->editTrainingFields($id_training, $arFields);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
            ];
            if (empty($arResult['SUCCESS']))
                $return['status'] = 'error';
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Добавить в тренировку пользователя который не зарегистрирован
     * @param $id_training
     * @param $arFields
     * @return array
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    public function addUnregisterMemberAction($id_training, $arFields)
    {
        $check = array_diff_key($this->arReqFields, $arFields);

        try {
            $start = microtime(true);

            if (empty($check) && ($arFields['UF_TRAINING_CONF'] == 'Y' || $arFields['UF_TRAINING_CONF'] == true || $arFields['UF_TRAINING_CONF'] == '1') ) {
                $rsMediator = new FactoryMediator('User');
                $obMediator = $rsMediator->getClass();

                $obResult = $obMediator->addUnregisterUser($arFields);

                $errors = $obResult->getErrors();
            } else {
                $errors[] = ['message' => 'need fields'];
            }

            if ($obResult->idNew == false) {
                $return['status'] = 'error';
                $return['errors'] = $errors;
            }
            else
            {
                $return = $this->addMemberInTrainingByTrainer($id_training, $obResult->idNew);
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        $return['time'] = 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';

        return $return;
    }

    /**
     * Добавление пользователя в тренировку, метод для тренера (нет проверки на поля)
     * @param $id_training
     * @param $id_member
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    public function addMemberInTrainingByTrainerAction($id_training, $id_member)
    {
        return $this->addMemberInTrainingByTrainer($id_training, $id_member);
    }

    /**
     * Добавление пользователя в тренировку, метод для тренера (нет проверки на поля)
     * @param $id_training
     * @param $id_member
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    private function addMemberInTrainingByTrainer($id_training, $id_member)
    {
        try {
            $start = microtime(true);

            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();

            $arResult = $obMediator->addMembersById($id_training, $id_member, false);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
            ];
            if (empty($arResult['SUCCESS']))
                $return['status'] = 'error';
            else
            {
                $obTraining = $obMediator->buildClass($id_training);

                $rsMediator = new FactoryMediator('User');
                $obMediator = $rsMediator->getClass();
                $obUser = $obMediator->buildClass($id_member);

                $bxEvent = new Event('git.module', 'onAfterTraininerAddMember', ['ENTITY' => $obTraining, 'USER' => $obUser]);
                $bxEvent->send();

                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Запись пользователя в тренировку по id, контроллер
     * @param $id_training
     * @param $id_member
     * @return array|string[]
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    public function addMeInTrainingAction($id_training, $id_member)
    {
        return $this->addMeInTraining($id_training, $id_member);
    }

    /**
     * Запись пользователя в тренировку по id
     * @param $id_training
     * @param $id_member
     * @return array|string[]
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    private function addMeInTraining($id_training, $id_member)
    {
        try {
            $start = microtime(true);

            $arReqFields = $this->arReqFields;

            $obPersonal = new Personal();
            $arUser = $obPersonal->userProfileAction();

            if (!empty($arReqFields))
                foreach ($arReqFields as $reqField)
                    if (empty($arUser['arUser'][$reqField]) || $arUser['arUser'][$reqField] === 0)
                        $arError[] = $reqField;

            if (empty($arError)) {
                $rsMediator = new FactoryMediator('Training');
                $obMediator = $rsMediator->getClass();
                $arResult = $obMediator->addMembersById($id_training, $id_member);

                $return = [
                    'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                    'errors' => $arResult['ERRORS'],
                ];
                if (empty($arResult['SUCCESS'])) {
                    $return['status'] = 'error';
                    $return['detail'] = $arResult['DETAIL'];
                }
                else
                {
                    $return['status'] = 'success';
                    $return['result'] = $arResult['SUCCESS'];
                }
            } else {
                $return = [
                    'status' => 'error',
                    'detail' => $arError,
                    'errors' => 'NEED_INFO'
                ];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Удаление участника из тренировки тренером
     * @param $id_training
     * @param $id_member
     * @return array
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    public function delMemberInTrainingByTrainerAction($id_training, $id_member)
    {
        return $this->delMemberInTrainingByTrainer($id_training, $id_member);
    }

    /**
     * Удаление участника из тренировки тренером
     * @param $id_training
     * @param $id_member
     * @return array
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    private function delMemberInTrainingByTrainer($id_training, $id_member)
    {
        try {
            $start = microtime(true);

            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();

            $arResult = $obMediator->delMembersById($id_training, $id_member, false);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
            ];
            if (empty($arResult['SUCCESS']))
                $return['status'] = 'error';
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Удаление себя из тренировки
     * @param $id_training
     * @param $id_member
     * @return array
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    public function delMeInTrainingByTrainerAction($id_training, $id_member)
    {
        return $this->delMeInTrainingByTrainer($id_training, $id_member);
    }

    /**
     * Удаление себя из тренировки
     * @param $id_training
     * @param $id_member
     * @return array
     * @throws Main\LoaderException
     * @throws Main\SystemException
     */
    private function delMeInTrainingByTrainer($id_training, $id_member)
    {
        try {
            $start = microtime(true);

            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();

            $arResult = $obMediator->delMembersById($id_training, $id_member);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
            ];
            if (empty($arResult['SUCCESS']))
                $return['status'] = 'error';
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Контроллер завершения тренировки тренером (отправка отчета)
     * @param $id_training
     * @return array
     */
    public function doEndTrainingAction($id_training)
    {
        return $this->doEndTraining($id_training);
    }

    /**
     * Завершение тренировки тренером (отправка отчета)
     * @param $id_training
     * @return array
     */
    private function doEndTraining($id_training)
    {
        try {
            $start = microtime(true);
            $rsMediator = new FactoryMediator('Training');
            $obMediator = $rsMediator->getClass();

            $arResult = $obMediator->endTraining($id_training);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
                'errors_code' => $arResult['ERRORS_CODE'],
            ];
            if (empty($arResult['SUCCESS']))
                $return['status'] = 'error';
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Получить данные блокировки активного пользователя в тренировках
     * @return array
     */
    public function getIsIBlockAction()
    {
        return $this->getIsIBlock();
    }

    /**
     * Получить данные блокировки активного пользователя в тренировках
     * @return array
     */
    private function getIsIBlock()
    {
        try {
            $start = microtime(true);
            $rsMediator = new FactoryMediator('User');
            $obMediator = $rsMediator->getClass();

            $arResult = $obMediator->getDataFromLockTableForThisUser();

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'errors' => $arResult['ERRORS'],
                'errors_code' => $arResult['ERRORS_CODE'],
            ];
            if (empty($arResult['SUCCESS']))
                $return['status'] = 'error';
            else
            {
                $return['status'] = 'success';
                $return['result'] = $arResult['SUCCESS'];
            }

        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Вернет страницу отчета по тренировкам
     * @return array
     */
    public function getReportPageAction(array $arFilter = [])
    {
        return $this->getReportPage($arFilter);
    }

    /**
     * Вернет страницу отчета по тренировкам
     * @return array
     */
    private function getReportPage(array $arFilter = [])
    {
        try {
            $start = microtime(true);
            $obBuilder = new ReportBuilder();

            if (!empty($arFilter)) {
                $obBuilder->setFilter($arFilter);
                $result = $obBuilder->getReportPageWithFilter();
            } else
                $result = $obBuilder->getReportPage();

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'result' => $result,
                'status' => 'success'
            ];
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Сформирует xml файл по тренировкам
     * @return array
     */
    public function createExcelFromListAction(array $arTrainings)
    {
        return $this->createExcelFromList($arTrainings);
    }

    /**
     * Сформирует xml файл по тренировкам
     * @return array
     */
    private function createExcelFromList(array $arTrainings)
    {
        try {
            $start = microtime(true);
            $obBuilder = new ReportBuilder();

            $result = $obBuilder->createExcelFromList($arTrainings);

            if (empty($result['ERRORS']))
                $return = [
                    'result' => $result['RESULT'],
                    'status' => 'success'
                ];
            else
                $return = [
                    'errors' => $result['ERRORS'],
                    'status' => 'error'
                ];

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
     * Вернет список людей которые еще не записаны на тренировку
     * @param string $string
     * @param $id_training
     * @return array
     */
    public function getMembersToTrainingByStrAction(string $string, $id_training)
    {
        return $this->getMembersToTrainingByStr($string, $id_training);
    }

    /**
     * Вернет список людей которые еще не записаны на тренировку
     * @param string $string
     * @param $id_training
     * @return array
     */
    private function getMembersToTrainingByStr(string $string, $id_training)
    {
        try {
            $start = microtime(true);
            $obTrainingBuilder = new TrainingBuilder();
            $arTrainings = $obTrainingBuilder->searchMembersForTraining($string, $id_training);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'status' => 'success',
                'result' => $arTrainings,
            ];
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Вернет список всех участников тренировок по строке поиска
     * @return array
     */
    public function getMembersTrainingByStrAction(string $string)
    {
        return $this->getMembersTrainingByStr($string);
    }

    /**
     * Вернет страницу отчета по тренировкам
     * @return array
     */
    private function getMembersTrainingByStr(string $string)
    {
        try {
            $start = microtime(true);
            $obBuilder = new ReportBuilder();

            $result = $obBuilder->getAllUsers($string);

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'result' => $result,
                'status' => 'success'
            ];
        } catch (Main\ArgumentException $e) {
            $return = [
                'status' => 'error',
                'errors' => $e->getMessage()
            ];
        }

        return $return;
    }

    /**
     * Получение данных куратора сайта
     * @return array
     */
    public function getKuratorAction()
    {
        return $this->getKurator();
    }

    /**
     * Получение данных куратора сайта
     * @return array
     */
    private function getKurator()
    {
        try {
            $start = microtime(true);
            $rsMediator = new FactoryMediator('User');
            $obMediator = $rsMediator->getClass();

            $obKurator = $obMediator->getMainKurator();

            if (empty($obKurator['ERROR']))
                $result = $obKurator['RESULT']->getInfo();

            $return = [
                'time' => 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.',
                'result' => $result,
                'status' => 'success'
            ];
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