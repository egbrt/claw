<?php
/*
    Classification Workbench
    Copyright (c) 2020-2022, WONCA ICPC-3 Foundation

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
require_once "auth/user.php";
require_once "templates/menu.php";

if (isAuthenticated($con, $user, $name, $role) and ($role == "admin")) {
    showMenu($user, $role);
    $db = new Database($con);
    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
        if ($action == "Set Language") {
            $db->globalChangeLanguage($_REQUEST['sourceLanguage'], $_REQUEST['targetLanguage']);
            showTranslator($db);
        }
        elseif ($action == "Translate") {
            $db->globalChangeWord($_REQUEST['translateRubrics'], $_REQUEST['sourceWord'], $_REQUEST['targetWord']);
            showTranslator($db);
        }
        elseif ($action == "Upload") {
            if (uploadList($file, $error)) {
                $db->changes->write('global', "Translations processed", $file, '');
                processList($db, $file);
            }
            else {
                echo $error;
            }
        }
        elseif ($action == "Download") {
            downloadWordList($db, $file);
            echo "<p>Click the file name to download: ";
            echo "<a href=\"" . $file ."\" download>" . basename($file) . "</a></p>";
        }
    }
    else {
        showTranslator($db);
    }
}
else {
    header('Location: ./index.php');
}

include "templates/footer.php";


function showTranslator($db)
{
    echo "<div id='feedbackMsg'>";
    echo "<p>Please wait, processing translations...</p>";
    echo "</div>";
    
    echo "<div id='translateSetLanguage' class='translate'>";
    echo "<h3>Set target language</h3>";
    echo "<p>Enter the ISO 2-letter code of <em>original</em> language ";
    echo "and of the language of the translation.</p>";
    echo "<p><strong>NB</strong>: You will only have to do this once when you start the translation process.</p>";
    echo "<form method='post'>";
    echo "<p>Current language: <input type=\"text\" size=\"3\" id=\"sourceLanguage\" name=\"sourceLanguage\"/";
    if (isset($_REQUEST['sourceLanguage'])) {
        echo " value=\"" . $_REQUEST['sourceLanguage'] . "\"";
    }
    echo "> translate to: <input type=\"text\" size=\"3\" id=\"targetLanguage\" name=\"targetLanguage\"/";
    if (isset($_REQUEST['targetLanguage'])) {
        echo " value=\"" . $_REQUEST['targetLanguage'] . "\"";
    }
    echo "></p>";
    echo "<input type=\"submit\" name=\"action\" value=\"Set Language\" id=\"buttonSetLanguage\" disabled/>";
    echo "</form></div>";
    
    echo "<div id='translateWord' class='translate'>";
    echo "<h3>Translate one word</h3>";
    echo "<p>Enter the word in the <em>original</em> language and its translation.</p>";
    echo "<form method='post'>";
    echo "<table>";
    echo "<tr><td>Original:</td><td><input type=\"text\" id=\"sourceWord\" name=\"sourceWord\"/></td></tr>";
    echo "<tr><td>Translation:</td><td><input type=\"text\" id=\"targetWord\" name=\"targetWord\"/></td></tr>";
    echo "<tr><td>In rubrics:</td><td><select id=\"translateRubrics\" name=\"translateRubrics\" style=\"width:100%\">";
    echo "<option value=\"-1\">All rubrics</option>";
    if ($rkinds = $db->rkinds->getAll()) {
        foreach ($rkinds as $rkind) {
            echo "<option value=\"" . $rkind['id'] . "\">" . $rkind['name'] . "</option>";
        }
    }
    echo "</select></td></tr>";
    echo "</table>";
    echo "<input type=\"submit\" name=\"action\" value=\"Translate\" id=\"buttonTranslate\" disabled/>";
    echo "</form></div>";
    
    echo "<div id='translateAllWords' class='translate'>";
    echo "<h3>Download list of all words</h3>";
    echo "<p>This writes all words from all rubrics into a file that can be used as the basis for making a ";
    echo "dictionary. After downloading the file, translate all words and upload the translations in the box on the right.</p>";
    echo "<form method='post'>";
    echo "<input id='buttonDownload' type='submit' name='action' value='Download'>";
    echo "</form></div>";
    
    echo "<div id='translateList' class='translate'>";
    echo "<h3>Select list of words to upload</h3>";
    echo "<p>The list must be in the form of a CSV (Comma Separated Value) file. The file must have two columns. ";
    echo "The first column must contain the words in the source language, the second column must contain the translations ";
    echo "in the target column.</p>";
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<p><input type='file' name='fileToUpload' id='fileToUpload'></p>";
    echo "<input id='buttonUpload' type='submit' name='action' value='Upload' disabled>";
    echo "</form></div>";
    
    echo "<script>";
    echo "$('#feedbackMsg').hide();";
    echo "$('#sourceWord').keyup(function() {";
    echo "$('#buttonTranslate').attr('disabled', ($('#sourceWord').val() == ''))";
    echo "});";
    echo "$('#sourceLanguage').keyup(function() {";
    echo "$('#buttonSetLanguage').attr('disabled', (($('#sourceLanguage').val() == '') || ($('#targetLanguage').val() == '')))";
    echo "});";
    echo "$('#targetLanguage').keyup(function() {";
    echo "$('#buttonSetLanguage').attr('disabled', (($('#sourceLanguage').val().length == '') || ($('#targetLanguage').val() == '')))";
    echo "});";
    
    echo "$('#buttonDownload').click(function() {";
    echo "$('#feedbackMsg').html('<p>Please wait, generating list of words...</p>');";
    echo "$('#feedbackMsg').show();";
    echo "$('div.translate').hide();";
    echo "});";

    echo "$('#fileToUpload').change(function() {";
    echo "$('#buttonUpload').attr('disabled', ($('#fileToUpload').val() == ''))";
    echo "});";
    echo "$('#buttonUpload').click(function() {";
    echo "$('#feedbackMsg').show();";
    echo "$('div.translate').hide();";
    echo "});";
    echo "</script>";
}


function uploadList(&$file, &$error)
{
    $file = '';
    $error = '';
    $valid = false;
    if (basename($_FILES["fileToUpload"]["name"])) {
        $dir = "uploads/";
        $file = $dir . basename($_FILES["fileToUpload"]["name"]);
        $type = strtolower(pathinfo($file,PATHINFO_EXTENSION));
        if (file_exists($file)) {
            unlink($file);
        }
        if (!file_exists($file)) {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $file)) {
                $valid = true;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        }
        else {
            $error = "file already exists, and could not be deleted.";
        }
    }
    else {
        $error = "Please choose a file first.";
    }
    return $valid;
}


function processList($db, $file)
{
    $data = fopen($file,"r");
    if ($data) {
        while ($word = fgetcsv($data)) {
            if (count($word) > 1) {
                $db->globalChangeWord(-1, $word[0], $word[1]);
            }
        }
        fclose($data);
    }
}


function downloadWordList($db, &$filename)
{
    $filename = './downloads/words.csv';
    if (file_exists($filename)) {
        unlink($filename);
    }
    $file = fopen($filename, 'w');
    if ($file) {
        if ($rows = $db->rubrics->getAll()) {
            $seen= array();
            $delimitors = " []\t()-,\"<>;:.?!'’/\\";
            foreach ($rows as $row) {
                $label = removeReferences($row['label']);
                $word = strtok($label, $delimitors);
                while ($word !== false) {
                    $word = trim($word, "");
                    if (strlen($word) < 2) {
                        // ignore one letter words
                    }
                    elseif (is_numeric($word[0])) {
                        // ignore word starting with number
                    }
                    elseif (is_numeric($word)) {
                        // ignore numbers
                    }
                    elseif ($db->nodes->find($word)) {
                        // ignore codes
                    }
                    elseif (array_key_exists($word, $seen)) {
                        $seen[$word] += 1;
                    }
                    else {
                        $seen[$word] = 1;
                    }
                    $word = strtok($delimitors);
                }
            }
        }
        
        foreach ($seen as $word => $number) {
            fwrite($file, "\"" . $word);
            fwrite($file, "\"," . $number);
            //fwrite($file, "\",\"" . $db->nodes->get($row['class'])->code); 
            fwrite($file, "\n");
        }

        fclose($file);
    }
}


function removeReferences($label)
{
    $words = explode(" ", strtolower($label));
    $label = '';
    $keep = true;
    foreach ($words as $word) {
        if (substr($word, 0, 10) == "<reference") {
            $keep = false;
        }
        elseif (!$keep) {
            $keep = (substr($word, 0, 12) == "</reference>");
        }
        else {
            if ($label != '') $label .= ' ';
            $label .= $word;
        }
    }
    return $label;
}

?>
