<?php
namespace Git\Module\Media\Abstracts;

use Bitrix\Main;
use Git\Module\Mediator\Interfaces\IForMediator;

abstract class Media implements IForMediator
{
    protected $name;
    protected $id;
    protected $info;
    protected $mediator;
    protected $status;
    protected $tmpFileFolder;

    public function __construct(int $id = null)
    {
        $this->setName();
        $ds = DIRECTORY_SEPARATOR;
        $this->tmpFileFolder = $_SERVER['DOCUMENT_ROOT'] . "{$ds}upload{$ds}tmp{$ds}";

        if ($id != null)
            $this->setInfo($id);
    }

    public function getInfo()
    {
        return $this->info;
    }

    protected function setInfo(int $id)
    {
        $this->setId($id);

        $this->info = $this->getDetailInfo();

        $this->info['ID'] = $this->id;
        $this->info['CLASS'] = $this->name;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    protected function setName()
    {
        $this->name = get_class($this);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMediator()
    {
        $this->setMediator();
        return $this->mediator;
    }

    public static function getListById(array $arId)
    {
        // TODO: Implement getListById() method.
    }

    /**
     * Сбор файла из чанков
     * @param $arPost
     * @return string
     * @throws Main\ArgumentException
     * @throws Main\IO\IoException
     */
    public function makeFile($arPost)
    {
        $fileId = $arPost['dzuuid'];
        $chunkTotal = $arPost['dztotalchunkcount'];
        $fileName = $arPost['fileName'];
        if(empty($fileId) || empty($chunkTotal) || empty($fileName)) {
            throw new Main\ArgumentException (GetMessage('CD_VAF_ERROR_EMPTY_FILE_DATA'));
        }

        $targetPath = $this->tmpFileFolder;
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        for ($i = 1; $i <= $chunkTotal; $i++) {
            // target temp file
            $temp_file_path = realpath("{$targetPath}{$fileId}-{$i}.{$fileType}");
            if($temp_file_path === false) {
                throw new Main\IO\IoException("Chunk {$i} not found");
            }

            // copy chunk
            $chunk = file_get_contents($temp_file_path);
            if ( empty($chunk) ) {
                throw new Main\IO\IoException("Chunks are uploading as empty strings.");
            }

            // add chunk to main file
            file_put_contents("{$targetPath}{$fileId}.{$fileType}", $chunk, FILE_APPEND | LOCK_EX);

            // delete chunk
            unlink($temp_file_path);
            if ( file_exists($temp_file_path) ) {
                throw new Main\IO\IoException("Your temp files could not be deleted.");
            }
        }

        if(file_exists($targetPath.$fileId.'.'.$fileType)) {
            return $targetPath.$fileId.'.'.$fileType;
        } else {
            throw new Main\IO\IoException('Error upload file');
        }
    }

    /**
     * Первичное сохранение фала, сразу в iblock
     * @param string $dzuuid
     * @param string $dztotalchunkcount
     * @param string $fileName
     * @return array
     */
    public function saveFile($dzuuid = '', $dztotalchunkcount = '', $fileName = '')
    {
        $post['dzuuid'] = $dzuuid;
        $post['dztotalchunkcount'] = $dztotalchunkcount;
        $post['fileName'] = $fileName;

        try {
            $fullFile = $this->makeFile($post);
        } catch (Main\ArgumentException $e) {
            $result['status'] = 'error';
            $result['errors'] = $e->getMessage();
        } catch (Main\IO\IoException $e) {
            $result['status'] = 'error';
            \GModule::log('Error make file '.$fileName.': '.$e->getMessage());
            $result['errors'] = GetMessage('CD_VAF_ERROR_UPLOAD_FILE');
        } catch (\Exception $e) {
            $result['status'] = 'error';
            \GModule::log('Error make file '.$fileName.': '.$e->getMessage());
            $result['errors'] = GetMessage('CD_VAF_ERROR_UPLOAD_FILE');
        }

        if ($fullFile) {
            $obEl = new \CIBlockElement();

            $mime = mime_content_type($fullFile);
            $arMime = explode('/', $mime);
            $type = $arMime[0];
            $arName = explode('.', $post['fileName']);
            $name = str_replace( '.'.end($arName), '', $post['fileName']);

            $PROP['FILE'] = \CFile::MakeFileArray($fullFile);
            $PROP['SIZE'] = $PROP['FILE']['size'];
            $PROP['TYPE'] = $type;


            if ($type == 'video')
            {
                $PROP['IS_VIDEO'] = 'Y';
            }

            $field = [
                'ACTIVE' => 'Y',
                'IBLOCK_ID' => $this->getIblockId(),
                'NAME' => $name,
                "PROPERTY_VALUES" => $PROP,
            ];
            if ($type == 'image')
            {
                $field['PREVIEW_PICTURE'] = $PROP['FILE'];
            }

            $id = $obEl->Add($field);

            if(!$id)
            {
                $result['ERROR'] = $obEl->LAST_ERROR;
            }
            else
            {
                $result['RESULT'] = $id;
                unlink($fullFile);
            }
        } else {
            $result['ERROR'] = $result['errors'];
        }

        return $result;
    }

    /**
     * Получить мишек у видео
     */
    public function getBears()
    {
        if (empty($this->getId())) return;
        $obMediator = $this->getMediator();
        return $this->info['BEARS'] = $obMediator->getBearsMedia($this);
    }

    public function delMedia()
    {
        if (empty($this->getId())) return;

        return \CIBlockElement::Delete($this->getId());
    }

    abstract static public function getIblockId();
    abstract protected function setMediator();
    abstract protected function getDetailInfo();
}
?>