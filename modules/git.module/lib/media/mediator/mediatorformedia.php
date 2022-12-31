<?php
namespace Git\Module\Media\Mediator;

use Bitrix\Main\Diag\Debug;
use Git\Module\Controllers\Bears;
use Git\Module\Helpers\Utils;
use Git\Module\Media\Factory\FactoryMedia;
use Git\Module\Media\General\MediaTraining;
use Git\Module\Mediator\General\Member;
use Git\Module\Mediator\Interfaces\IForMediator;

class MediatorForMedia extends Member
{
    /**
     * Возвращает нужный класс сформированных фабрикой
     * @param int $id
     * @return mixed
     */
    public function buildClass(int $id, string $type = null) : IForMediator
    {
        $obRg = $this->entity = new FactoryMedia($id, $type);
        return $obRg->getClass();
    }

    /**
     * Вернет список медиа тренировок
     * @return array|mixed
     */
    public function getAll()
    {
        return self::getAllStatic();
    }

    /**
     * Вернет список всех медиа тренировок
     * @return array
     */
    public static function getAllStatic()
    {
        return MediaTraining::getAllStatic();

    }

    public function getMediaTrainingObj()
    {
        return new MediaTraining();
    }

    /**
     * Вернет медиа тренировки
     * @param IForMediator $obTraining
     * @return array
     */
    public function getMediaInTraining(IForMediator $obTraining)
    {
        $obMedia = $this->getMediaObj();

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['PARENT_ID'] => $obTraining->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obMedia->getName())
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
        {
            foreach ($arList as $arData)
            {
                $arResult[] = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
            }
        }

        return $arResult;
    }

    /**
     * Вернет медиа тренировки в виде массива из таблицы
     * @param IForMediator $obTraining
     * @return array
     */
    public function getMediaInTrainingModuleTable(IForMediator $obTraining)
    {
        $obMedia = $this->getMediaObj();

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['PARENT_ID'] => $obTraining->getId(),
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obMedia->getName())
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
            $arResult = $arList;

        return $arResult;
    }

    /**
     * Получить медиа в тренировках
     * @param array $arTrainingId
     * @return array
     */
    public function getMediaInTrainings(array $arTrainingId = [])
    {
        $obMediator = self::getMediatorTraining();
        $obTraining = $obMediator->getTrainingObj();

        $obMedia = $this->getMediaObj();

        $arData = [
            $this->arFields['PARENT'] => Utils::formatClassNameForWrite($obTraining->getName()),
            $this->arFields['PARENT_ID'] => $arTrainingId,
            $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obMedia->getName())
        ];

        $arList = $this->get($arData);

        if (!empty($arList))
        {
            foreach ($arList as $arData)
            {
                $arResult[] = $this->buildClass($arData[$this->arFields['CHILD_ID']]);
            }
        }

        return $arResult;
    }

    /**
     * Добавить/Изменить медиа в тренировке
     */
    public function setMediaInTraining()
    {

    }

    public function getMediaObj(int $id = null)
    {
        $obj = new MediaTraining();
        return $obj;
    }

    /**
     * Сохранение файла в тренировки
     * @param string $dzuuid
     * @param string $dztotalchunkcount
     * @param string $fileName
     */
    public function saveTrainingFile($id_training, $dzuuid = '', $dztotalchunkcount = '', $fileName = '', bool $check_rights = true)
    {
        $obMedia = $this->getMediaTrainingObj();
        $obMediator = self::getMediatorTraining();
        $obTraining = $obMediator->buildClass($id_training);

        if ($check_rights) {
            if ($obMediator->isCanUserUploadMedia($obTraining))
                $result = $obMedia->saveFile($dzuuid, $dztotalchunkcount, $fileName);
            else
                $result['ERROR'] = 'PERMISSION DENIED';
        }

        if (empty($result['ERROR'])) {
            $id_media = $result['RESULT'];
            $ar_already_media = $this->checkAlreadyMedia($obTraining);

            $obMedia = $this->buildClass($id_media);

            $tmpResult = $this->add($obTraining, $obMedia);
            $tmpError = $tmpResult->getErrorMessages();

            if ($tmpResult->isSuccess())
            {
                $arResult['SUCCESS'] = $id_media;

                if (!empty($ar_already_media))
                    $this->delOldMedia($ar_already_media);
            }
            if (!empty($tmpError))
                $arResult['ERRORS'] = $tmpError;

        } else {
            $arResult['ERRORS'][] = $result['ERROR'];
        }

        return $arResult;
    }

    /**
     * Получаем количество мишек
     * @param IForMediator $obMedia
     */
    public function getBearsMedia(IForMediator $obMedia)
    {
        $bears = 0;
        $obBears = new Bears();
        $arInfoBear = $obBears->getBearsAction($obMedia->getId());
        $bears = $arInfoBear['count'];

        return $bears;
    }

    /**
     * Удаление старого медиа в тренировке (использовать после добавления нового)
     * @param array $ar_media
     */
    public function delOldMedia(array $ar_media)
    {
        if (!empty($ar_media['ID']))
            $this->del([$ar_media['ID']]);
        if (!empty($ar_media[$this->arFields['CHILD_ID']])) {
            $obMedia = $this->buildClass($ar_media[$this->arFields['CHILD_ID']]);
            $obMedia->delMedia();
        }
    }

    /**
     * Проверка на наличие медиа у данной тренировки
     * @param IForMediator $obTraining
     * @return mixed
     */
    public function checkAlreadyMedia(IForMediator $obTraining)
    {
        $arDataMedia = $this->getMediaInTrainingModuleTable($obTraining);

        return $arDataMedia[0];
    }

    /**
     * Получить тренировку к которой принадлежит медиа
     * @param IForMediator $obMedia
     * @return array
     */
    public function getTrainingWhereMedia(IForMediator $obMedia)
    {
        $obMediator = self::getMediatorTraining();

        return $obMediator->getTrainingByMedia($obMedia);
    }

    /**
     * Получить соседние медиа по регулярной тренировке
     * @param IForMediator $obMedia
     * @return array
     */
    public function getOtherMediaInRgTraining(IForMediator $obMedia)
    {
        $obMediator = self::getMediatorTraining();

        $arObTrainings = $obMediator->getAllTrainingsInRgTrainingByMedia($obMedia);

        if (!empty($arObTrainings))
            foreach ($arObTrainings as $obTraining)
                if (!empty($obTraining->getId()))
                    $arId[] = $obTraining->getId();

        if (!empty($arId) && $arObTrainings[0] instanceof IForMediator) {
            $arData = [
                $this->arFields['PARENT'] => Utils::formatClassNameForWrite($arObTrainings[0]->getName()),
                $this->arFields['PARENT_ID'] => $arId,
                $this->arFields['CHILD'] => Utils::formatClassNameForWrite($obMedia->getName()),
                $this->arFields['ACTIVE'] => 'Y',
            ];
            $arList = $this->get($arData);
        }

        if (!empty($arList)) {
            foreach ($arList as $item) {
                $arResult[] = $this->buildClass($item[$this->arFields['CHILD_ID']]);
            }
        }

        return $arResult;
    }
}
?>