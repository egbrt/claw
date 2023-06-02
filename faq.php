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

if (isAuthenticated($con, $user, $name, $role)) {
    showMenu($user, $role);
?>
    <div id="faq">
<?php
    if ($role == "admin") {
?>
        <h3 class="switch" for="sectionClassification">Classification</h3>
        <div id="sectionClassification" style="display:none">
        <h4 class="switch" for="createDBase">How to create a new classification</h3>
        <ul id="createDBase" style="display:none">
            <li>Select <em>ClaML</em> in the left menu</li>
            <li>Click the button <em>EmptyDBase</em> in the upper left box</li>
            <li>Go to the <em>Editor</em> and create your classification</li>
        </ul>

        <h4 class="switch" for="downloadClaML">How to download the current classification</h3>
        <ul id="downloadClaML" style="display:none">
            <li>Select <em>ClaML</em> in the left menu</li>
            <li>Optionally include (default) or exclude the hidden rubrics in the generated ClaML file. See <em>Meta Model</em> section about how to create hidden rubrics.
            </li>
            <li>Click the button <em>Download</em> in the upper right box</li>
            <li>Wait a moment until the ClaML file is generated</li>
            <li>Click the file name of the ClaML file to download the classification</li>
            <li>Optionally also download the changelog</li>
        </ul>

        <h4 class="switch" for="uploadClaML">How to upload a ClaML file</h3>
        <ul id="uploadClaML" style="display:none">
            <li>Select <em>ClaML</em> in the left menu</li>
            <li>Click the button <em>Choose File</em> in the lower left box</li>
            <li>Select the ClaML file on your computer</li>
            <li>Click the button <em>Upload</em></li>
        </ul>
        </div>
        <hr/>
<?php
    }
    if (($role == "admin") || ($role == "editor")) {
?>
        <h3 class="switch" for="sectionMetaModel">Meta Model</h3>
        <div id="sectionMetaModel" style="display:none">
        <p>The <em>Meta Model</em> defines which kind of classes and rubrics the classification may contain.</p>

        <h4 class="switch" for="addCKind">How to add a classkind</h4>
        <ul id="addCKind" style="display:none">
            <li>Select <em>Meta Model</em> in the left menu</li>
            <li>Enter the name for the new classkind in the lower left box</li>
            <li>Optionally enter a text to display for the classkind</li>
            <li>Click the button <em>Add</em></li>
        </ul>
        
        <h4 class="switch" for="addRKind">How to add a rubrickind</h4>
        <ul id="addRKind" style="display:none">
            <li>Select <em>Meta Model</em> in the left menu</li>
            <li>Enter the name for the new rubrickind in the lower right box</li>
            <li>Optionally enter a text to display for the rubrickind</li>
            <li>Click the button <em>Add</em></li>
        </ul>
        
        <h4 class="switch" for="hideRubric">How to hide rubrics from the browser</h4>
        <ul id="hideRubric" style="display:none">
            <li>Select <em>Meta Model</em> in the left menu</li>
            <li>Select the rubric kind of the rubrics that you want to hide</li>
            <li>Put a '.' as first character of the name of rubric kind</li>
            <li>Click the button <em>Change</em></li>
        </ul>
            </div>
        <hr/>
        
        <h3 class="switch" for="sectionExport">Export</h3>
        <ul id="sectionExport" style="display:none">
            <li>Select <em>Export</em> in the left menu</li>
            <li>Select the rubrickinds of the rubrics that should be exported</li>
            <li>Select the format of the export: HTML or CSV</li>
            <li>In case of CSV export, check the appropriate box if you only want to export:
            <ul>
                <li>leaf codes (i.e. codes without children)</li>
                <li>referenced code without the text</li>
            </ul>
            <li>Click the button <em>Export</em></li>
            <li>Wait a moment until the box with the file name appears</li>
            <li>Click the file name to download the file</li>
        </ul>
        <hr/>
        
<?php
    }
    if (($role == "admin") || ($role == "editor") || ($role== "writer")) {
?>
        <h3 class="switch" for="sectionEditor">Editor</h3>
        <div id="sectionEditor" style="display:none">
        <h4 class="switch" for="addRubric">How to add a rubric</h4>
        <ul id="addRubric" style="display:none">
            <li>Select the class where a rubric should be added</li>
            <li>Type the new rubric under the appropriate rubrickind</li>
            <li>If needed, first click the appropriate rubrickind in the upper-right menu</li>
            <li>Click the button <em>Save changes</em></li>
        </ul>
        
        <h4 class="switch" for="removeRubric">How to remove a rubric</h4>
        <ul id="removeRubric" style="display:none">
            <li>Just remove the rubric in the editor</li>
            <li>Click the button <em>Save changes</em></li>
        </ul>
        
        <h4 class="switch" for="addClass">How to add a class</h4>
        <ul id="addClass" style="display:none">
            <li>Select the parent class for the new class</li>
            <li>Click <em>_subclass</em> in the upper-right menu</li>
            <li>Type the code for the new subclass</li>
            <li>Click the button <em>Save changes</em></li>
        </ul>
        
        <h4 class="switch" for="removeClass">How to remove a class</h4>
        <ul id="removeClass" style="display:none">
            <li>Select the class to be removed</li>
            <li>Click <em>_code</em> in the upper-right menu</li>
            <li>Click <em>_code := DELETE</em> in the upper-right menu</li>
            <li>Click the button <em>Save changes</em></li>
        </ul>
        
        <h4 class="switch" for="sortClasses">How to sort sub-classes</h4>
        <ul id="sortClasses" style="display:none">
            <li>Select the class for which the sub-classes should be sorted</li>
            <li>Click <em>_code</em> in the upper-right menu</li>
            <li>Click <em>_code := SORTSUBS</em> in the upper-right menu</li>
            <li>Click the button <em>Save changes</em></li>
        </ul>

        <h4 class="switch" for="changeCKind">How to change the classkind of a class</h4>
        <ul id="changeCKind" style="display:none">
            <li>Click <em>_classkind</em> in the upper-right menu</li>
            <li>Click the desired classkind from the upper-right menu</li>
            <li>Alternatively, type the name of the classkind</li>
            <li>Click the button <em>Save changes</em></li>
        </ul>
        </div>
        <hr/>

<?php if ($role == "admin") { ?>
        <h3 class="switch" for="sectionTranslate">Translate</h3>
        <div id="sectionTranslate" style="display:none">
        <h4 class="switch" for="changeLanguage">Change language of all rubrics</h4>
        <ul id="changeLanguage" style="display:none">
            <li>Select <em>Translate</em> in the left menu</li>
            <li>Enter the ISO 2-letter code of the current language (e.g. 'en')</li>
            <li>and enter the code for the target language</li>
            <li>Click the button <em>Set language</em> in the upper right box</li>
        </ul>

        <h4 class="switch" for="changeOneWord">Globally translate one word</h4>
        <ul id="changeOneWord" style="display:none">
            <li>Select <em>Translate</em> in the left menu</li>
            <li>In the <em>Translate one word</em> box:
                <ul>
                    <li>enter original word</li>
                    <li>enter the translation</li>
                    <li>Click the button <em>Translate</em></li>
                </ul>
            </li>
        </ul>
        
        <h4 class="switch" for="downloadAllWords">Download list of all words</h4>
        <ul id="downloadAllWords" style="display:none">
            <li>Select <em>Translate</em> in the left menu</li>
            <li>Click the button <em>Download</em> in the lower left box</li>
        </ul>
        
        <h4 class="switch" for="uploadAllWords">Upload list of translated words</h4>
        <ul id="uploadAllWords" style="display:none">
            <li>Select <em>Translate</em> in the left menu</li>
            <li>Click the button <em>Choose File</em> and select the file on your computer with the translations</li>
            <li>Click the button <em>Upload</em> in the lower right box</li>
        </ul>
        
        <h4 class="switch" for="followOriginal">Check english original</h4>
        <div id="followOriginal" style="display:none">
            <p>While translating the ICPC-3 in your own language, you might want to keep an eye on the original
            english version. An option is to open the ICPC-3 browser with the original english version next to the
            classification workbench. When you use the button <em>Start original browser</em> on the Editor page
            the ICPC-3 browser is opened and follows the code on which you are working.</p>
            <p>If your monitor is large enough you can have a window with the classification workbench and the 
            ICPC-3 browser side by side. Another setup is to have two monitors side by side.</p>
        </div>
        </div>
        <hr/>
<?php
}
?>

        <h3 class="switch" for="sectionStats">Statistics</h3>
        <ul id="sectionStats" style="display:none">
            <li>Select <em>Statistics</em> in the left menu</li>
            <li>On the left is a list with the classkinds and how many of each classkind is present in the classification</li>
            <li>On the right is the same for the rubrickinds</li>
        </ul>
        <hr/>

        <h3 class="switch" for="sectionChangeLog">Changelog</h3>
        <ul id="sectionChangeLog" style="display:none">
            <li>Select <em>Changelog</em> in the left menu</li>
            <li>When, who and what changed is shown</li>
        </ul>
        <hr/>

        <h3 class="switch" for="sectionSettings">Settings</h3>
        <div id="sectionSettings" style="display:none">
        <h4 class="switch" for="changePassword">How to change your password</h4>
        <ul id="changePassword" style="display:none">
            <li>Select <em>Settings</em> in the left menu</li>
            <li>Enter your new password</li>
            <li>Click the button <em>Change</em></li>
        </ul>
        </div>
        <hr/>
<?php
    }
?>
    </div>
<?php
    mysqli_close($con);
}
?>
