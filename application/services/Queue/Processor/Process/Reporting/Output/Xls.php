<?php
require_once APPLICATION_PATH . '/../library/PHPExcel/Classes/PHPExcel.php';

class Process_Reporting_Output_Xls extends Process_Reporting_Output
{

    protected $extension = "xlsx";

    protected function processValid($reporting, $attributes, $data)
    {
        try {
            $file = new PHPExcel();
            $file->getProperties()->setCreator("infoCMDB");
            $file->getProperties()->setTitle($reporting[Db_Reporting::NOTE]);
            $file->getProperties()->setSubject($reporting[Db_Reporting::NOTE]);
            $file->getProperties()->setDescription($reporting[Db_Reporting::DESCRIPTION]);


            // add sheet
            $file->addSheet(new PHPExcel_Worksheet($file), $sheetNumber);
            $file->setActiveSheetIndex(0);
            $file->getActiveSheet()->setTitle($reporting[Db_Reporting::NAME]);

            // add first line (attributes)
            $rowKey = 1;
            foreach ($attributes as $columnKey => $attribute) {
                $file->getActiveSheet()->setCellValueByColumnAndRow($columnKey, $rowKey, $attribute);
                $file->getActiveSheet()->getStyleByColumnAndRow($columnKey, $rowKey)->getFont()->setBold(true);
                $file->getActiveSheet()->getStyleByColumnAndRow($columnKey, $rowKey)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            }


            // add content
            $addCnt = 2; // add to rownumber for xls guideline
            foreach ($data as $rowKey => $row) {
                $rowKey = $rowKey + $addCnt;

                foreach ($attributes as $columnKey => $attribute) {
                    $file->getActiveSheet()->setCellValueByColumnAndRow($columnKey, $rowKey, $row[$attribute]);
                }
            }


            // finally autosize
            foreach ($attributes as $columnKey => $attribute) {
                $file->getActiveSheet()->getColumnDimensionByColumn($columnKey)->setAutoSize(true);
            }

            $objWriter = PHPExcel_IOFactory::createWriter($file, 'Excel2007');
            $objWriter->save($this->filepath .'/'. $this->file);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
        }
    }
}