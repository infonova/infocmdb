<?php

class Process_Reporting_Output_Csv extends Process_Reporting_Output
{

    protected $extension = "csv";

    protected function processValid($reporting, $attributes, $data)
    {
        $file = fopen($this->filepath .'/'. $this->file, 'w') or die("can't open file");

        // write first line
        $line = null;
        foreach ($attributes as $attribute) {
            if ($line) {
                $line .= ';';
            }
            $line .= $attribute;
        }

        // TODO: remove/replace linebreak handling!
        $line .= "
";
        fwrite($file, $line);


        // write data lines
        foreach ($data as $res) {
            $line = null;
            foreach ($attributes as $attribute) {
                if ($line) {
                    $line .= ';';
                }
                $line .= utf8_decode($res[$attribute]);
            }
            // TODO: remove/replace linebreak handling!
            $line .= "
";
            fwrite($file, $line);
        }

        fclose($file);
    }
}