<?php

/**
 * pdf/vcf export handling
 *
 *
 *
 */
class Service_Ci_Export extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 307, $themeId);
    }


    /**
     * retrieves a pdf of the given ci in binary form
     *
     * @param unknown_type $ciId
     *
     * @return string
     */
    public function getPdfFile($ciId, $userId)
    {
        try {
            $filePath     = APPLICATION_PATH . '/views/helpers/pdfTemplates/';
            $filename     = $ciId . '.pdf';
            $downloadfile = $filePath . $filename; // used for pdf templates


            $pdf  = new Zend_Pdf();
            $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);


            $topPos  = $pageHeight - 36;
            $leftPos = 36;

            $helvetiaFont = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);

            // handle content generation

            // select ci type
            $ciTypeDao = new Dao_CiType();
            $ciTypeDto = $ciTypeDao->getCiTypeByCiId($ciId);
            unset($ciTypeDao);

            // select zugewiesene Projekte
            $projectDao  = new Dao_Project();
            $projectList = $projectDao->getProjectsByCiId($ciId);
            unset($projectDao);

            $attributeDao  = new Dao_Attribute();
            $attributeList = $attributeDao->getAttributesByCiId($ciId, $userId);
            unset($attributeDao);


            // print CI id
            $page->setFont($helvetiaFont, 13);
            $page->drawText('CI ID: ' . $ciId, 20, 800);

            // print ci type
            $page->drawText('CI Typ: ' . $ciTypeDto[Db_CiType::DESCRIPTION], 20, 780);


            // datestamp
            //$page->drawText('Datestamp: '.$ciHistoryDto->getDatestamp(), 20, 760);
            $page->drawText('Datestamp: Keiner', 20, 760);


            // project/s:
            $pString = "";
            foreach ($projectList as $project) {
                if ($pString) {
                    $pString .= ', ' . $project['description'];
                } else {
                    $pString = $project['description'];
                }
            }

            $page->drawText('Project(s): ' . $pString, 20, 740);


            $pos = 700;

            $page->setFont($helvetiaFont, 8);
            foreach ($attributeList as $attribute) {
                $value    = $attribute[Db_CiAttribute::VALUE_TEXT] . $attribute[Db_CiAttribute::VALUE_DATE];
                $fileText = $attribute[Db_Attribute::DESCRIPTION] . ": " . $value;
                $page->drawText($fileText, 20, $pos);
                $pos = $pos - 10;
            }


            $pdf->pages[0] = $page;

            //this is the pdf as binary
            return $pdf->render();
        } catch (Exception $e) {
            throw new Exception_Ci($e);
        }
    }


    public function getCiListExport($ciTypeId, $type, $userId, $projectId = null, $filter = null, $exportall = false, $orderBy = null, $ciRelationTypeId = null, $sourceCiid = null)
    {
        $ciServiceGet = new Service_Ci_Get($this->translator, $this->logger, parent::getThemeId());
        $path         = APPLICATION_DATA . '/exports/';
        $isQuery      = false;

        $ciTypeDao = new Dao_CiType();
        $ciType    = $ciTypeDao->getCiType($ciTypeId);

        $date     = date("YmdHms\_");
        $filename = $date . '_' . $ciType[Db_CiType::NAME] . '.' . $type;

        $path = $path . $filename;

        $ciDao = new Dao_Ci();

        // select all ci list data (searchlist conform)

        if (!empty($ciType[Db_CiType::QUERY])) {
            $result        = $ciDao->getListResultQueryForCiList($ciType[Db_CiType::QUERY], $orderBy, array(':user_id:' => $userId, ':project_id:' => $projectId));
            $numberRows    = count($result);
            $attributeList = array();
            foreach ($result[0] as $col => $val) {
                $attributeList[] = array('name' => $col, 'description' => $col);
            }
            $isQuery = true;
        } else {
            $attributeDao = new Dao_Attribute();

            if ($exportall) {
                $attributeList = $attributeDao->getAttributesForExportAll($ciTypeId, $userId);
            } else {
                $attributeList = $attributeDao->getAttributesByTypeId($ciTypeId, parent::getThemeId(), $userId);
            }

            $attributeListIds = array();
            foreach ($attributeList as $attribute) {
                $attributeListIds[] = $attribute[Db_Attribute::ID];
            }


            if (!$projectId) {
                // get permissionlist
                $projectDao  = new Dao_Project();
                $projectList = $projectDao->getProjectsByUserId($userId);

                foreach ($projectList as $p) {
                    if (!$projectId) {
                        $projectId = $p[Db_Project::ID];
                    } else {
                        $projectId .= ', ' . $p[Db_Project::ID];
                    }
                }
            }

            $ciList     = $ciDao->getCiListForCiIndex($ciTypeId, $projectId, $userId, null, null, null, null, null, null, null, $ciRelationTypeId, $sourceCiid);
            $numberRows = count($ciList);
            $result     = $ciServiceGet->getListResultForCiList($attributeList, $ciList);


            // if no order defined, use the default ci_type configuration
            if (empty($orderBy)) {
                $defaultSortAttribute = null;
                if ($ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID]) {
                    $defaultSortAttribute = $ciType[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID];
                    $isDefaultSortAsc     = $ciType[Db_CiType::IS_DEFAULT_SORT_ASC];
                } else {
                    // check if one of the parent ci_types has a order defined
                    $needParent     = true;
                    $parentCiTypeId = $ciType[Db_CiType::PARENT_CI_TYPE_ID];

                    // loop through all parents
                    while ($needParent) {
                        if (!$parentCiTypeId) {
                            $needParent = false;
                            break;
                        }

                        $parentCi       = $ciTypeDao->getRawCiType($parentCiTypeId);
                        $parentCiTypeId = $parentCi[Db_CiType::PARENT_CI_TYPE_ID];

                        if ($parentCi[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID]) {
                            $defaultSortAttribute = $parentCi[Db_CiType::DEFAULT_SORT_ATTRIBUTE_ID];
                            $isDefaultSortAsc     = $parentCi[Db_CiType::IS_DEFAULT_SORT_ASC];
                            $needParent           = false;
                            break;
                        }
                    }
                }

                //if default-attribute is set in any of the ci-types
                if ($defaultSortAttribute) {
                    $atr = $attributeDao->getAttribute($defaultSortAttribute);
                    if ($isDefaultSortAsc) {
                        $direction = "ASC";
                    } else {
                        $direction = "DESC";
                    }
                    #only add ordering if attribute is in result
                    if (in_array($defaultSortAttribute, $attributeListIds)) {
                        $orderBy = array($atr[Db_Attribute::NAME] => $direction);
                    }
                }
            }

            if (is_array($result) && isset($orderBy) && count($result) > 0) {
                $result = $ciServiceGet->array_sort($result, $orderBy, $attributeList);
            }
        }


        if (isset($filter)) {
            $result = $ciServiceGet->filterciList($result, $attributeList, $filter);
            $result = $ciServiceGet->filterciListAttributes($result, $attributeList, $filter);
        }


        switch ($type) {
            case 'csv':
                $this->writeCsvExport($path, $ciType, $attributeList, $result, $isQuery);
                break;
            case 'xls':
            case 'xlsx':
            default:
                $this->writeXlsxExport($path, $ciType, $attributeList, $result, $isQuery);
                break;
        }

        $size = filesize($path);
        return array(
            'filename' => $filename,
            'path'     => $path,
            'size'     => $size,
        );
    }

    private function writeCsvExport($path, $ciType, $attributeList, $result, $isQuery = false)
    {
        // TODO: implement me!
    }


    private function writeXlsxExport($path, $ciType, $attributeList, $result, $isQuery = false)
    {
        require_once APPLICATION_PATH . '/../library/PHPExcel/Classes/PHPExcel.php';

        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_sqlite;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        $session   = Zend_Registry::get('session');
        $adminMode = $session->adminMode;


        // create new Excel Writer
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("infoCMDB");
        $objPHPExcel->getProperties()->setTitle($ciType[Db_CiType::DESCRIPTION]);
        $objPHPExcel->getProperties()->setSubject($ciType[Db_CiType::DESCRIPTION]);
        $objPHPExcel->getProperties()->setDescription($ciType[Db_CiType::NOTE]);

        $sheet = $objPHPExcel->getActiveSheet();

        // write Attribute Names (header)
        $rowCount  = 1;
        $index_pos = 0;

        // ci id

        if ($isQuery == false) {
            if ($adminMode === true) {
                $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, 'ci_id');
            } else {
                $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, 'id');
            }
            $sheet->getStyleByColumnAndRow($index_pos, $rowCount)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($index_pos, $rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $index_pos++;
        }

        foreach ($attributeList as $attribute) {
            if ($adminMode === true) {
                $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, $attribute[Db_Attribute::NAME]);
            } else {
                $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, $attribute[Db_Attribute::DESCRIPTION]);
            }
            $sheet->getStyleByColumnAndRow($index_pos, $rowCount)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($index_pos, $rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $index_pos++;
        }
        $rowCount++;


        // Write data lines
        foreach ($result as $key => $value) {

            $index_pos = 0;

            if ($isQuery == false) {
                // ci id
                $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, $value[Db_Ci::ID]);
                $index_pos++;
            }
            foreach ($attributeList as $attribute) {

                $value[$attribute[Db_Attribute::NAME]] = html_entity_decode(htmlspecialchars_decode(strip_tags($value[$attribute[Db_Attribute::NAME]])));

                // Zahlungsmittel
                if ($attribute[Db_Attribute::ATTRIBUTE_TYPE_ID] == 9) {
                    $sheet->getStyleByColumnAndRow($index_pos, $rowCount)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
                    $value[$attribute[Db_Attribute::NAME]] = str_replace('.', '', substr($value[$attribute[Db_Attribute::NAME]], 5));
                    $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, $value[$attribute[Db_Attribute::NAME]]);
                    $sheet->getCellByColumnAndRow($index_pos, $rowCount)->setDataType(PHPExcel_Cell_DataType::TYPE_NUMERIC);
                } elseif (preg_match('/^=/', $value[$attribute[Db_Attribute::NAME]])) { // if the first signs is the "equals"-symbol, excel thinks the value is a formula --> force data type string
                    $sheet->setCellValueExplicitByColumnAndRow($index_pos, $rowCount, $value[$attribute[Db_Attribute::NAME]], PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($index_pos, $rowCount, $value[$attribute[Db_Attribute::NAME]]);
                }
                $index_pos++;
            }
            unset($result[$key]);
            $rowCount++;
        }


        // autosize

        $index_pos = 0;
        $sheet->getColumnDimensionByColumn($index_pos)->setAutoSize(true);
        $index_pos++;

        foreach ($attributeList as $attribute) {
            $sheet->getColumnDimensionByColumn($index_pos)->setAutoSize(true);
            $index_pos++;
        }

        unset($result);
        unset($attributeList);
        unset($index_pos);
        unset($rowCount);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $objWriter->setUseDiskCaching(true, APPLICATION_DATA . "/cache");
        $objWriter->save($path);
    }
}