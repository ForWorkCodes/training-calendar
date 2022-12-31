<?php
namespace Git\Module\Builder\General;

require $_SERVER["DOCUMENT_ROOT"].'/local/vendor/autoload.php';

use Bitrix\Main\Diag\Debug;
use Git\Module\Builder\Abstracts\BuilderTrainingModule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Строитель файла excel
 * Class ExcelBuilder
 * @package Git\Module\Builder\General
 */
class ExcelBuilder
{
    private $obSpreadsheet;
    private $sheet;

    public function __construct()
    {
        //Создаем экземпляр класса электронной таблицы
        $this->obSpreadsheet = new Spreadsheet();
        //Получаем текущий активный лист
        $this->sheet = $this->obSpreadsheet->getActiveSheet();
        // Выравнивание всего документа по левому краю
    }

    /**
     * Запись в ячейки
     */
    public function setCellValue(string $coord, string $value)
    {
        $this->sheet->setCellValue($coord, $value);
    }

    /**
     * Форматирование ячеек
     * @param $coord
     * @param $type
     */
    public function setFillType($coord, $type)
    {
        switch ($type) {
            case 'bold':
                $styleArray['font']['bold'] = true;
                break;
        }

        $this->sheet->getStyle($coord)->applyFromArray($styleArray);
    }

    /**
     * Установить авто ширину для ячейки
     * @param $coord
     */
    public function setAutoSize($coord)
    {
        $this->sheet->getColumnDimension($coord)->setAutoSize(true);
    }

    /**
     * Выравнивание по левому краю
     * @param $last_row
     */
    public function setAlignment($last_row)
    {
        $this->sheet->getStyle('A1:Z'.$last_row)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }

    /**
     * Сохранение сформированного файла
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function saveExcel($path)
    {
        $writer = new Xlsx($this->obSpreadsheet);

        try {
            $writer->save($path);
            $result = true;
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $exception) {
            $result = $exception->getMessage();
        }

        return $result;
    }
}
?>