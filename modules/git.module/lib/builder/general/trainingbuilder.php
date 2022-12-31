<?php
namespace Git\Module\Builder\General;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Git\Module\Builder\Abstracts\BuilderTrainingModule;
use Git\Module\Factory\General\FactoryMediator;
use Git\Module\Helpers\Utils;
use Git\Module\Mediator\Interfaces\IForMediator;
use Git\Module\Training\Interfaces\ITraining;
use Git\Module\RegularTraining\Interfaces\IRegularTraining;
use Git\Module\User\Mediator\MediatorForUser;

/**
 * Строитель для тренировок
 * Class TrainingBuilder
 * @package Git\Module\Builder\General
 */
class TrainingBuilder extends BuilderTrainingModule
{
    private $arRgTraining;
    private $arLock;
    private $needGetFilterData;
    private $obMediatorUser;

    /**
     * Промежуточный метод для получения страницы тренировки
     * @param $id_training
     * @return array
     */
    public function getTrainingPage($id_training)
    {
        return $this->buildTrainingPage($id_training);
    }

    /**
     * Вернет список людей которые еще не записаны на тренировку
     * @param string $string
     * @param $id_training
     */
    public function searchMembersForTraining(string $string, $id_training)
    {
        $obMediator = new FactoryMediator('User');
        $this->obMediatorUser = $obMediator->getClass();

        return $this->getMembersForTraining($string, $id_training);
    }

    private function getMembersForTraining(string $string, $id_training)
    {
        $obTraining = $this->getMembersInTraining($id_training);
        $arTraining = $obTraining->getInfo();
        $arObMembers = $arTraining['MEMBERS'];

        if (!empty($arObMembers))
            foreach ($arObMembers as $obMember)
                $arId[] = $obMember->getId();

        global $USER;
        $arId[] = $USER->GetID();

        $arUsers = $this->obMediatorUser->getUsersInUserTableByString($string);

        if (!empty($arUsers))
            foreach ($arUsers as $arUser) {
                if (!in_array($arUser['ID'], $arId)) {
                    $arResult['USERS'][] = $this->formatDataUser($arUser);
                }
            }

        return $arResult;
    }

    /**
     * Получить список участников тренировки
     * @param $id_training
     * @return mixed
     */
    private function getMembersInTraining($id_training)
    {
        $obMediator = new FactoryMediator('RegularTraining');
        $obRgMediator = $obMediator->getClass();

        $arObRgTraining = $obRgMediator->getRgByIdTraining($id_training);

        if (!empty($arObRgTraining))
            foreach ($arObRgTraining as $obRgTraining)
                $arObTraining = $obRgTraining->getTrainingById($id_training);

        if (!empty($arObTraining))
            foreach ($arObTraining as $obTraining)
                $obTraining->getMembers();

        return $obTraining;
    }

    /**
     * Промежуточный метод для получения списка тренировок
     * @return array
     */
    public function getListTrainingsPage()
    {
        return $this->buildTrainingsPage();
    }

    /**
     * Формирование данных для получения страницы тренировки
     * @param $id_training
     * @return array
     */
    private function buildTrainingPage($id_training)
    {
        $obMediator = new FactoryMediator('RegularTraining');
        $obRgMediator = $obMediator->getClass();

        $arObRgTraining = $obRgMediator->getRgByIdTraining($id_training);

        if (!empty($arObRgTraining))
            foreach ($arObRgTraining as $obRgTraining)
                $arId[] = $obRgTraining->getId();

        $arFilter = [
            'ID' => $arId,
            'ACTIVE' => '',
            'TRAINING' => $id_training // Передаю для дальнейшей фильтрации получаемой тренировки
        ];
        $this->setFilter($arFilter);

        return $this->buildTrainingsPage();
    }

    /**
     * Формирует массив данных для страницы тренировок
     * @return array
     */
    private function buildTrainingsPage()
    {
        $this->getList();
        $this->getMyUserData();
        $this->getUserLockTable();

        $obRgTrainings = $this->arRgTraining;

        if (!empty($obRgTrainings) && count($obRgTrainings) > 0)
        {
            foreach ($obRgTrainings as $obRgTraining)
            {
                if (!($obRgTraining instanceof IRegularTraining)) continue;

                if (!empty($this->arFilter['TRAINING'])) {
                    $obRgTraining->getTrainingById($this->arFilter['TRAINING']);
                } else {
                    $obRgTraining->getTrainings(false); // Вернется объект, с ним нужно работать
                }
                $obRgTraining->getTrainer();

                $arInfo = $obRgTraining->getInfo();

                if ($this->needGetFilterData) {
                    if (!empty($arInfo['PROPERTIES']['WAVE']['VALUE_ENUM_ID']))
                        $arFilterData['WAVE'][$arInfo['PROPERTIES']['WAVE']['VALUE_ENUM_ID']] = [
                            'NAME' => $obRgTraining->formatWaveData($arInfo['PROPERTIES']['WAVE']['VALUE_ENUM_ID']),
                            'ID' => $arInfo['PROPERTIES']['WAVE']['VALUE_ENUM_ID']
                        ];

                    if (!empty($arInfo['PROPERTIES']['LOCATION']['VALUE']))
                        $arFilterData['LOCATION'][$arInfo['PROPERTIES']['LOCATION']['VALUE']] = [
                            'NAME' => $arInfo['PROPERTIES']['LOCATION']['VALUE'],
                            'ID' => $arInfo['PROPERTIES']['LOCATION']['VALUE']
                        ];

                    for ($i = 1; $i < 8; $i++)
                        $arFilterData['DAY'][$i] = Utils::getLangNameDayByNum($i);

                    if (!empty($arInfo['TRAINER']))
                    {
                        foreach ($arInfo['TRAINER'] as $obTrainer)
                        {
                            if (!($obTrainer instanceof IForMediator)) continue;
                            $tmpTrainer = $obTrainer->getInfo();
                            $format = $this->formatDataUser($tmpTrainer);
                            $arFilterData['TRAINER'][$format['ID']] = [
                                'NAME' => $format['FORMAT_NAME'],
                                'ID' => $format['ID']
                            ];
                        }
                    }
                }

                if (!empty($arInfo['TRAININGS']))
                {
                    foreach ($arInfo['TRAININGS'] as $obTraining)
                        $arResult['TRAININGS'][] = $this->formatDataTrainingFromObject($obTraining, $arInfo);
                }
            }

            $return['TRAININGS'] = $this->sortTrainingsList($arResult['TRAININGS']);

            if (!empty($arFilterData))
                $return['FILTER'] = $arFilterData;

        }

        $return['USER'] = $this->arUser;
        $return['LOCK_DATA'] = $this->arLock;

        $rsMediator = new FactoryMediator('User');
        $obMediator = $rsMediator->getClass();
        $obKurator = $obMediator->getMainKurator();

        if (empty($obKurator['ERROR']))
            $return['KURATOR'] = $this->formatDataUser($obKurator['RESULT']->getInfo());

        return $return;
    }

    private function getUserLockTable()
    {
        $rsMediator = new FactoryMediator('User');
        $obMediator = $rsMediator->getClass();
        $this->arLock = $obMediator->getDataFromLockTableForThisUser()['SUCCESS'];
    }

    /**
     * Получение списка регулярных тренировок
     */
    private function getList()
    {
        $obMediator = new FactoryMediator('RegularTraining');
        $obRgMediator = $obMediator->getClass();

        if (!empty($this->arFilter))
            $obRgMediator->setFilter($this->arFilter);

        $this->arRgTraining = $obRgMediator->getAllWithChildren();
    }

    /**
     * Установка фильтра для выборки
     * @param array $arFilter
     */
    public function setFilter(array $arFilter)
    {
        $arResult = [];

        foreach ($arFilter as $key => $arData) {
            switch($key) {
                case 'TRAINER':
                    $arResult['PROPERTY_' . $key] = $arData;
                    break;
                case 'TRAINING': // Только чтобы в дальнейшем получить одну тренировку внутри регулярной
                    $arResult[$key] = $arData;
                    break;
                case 'DAY':
                    $arResult['PROPERTY_' . $key] = $arData;
                    break;
                case 'LOCATION':
                    $arResult['PROPERTY_' . $key] = $arData;
                    break;
                case 'WAVE':
                    $arResult['PROPERTY_' . $key] = $arData;
                    break;
                case 'ID':
                    $arResult[$key] = $arData;
                    break;
                case 'ACTIVE':
                    $arResult[$key] = $arData;
                    break;
            }
        }

        if (!empty($arResult))
            $this->arFilter = $arResult;
    }

    /**
     * Вернуть также и возможные действия пользователя с тренировкой
     */
    public function needButtonType()
    {
        $this->needBtnType = true;
    }

    /**
     * Вернуть также и медиа тренировки
     */
    public function needMedia()
    {
        $this->needMedia = true;
    }

    /**
     * Вернуть также и данные для заполнения фильтра (для фронта)
     */
    public function needGetFilterData()
    {
        $this->needGetFilterData = true;
    }
}
?>