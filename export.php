<?php
/*
    Classification Workbench
    Copyright (c) 2020-2021, WONCA ICPC-3 Foundation

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

include "templates/header.php";
require_once "templates/menu.php";
require_once "auth/user.php";
require_once "classes/database.php";

if (isAuthenticated($con, $user, $name, $role) and (($role == "editor") or ($role == "admin"))) {
    showMenu($user, $role);
    $db = new Database($con);
    defineExport($db);
    if (isset($_REQUEST['submit'])) {
        $action = $_REQUEST['submit'];
        if ($action == "Export") {
            if ((isset($_REQUEST['exportHTML'])) and ($_REQUEST['exportHTML'])) {
                $file = "./downloads/export.html";
                if (file_exists($file)) unlink($file);
                createExportHTML($db, $file);
            }
            else {
                $file = "./downloads/export.csv";
                if (file_exists($file)) unlink($file);
                if ((isset($_REQUEST['exportSimilarity'])) and ($_REQUEST['exportSimilarity'])) {
                    createExportSimilarity($db, $file);
                }
                else {
                    createExportCSV($db, $file);
                }
            }
            
            echo "<div id='exportResults'>";
            if (file_exists($file)) {
                echo "<p>Click the file name to download:<br/>";
                echo "<a href=\"" . $file ."\" download>" . basename($file) . "</a></p>";
            }
            else {
                echo "<p>There is no file created.</p>";
            }
            echo "</div>";
        }
    }
}
else {
    header('Location: ./index.php');
}
include "templates/footer.php";


function defineExport($db)
{
    echo "<h3>Define export</h3>";
    echo "<p>Select which rubrics you want to export.</p>";
    showRKinds($db);

    echo "<div id='defineExport'>";
    echo "<form method='post'>";
    echo "<input type='hidden' id='selectedRKinds' name='selectedRKinds' />";
    echo "<input type='checkbox' id='exportHTML' name='exportHTML'";
    if (isset($_REQUEST['exportHTML']) and ($_REQUEST['exportHTML'])) {
        echo " checked";
    }
    echo ">Export HTML instead of CSV</input><br/>";

    echo "<input type='checkbox' id='exportSimilarity' name='exportSimilarity'";
    if (isset($_REQUEST['exportSimilarity']) and ($_REQUEST['exportSimilarity'])) {
        echo " checked";
    }
    echo ">Export similarity comparison of rubrics<br/>";

    echo "<input type='checkbox' id='stripTags' name='stripTags'";
    if (isset($_REQUEST['stripTags']) and ($_REQUEST['stripTags'])) {
        echo " checked";
    }
    echo ">Strip XML tags from text of rubrics.</input><br/>";

    echo "<input type='checkbox' id='onlyLeafCodes' name='onlyLeafCodes'";
    if (isset($_REQUEST['onlyLeafCodes']) and ($_REQUEST['onlyLeafCodes'])) {
        echo " checked";
    }
    echo ">Only export leaf codes.</input><br/>";

    echo "<input type='checkbox' id='onlyReferencedCode' name='onlyReferencedCode'";
    if (isset($_REQUEST['onlyReferencedCode']) and ($_REQUEST['onlyReferencedCode'])) {
        echo " checked";
    }
    echo ">Only export referenced code, i.e. remove the text.</input><br/>";

    echo "<input type='checkbox' id='concatRubrics' name='concatRubrics'";
    if (isset($_REQUEST['concatRubrics']) and ($_REQUEST['concatRubrics'])) {
        echo " checked";
    }
    echo ">Concatenate rubrics of the same kind.</input><br/>";

    echo "<input type='checkbox' id='emptyLine' name='emptyLine'";
    if (isset($_REQUEST['emptyLine']) and ($_REQUEST['emptyLine'])) {
        echo " checked";
    }
    echo ">Write empty line between classes.</input><br/>";

    echo "<input type='submit' id='submitExport' value='Export' name='submit'>";
    echo "</form>"; 
    echo "</div>";
}


function showRKinds($db)
{
    $rkinds = array(1);
    if (isset($_REQUEST['selectedRKinds'])) {
        $rkinds = explode(' ', trim($_REQUEST['selectedRKinds']));
    }
    echo "<div id=\"exportRKindsAll\" class=\"kinds\"><ul>";
    if ($rows = $db->rkinds->getAll()) {
        foreach ($rows as $row) {
            echo "<li id=\"" . $row['id'] . "\"";
            if (in_array($row['id'], $rkinds)) {
                echo " selected=\"selected\"";
            }
            echo ">";
            echo $row['name'] . "</li>";
        }
    }
    echo "</ul></div>";
}


function createExportCSV($db, $filename)
{
    $concat = false;
    $emptyLine = false;
    $stripTags = false;
    $onlyReferencedCode = false;
    if (isset($_REQUEST['stripTags'])) $stripTags = $_REQUEST['stripTags'];
    if (isset($_REQUEST['emptyLine'])) $emptyLine = $_REQUEST['emptyLine'];
    if (isset($_REQUEST['concatRubrics'])) $concat = $_REQUEST['concatRubrics'];
    if (isset($_REQUEST['onlyReferencedCode'])) $onlyReferencedCode = $_REQUEST['onlyReferencedCode'];
    $rkinds = explode(' ', trim($_REQUEST['selectedRKinds']));
    if (count($rkinds) > 0) {
        $query = "SELECT classes.code, rkinds.name, rubrics.id, rubrics.label FROM rubrics INNER JOIN classes ON classes.id=rubrics.class INNER JOIN rkinds ON rkinds.id=rubrics.kind WHERE (";
        $i = 0;
        while ($i < count($rkinds)) {
            if ($i > 0) $query .= " OR ";
            $query .= "rubrics.kind=" . $rkinds[$i];
            $i++;
        }
        $query .= ')';
        if (isset($_REQUEST['onlyLeafCodes']) and ($_REQUEST['onlyLeafCodes'])) {
            $query .= " AND classes.hasChildren=0";
        }
        $query .= " ORDER BY classes.code, rubrics.kind";

        if ($result = mysqli_query($db->con, $query)) {
            $file = fopen($filename, 'w');
            if ($file) {
                $prevCode = "";
                $prevKind = "";
                while ($row = mysqli_fetch_array($result)) {
                    if ($onlyReferencedCode) {
                        $label = getReferencedCode($row['label']);
                    }
                    else if ($stripTags) {
                        $label = strip_tags($row['label']);
                    }
                    else {
                        $label = $row['label'];
                    }
                    $label = str_replace('"', '\"', $label);
                    
                    // necessary to use strcmp instead of ==, because of numerical codes where 601 == 601.00
                    if ($concat and (strcmp($row['code'], $prevCode) == 0) and ($row['name'] == $prevKind)) {
                        fwrite($file, ", " . $label);
                    }
                    else {
                        if ($prevCode != "") {
                            fwrite($file, "\"\n");
                            if ($emptyLine and ($prevCode != $row['code'])) fwrite($file, "\"\",\"\",\"\"\n");
                        }
                        fwrite($file, "\"" . $row['code'] . "\","); 
                        fwrite($file, "\"" . $row['name'] . "\",");
                        fwrite($file, "\"");
                        fwrite($file, $label);
                        $prevCode = $row['code'];
                        $prevKind = $row['name'];
                    }
                }
                if ($prevCode != "") fwrite($file, "\"\n");
                fclose($file);
            }
            $result->free_result();
        }
    }
}


function createExportSimilarity($db, $filename)
{
    $rkinds = explode(' ', trim($_REQUEST['selectedRKinds']));
    $file = fopen($filename, 'w');
    if ($file) {
        if (count($rkinds) > 1) {
            $emptyLine = false;
            if (isset($_REQUEST['emptyLine'])) $emptyLine = $_REQUEST['emptyLine'];
            $query = "SELECT classes.id AS classId, classes.code, rubrics.label, rkinds.name AS rkindName FROM rubrics INNER JOIN classes ON classes.id=rubrics.class INNER JOIN rkinds ON rkinds.id=rubrics.kind WHERE (";
            $compareRubrics = 0;
            while ($compareRubrics < count($rkinds)-1) {
                if ($compareRubrics > 0) $query .= " OR ";
                $query .= "rubrics.kind=" . $rkinds[$compareRubrics];
                $compareRubrics++;
            }
            $query .= ") ORDER BY classes.code";
            if ($result = mysqli_query($db->con, $query)) {
                $icds = $result->fetch_all(MYSQLI_ASSOC);
                $result->free_result();
            }
        
            $snomeds = array();
            $code = "";
            foreach ($icds as $icd) {
                if ($icd['code'] != $code) {
                    if ($snomeds) { // check if there are any snomeds left
                        foreach ($snomeds as $snomed) {
                            if ($snomed['label'] != "") {
                                fwrite($file, "\"" . $code . "\",");
                                if ($compareRubrics > 1) fwrite($file, "\"\",");
                                fwrite($file, "\"\",");
                                fwrite($file, "\"" . strip_tags($snomed['label']) . "\",\"\"\n");
                            }
                        }
                    }
                    if ($emptyLine) {
                        fwrite($file, "\"\",");
                        if ($compareRubrics > 1) fwrite($file, "\"\",");
                        fwrite($file, "\"\",\"\",\"\"\n");
                    }
                    // get snomed rubrics
                    $code = $icd['code'];
                    $query = "SELECT classes.code, rubrics.label FROM rubrics INNER JOIN classes ON classes.id=rubrics.class WHERE rubrics.kind=" . $rkinds[$compareRubrics] . " AND rubrics.class = " . $icd['classId'];
                    if ($result = mysqli_query($db->con, $query)) {
                        $snomeds = $result->fetch_all(MYSQLI_ASSOC);
                        $result->free_result();
                    }
                }
                if ($icd['code'] == $code) {
                    $icd_label = removeReference($icd['label']);
                    if ($icd_label != "no exact corresponding class") { //ignore
                        $i = 0;
                        $lowest = -1;
                        $similar = -1;
                        $snomed_label = "";
                        while ($i < count($snomeds)) {
                            $compare_label = removeReference($snomeds[$i]['label']);
                            if ($compare_label == "no exact corresponding term") {
                                $snomeds[$i]['label'] = "";
                            }
                            else {
                                $lev = levenshtein($icd_label, $compare_label);
                                if ($lev == 0) { // exactly the same
                                    $snomed_label = $compare_label;
                                    $lowest = $lev;
                                    $similar = $i;
                                    break;
                                }
                                if (($lev < $lowest) || ($lowest < 0)) {
                                    $snomed_label = $compare_label;
                                    $lowest = $lev;
                                    $similar = $i;
                                }
                            }
                            $i++;
                        }
                        if ($snomed_label == "") {
                            $similarity = 0;
                        }
                        else {
                            $similarity = round(100 * (1 - $lowest / max(strlen($icd_label), strlen($snomed_label))), 2);
                        }
                        if ($similarity < 50) { // no snomed label found
                            fwrite($file, "\"" . $code . "\",");
                            if ($compareRubrics > 1) fwrite($file, "\"" . $icd['rkindName'] . "\",");
                            fwrite($file, "\"" . strip_tags($icd['label']) . "\",");
                            fwrite($file, "\"\",\"\"\n");
                        }
                        elseif ($icd_label != "no exact corresponding class") {
                            fwrite($file, "\"" . $code . "\",");
                            if ($compareRubrics > 1) fwrite($file, "\"" . $icd['rkindName'] . "\",");
                            fwrite($file, "\"" . strip_tags($icd['label']) . "\",");
                            fwrite($file, "\"" . strip_tags($snomeds[$similar]['label']) . "\",");
                            fwrite($file, "\"" . $similarity . "%\"\n");
                            $snomeds[$similar]['label'] = "";
                        }
                    }
                }
            }
        }
        else {
            fwrite($file, "A similarity comparison table can only be made between two rubric kinds.\n");
        }
        fclose($file);
    }
}


function removeReference($text)
{
    $result = $text;
    if ($i = strpos($text, " Id <Reference")) {
        $result = substr($text, 0, $i);
    }
    elseif ($i = strpos($text, "<Reference")) {
        $result = substr($text, 0, $i);
    }
    return strtolower(trim($result));
}


function createExportHTML($db, $filename)
{
    $file = fopen($filename, 'w');
    if ($file) {
        fwrite($file, "<!DOCTYPE html>\n");
        fwrite($file, "<html>\n");
        fwrite($file, "<head>\n");
        fwrite($file, "<title>" . $db->getClassificationName() . "</title>\n");
        fwrite($file, "<meta charset=\"UTF-8\"/>\n");
        fwrite($file, "</head>\n");
        
        $rkinds = explode(' ', trim($_REQUEST['selectedRKinds']));
        writeClassHTML($db, $file, $rkinds, 1, $db->getTopCategory());
        fwrite($file, "</html>\n");
        fclose($file);
    }
}


function writeClassHTML($db, $file, $rkinds, $depth, $class)
{
    if ($rows = $db->nodes->selectChildren($class)) {
        foreach ($rows as $row) {
            writeRubricsHTML($db, $file, $rkinds, $depth, $row['id'], $row['code']);
            writeClassHTML($db, $file, $rkinds, $depth+1, $row['id']);
        }
    }
}


function writeRubricsHTML($db, $file, $rkinds, $depth, $class, $code)
{
    if ($rows = $db->rubrics->getAllOfClass($class)) {
        $current_kind = 0;
        foreach ($rows as $row) {
            $db->rkinds->getNameAndDisplay($row['kind'], $kind, $display);
            if ($kind == PREFERRED_NAME) {
                fwrite($file, "<h" . $depth  . ">");
                fwrite($file, $code . " ");
                fwrite($file, $row['label']);
                fwrite($file, "</h" . $depth . ">\n");
            }
            elseif (in_array($row['kind'], $rkinds)) {
                if ($row['kind'] <> $current_kind) {
                    fwrite($file, "<h6>" . $display . "</h6>\n");
                    $current_kind = $row['kind'];
                }
                fwrite($file, "<p>" . $row['label'] . "</p>\n");
            }
        }
    }
}


function getReferencedCode($label)
{
    $codes = '';
    $start = stripos($label, '<Reference', 0);
    while (is_numeric($start)) {
        $start = stripos($label, '>', $start+10);
        if ($start) {
            $end = stripos($label, '<', $start+1);
            if ($end) {
                if ($codes != '') $codes .= ' ';
                $codes .= substr($label, $start+1, $end-$start-1);
                $start = $end+1;
            }
        }
        if ($start < strlen($label)) {
            $start = stripos($label, '<Reference', $start+10);
        }
        else {
            $start = false;
        }
    }
    return $codes;
}


?>

