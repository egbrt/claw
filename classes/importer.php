<?php
/*
    Classification Workbench
    Copyright (c) 2020-2023, WONCA ICPC-3 Foundation

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

require_once "classes/database.php";


class Importer {
    public $errors;
    private $file;

    function __construct()
    {
        $this->errors = "";
        $this->file = "";
    }

    function uploadFile()
    {
        $valid = false;
        if (basename($_FILES["csvToUpload"]["name"])) {
            $dir = "uploads/";
            $this->file = $dir . basename($_FILES["csvToUpload"]["name"]);
            $type = strtolower(pathinfo($this->file,PATHINFO_EXTENSION));
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            if (!file_exists($this->file)) {
                if ($type == "csv") {
                    if (move_uploaded_file($_FILES["csvToUpload"]["tmp_name"], $this->file)) {
                        $valid = true;
                    } else {
                        $this->errors = "Sorry, there was an error uploading your file.";
                    }
                }
                else {
                    $this->errors = "Only CSV files allowed.";
                }
            }
            else {
                $this->errors = "file already exists, and could not be deleted.";
            }
        }
        else {
            $this->errors = "Please choose a file first.";
        }
        return $valid;
    }

    function parseCSV($db, $replace)
    {
        $this->errors = '';
        $valid = true;
        $data = fopen($this->file,"r");
        if ($data) {
            if ($replace) {
                $prevCode = 0;
                $prevKind = 0;
                while ($column = fgetcsv($data)) {
                    if (count($column) == 3) {
                        $code = $db->nodes->find(strval($column[0]));
                        $kind = $db->rkinds->getId($column[1]);
                        if ($code and ($kind > 0)) {
                            if (($code != $prevCode) or ($kind != $prevKind)) {
                                $db->rubrics->deleteAllOfKind($code->id, $kind);
                                $prevCode = $code;
                                $prevKind = $kind;
                            }
                        }
                    }
                }
                rewind($data);
            }
            while ($column = fgetcsv($data)) {
                if (count($column) == 3) {
                    $code = $db->nodes->find(strval($column[0]));
                    $kind = $db->rkinds->getId($column[1]);
                    $text = str_replace('\"', '"', $column[2]);
                    if ($code and ($kind > 0) and ($text != "")) {
                        $db->rubrics->insert($code->id, $kind, "en", $text);
                    }
                }
            }
            fclose($data);
        }
        return $valid;
    }    

    
}
?>
