<?php

class FileImportCest extends AbstractFunctionalTest
{
    protected $baseData = array(
        array(
            'project' => array(
                'csv' => 'firestorm',
                'db' => array(
                    'table' => 'ci_project',
                    'column' => 'project_id',
                    'value' => 2,
                ),
            ),
            'ci_type' => array(
                'csv' => 'demo',
                'db' => array(
                    'table' => 'ci',
                    'column' => 'ci_type_id',
                    'value' => 1,
                ),
            ),
            'general_unique_input' => array(
                'csv' => 'acceptance_test_row_1',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_regular_input' => array(
                'csv' => 'Regular Input',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_numeric_input' => array(
                'csv' => '42597856',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_textarea' => array(
                'csv' => 'Textarea',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_textedit' => array(
                'csv' => '<b>Textedit</b>',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_dropdown_static' => array(
                'csv' => 'Option 1',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_default',
                    'value' => 1,
                ),
            ),
            'general_checkbox' => array(
                'csv' => 'Check 2',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                    'value' => 5,
                ),
            ),
            'general_radio' => array(
                'csv' => 'Radio 3',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_default',
                    'value' => 9,
                ),
            ),
            'general_date' => array(
                'csv' => '2017-01-01',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_date',
                ),
            ),
            'general_datetime' => array(
                'csv' => '2017-05-23 20:15:33',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_date',
                ),
            ),
            'general_currency' => array(
                'csv' => '1200,45',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_password' => array(
                'csv' => 'Password',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            'general_hyperlink' => array(
                'csv' => 'http://bearingpoint.com',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            /*
            'general_attachment' => array(
                'csv'       => 'file1.pdf',
                'db' => array(
                    'table'  => 'ci_attribute',
                    'column' => 'value_text',
                ),
            ),
            */
            'general_dropdown_citype' => array(
                'csv' => '15',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_ci',
                ),
            ),
            'general_dropdown_citype_persistent' => array(
                'csv' => '12',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_ci',
                ),
            ),
            'general_dropdown_sql_filled_select' => array(
                'csv' => '1',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_ci',
                ),
            ),
            'general_dropdown_sql_filled_autocomplete' => array(
                'csv' => '2',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_ci',
                ),
            ),
            'general_dropdown_sql_filled_multiselect' => array(
                'csv' => '3,4',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_ci',
                ),
            ),
            'general_dropdown_sql_filled_multiselect_counter' => array(
                'csv' => '3::1,4::2',
                'db' => array(
                    'table' => 'ci_attribute',
                    'column' => 'value_ci',
                ),
            ),
        ),
    );


    public function importFileAutoImportCreate(FunctionalTester $I)
    {
        $baseFilename = get_class($this) . '_' . __FUNCTION__ . '.csv';
        $numRecordsCi = $I->grabNumRecords('ci');

        $this->importFileAutoImport($I, $baseFilename, $this->baseData);
        $this->validateCiData($I, $this->baseData);

        $numRecordsCi = $numRecordsCi + count($this->baseData);
        $I->seeNumRecords($numRecordsCi, 'ci');

    }

    public function importFileAutoImportUpdate(FunctionalTester $I)
    {
        $baseFilename = get_class($this) . '_' . __FUNCTION__ . '.csv';

        $data = $this->baseData;

        $data[0]['general_regular_input']['csv']                           = 'Changed Regular Input';
        $data[0]['general_numeric_input']['csv']                           = 5678;
        $data[0]['general_textarea']['csv']                                = 'Changed Textarea';
        $data[0]['general_textedit']['csv']                                = 'Changed <b>Textedit</b>';
        $data[0]['general_dropdown_static']['csv']                         = 'Option 2';
        $data[0]['general_dropdown_static']['csv']                         = 'Option 2';
        $data[0]['general_dropdown_static']['db']['value']                 = 2;
        $data[0]['general_checkbox']['csv']                                = 'Check 3';
        $data[0]['general_checkbox']['db']['value']                        = 6;
        $data[0]['general_radio']['csv']                                   = 'Radio 1';
        $data[0]['general_radio']['db']['value']                           = 7;
        $data[0]['general_date']['csv']                                    = '2018-07-12';
        $data[0]['general_datetime']['csv']                                = '2030-12-05 15:23:55';
        $data[0]['general_currency']['csv']                                = '10,50';
        $data[0]['general_password']['csv']                                = 'new password';
        $data[0]['general_hyperlink']['csv']                               = 'http://www.bearingpoint.com/test';
        $data[0]['general_dropdown_citype']['csv']                         = 14;
        $data[0]['general_dropdown_citype_persistent']['csv']              = 15;
        $data[0]['general_dropdown_sql_filled_select']['csv']              = 2;
        $data[0]['general_dropdown_sql_filled_autocomplete']['csv']        = '3';
        $data[0]['general_dropdown_sql_filled_multiselect']['csv']         = '2,3';
        $data[0]['general_dropdown_sql_filled_multiselect_counter']['csv'] = '1::30,3::1';

        $this->importFileAutoImport($I, $baseFilename, $data);
        $this->validateCiData($I, $data);
    }

    public function importFileAutoImportUpdateWithSpecialCharactersXSS(FunctionalTester $I)
    {
        $baseFilename = get_class($this) . '_' . __FUNCTION__ . '.csv';

        $data = $this->baseData;

        $data[0]['general_regular_input']['csv'] = 'Regular input with XSS... <script>console.error(\'XSS-injection\'); alert(\'XSS-Injection\');</script>';

        $this->importFileAutoImport($I, $baseFilename, $data);
        $this->validateCiData($I, $data);
    }

    public function importFileAutoImportUpdateWithSpecialCharactersSqlInjection(FunctionalTester $I)
    {
        $baseFilename = get_class($this) . '_' . __FUNCTION__ . '.csv';

        $data = $this->baseData;

        $data[0]['general_regular_input']['csv'] = 'Regular input with SQL Injection\'" OR 1=1; show tables; --';

        $this->importFileAutoImport($I, $baseFilename, $data);
        $this->validateCiData($I, $data);
    }

    protected function importFileAutoImport(FunctionalTester $I, $baseFilename, $data)
    {
        $fileDestination = APPLICATION_PUBLIC . '/_uploads/import/queue/auto_validation/import/' . $baseFilename;

        $I->createCsv($fileDestination, $data);

        $I->startListener('file');

        $I->seeInDatabase('queue_message', array(
            'queue_id' => 1,
            'args' => "<?xml version=\"1.0\"?>\n<arguments><argument>" . $baseFilename . "</argument><argument>auto</argument><argument>import</argument></arguments>\n",
            'status' => 'idle',
        ));

        $I->startProcessor('import', 'file');

        $I->seeInDatabase('queue_message', array(
            'queue_id' => 1,
            'args' => "<?xml version=\"1.0\"?>\n<arguments><argument>" . $baseFilename . "</argument><argument>auto</argument><argument>import</argument></arguments>\n",
            'status' => 'completed',
        ));

        $I->seeInDatabase('import_file_history', array(
            'filename' => $baseFilename,
            'validation' => 'auto',
            'queue' => 'import',
            'status' => 'success',
        ));
    }

    protected function validateCiData(FunctionalTester $I, $data)
    {
        $lastInsertedCiIdResult = $I->grabQueryResult('SELECT * FROM ci ORDER BY ID DESC LIMIT 1');
        $I->assertSame(count($lastInsertedCiIdResult), 1);
        $lastInsertedCiId = $lastInsertedCiIdResult[0]['id'];

        $ciId = $lastInsertedCiId - count($data) + 1;

        foreach ($data as $row) {
            foreach ($row as $attributeName => $attributeConfig) {
                $dbConfig = $attributeConfig['db'];
                if (isset($dbConfig['table']) && isset($dbConfig['column'])) {
                    $value = $attributeConfig['csv'];
                    if (isset($dbConfig['value'])) {
                        $value = $dbConfig['value'];
                    }

                    $ciColumnName = 'ci_id';
                    if ($dbConfig['table'] == 'ci') {
                        $ciColumnName = 'id';
                    }

                    $dbRow = array(
                        $ciColumnName => $lastInsertedCiId,
                        $dbConfig['column'] => $value,
                    );
                    $I->seeInDatabase($dbConfig['table'], $dbRow);
                }
            }

            $ciId++;
        }
    }


}