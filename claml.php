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
require_once "templates/menu.php";
require_once "auth/user.php";
require_once "classes/database.php";
require_once "classes/uploader.php";
require_once "classes/importer.php";
require_once "classes/downloader.php";

if (isAuthenticated($con, $user, $name, $role) and ($role == "admin")) {
    showMenu($user, $role);
    if (isset($_REQUEST['submit'])) {
        $action = $_REQUEST['submit'];
        $db = new Database($con);
        if ($action == "EmptyDBase") {
            $db->create();
            header('Location: ./index.php');
        }
        elseif ($action == 'Upload') {
            $uploader = new Uploader();
            if ($uploader->uploadFile()) {
                if ((isset($_REQUEST['isExtension'])) and ($_REQUEST['isExtension'])) {
                    $valid = $uploader->parseExtension($db);
                }
                else {
                    $valid = $uploader->parseClaML($db);
                }
                if ($valid) {
                    header('Location: ./index.php');
                }
                else {
                    showFeedback($uploader->errors);
                }
            }
            else {
                selectUpload($uploader->errors);
            }
        }
        elseif ($action == 'Import') {
            $importer = new Importer();
            if ($importer->uploadFile()) {
                $replace = ((isset($_REQUEST['replaceCSV'])) and ($_REQUEST['replaceCSV']));
                $valid = $importer->parseCSV($db, $replace);
                if ($valid) {
                    header('Location: ./index.php');
                }
                else {
                    showFeedback($importer->errors);
                }
            }
            else {
                selectUpload($importer->errors);
            }
        }
        elseif ($action == 'Download') {
            $downloader = new Downloader();
            $downloader->includeHiddenRubrics = ((isset($_REQUEST['includeHidden'])) and ($_REQUEST['includeHidden']));
            $downloader->createClaML($db);
            $downloader->writeChangeLog($db);
            echo "<p>Click the file name to download: ";
            echo "<a href=\"" . $downloader->claml ."\" download>" . basename($downloader->claml) . "</a></p>";
            echo "<p>The changelog can be downloaded here: ";
            echo "<a href=\"" . $downloader->changelog ."\" download>" . basename($downloader->changelog) . "</a></p>";
            $downloader->writeChangeLogAsHTML($db);
            echo "<p>The changelog in HTML format can be downloaded here: ";
            echo "<a href=\"" . $downloader->changelog ."\" download>" . basename($downloader->changelog) . "</a></p>";
        }
        else { // cancel
            header('Location: ./index.php');
        }
    }
    else {
        selectUpload('');        
    }
}
else {
    header('Location: ./index.php');
}
include "templates/footer.php";


function selectUpload($error)
{
    if ($error == "") {
        $error = "The classification source must be available in ClaML format.";
    }

    echo "<div id='feedbackMsg' style='display:none'>";
    echo "<p>Please wait, reading ClaML file...</p>";
    echo "</div>";
    
    echo "<div class='claml emptyDBase' style='display:none'>";
    echo "<h3>Create empty database</h3>";
    echo "<p>This removes the currently loaded classification and creates an empty database.</p>";
    echo "<form method='post'>";
    echo "<input type='submit' value='EmptyDBase' name='submit'>";
    echo "</form>"; 
    echo "</div>";

    echo "<div class='claml uploadClaML' style='display:none'>";
    echo "<h3>Select classification source to upload</h3>";
    echo "<p id='errorMsg'/>";
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<p><input type='file' name='fileToUpload' id='fileToUpload'></p>";
    echo "<p><input type='checkbox' name='isExtension' id='isExtension'>Import as extension, i.e. add to existing classification.</checkbox></p>";
    echo "<input id='uploadButton' type='submit' value='Upload' name='submit'>";
    echo "</form>";
    echo "</div>";
    
    echo "<div class='claml downloadClaML' style='display:none'>";
    echo "<h3>Download ClaML</h3>";
    echo "<p>This writes the contents of the database into a ClaML file. Your download should start automatically.</p>";
    echo "<form method='post'>";
    echo "<p><input type='checkbox' name='includeHidden' id='includeHidden' checked>Include hidden rubrics in export.</checkbox></p>";
    echo "<input id='downloadButton' type='submit' value='Download' name='submit'>";
    echo "</form>";
    echo "</div>";

    echo "<div class='claml uploadCSV' style='display:none'>";
    echo "<h3>Select rubrics to upload</h3>";
    echo "<p>Imports rubrics from a CSV file with three columns, like this:<br/>\"code\", \"rubrickind\", \"rubric text, and escape the \\\"apostrophes\\\"\".</p>";
    echo "<p id='errorMsg'/>";
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<p><input type='file' name='csvToUpload' id='csvToUpload'></p>";
    echo "<p><input type='checkbox' name='replaceCSV' id='replaceCSV'>Replace existing rubrics of same kind as in upload.</checkbox></p>";
    echo "<input id='importButton' type='submit' value='Import' name='submit'>";
    echo "</form>";
    echo "</div>";
    
    echo "<script>";
    echo "$('#errorMsg').html('" . $error . "');";
    echo "$('div.claml').show();";
    echo "$('#uploadButton').attr('disabled', true);";
    echo "$('#fileToUpload').change(function() {";
    echo "$('#uploadButton').attr('disabled', ($('#fileToUpload').val() == ''))";
    echo "});";
    echo "$('#importButton').attr('disabled', true);";
    echo "$('#csvToUpload').change(function() {";
    echo "$('#importButton').attr('disabled', ($('#csvToUpload').val() == ''))";
    echo "});";

    echo "$('#uploadButton').click(function() {";
    echo "$('#feedbackMsg').show();";
    echo "$('div.claml').hide();";
    echo "});";
    
    echo "$('#importButton').click(function() {";
    echo "$('#feedbackMsg').html('<p>Please wait, importing CSV file...</p>');";
    echo "$('#feedbackMsg').show();";
    echo "$('div.claml').hide();";
    echo "});";

    echo "$('#downloadButton').click(function() {";
    echo "$('#feedbackMsg').html('<p>Please wait, generating ClaML file...</p>');";
    echo "$('#feedbackMsg').show();";
    echo "$('div.claml').hide();";
    echo "});";
    echo "</script>";
}


function showFeedback($errors)
{
    echo "The file could not be (completely) parsed, because it contains the following errors:<br/>";
    echo $errors;
}



?>
