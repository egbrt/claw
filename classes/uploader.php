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

require_once "classes/debugger.php";


class Uploader {
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
        if (basename($_FILES["fileToUpload"]["name"])) {
            $dir = "uploads/";
            $this->file = $dir . basename($_FILES["fileToUpload"]["name"]);
            $type = strtolower(pathinfo($this->file,PATHINFO_EXTENSION));
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            if (!file_exists($this->file)) {
                if ($type == "xml") {
                    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $this->file)) {
                        $valid = true;
                    } else {
                        $this->errors = "Sorry, there was an error uploading your file.";
                    }
                }
                else {
                    $this->errors = "Only ClaML files allowed.";
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
    

    function parseClaML($db)
    {
        $this->errors = '';
        $valid = true;
        $claml = new XMLReader;
        $claml->open($this->file);
        libxml_use_internal_errors(true);
        if ($this->isClaML($claml)) {
            $db->clearTables();
            while ($claml->read()) {
                if (($claml->depth == 1) and ($claml->nodeType == XMLReader::ELEMENT)) {
                    if ($claml->name == "Title") {
                        $this->addTitle($db, $claml);
                    }
                    elseif ($claml->name == "Authors") {
                        $this->addAuthors($db, $claml);
                    }
                    elseif ($claml->name == "ClassKinds") {
                        $this->addClassKinds($db, $claml);
                    }
                    elseif ($claml->name == "RubricKinds") {
                        $this->addRubricKinds($db, $claml);
                    }
                    elseif ($claml->name == "Modifier") {
                        $this->addModifier($db, $claml);
                    }
                    elseif ($claml->name == "ModifierClass") {
                        $this->addModifierClass($db, $claml);
                    }
                    elseif ($claml->name == "Class") {
                        $this->addClass($db, $claml);
                    }
                }
            }
        }
        else {
            $this->errors = "This is not a valid ClaML file.<br/>";
            $valid = false;
        }
        foreach(libxml_get_errors() as $error) {
            $this->errors .= 'At line: ' . $error->line . ', column: ' . $error->column . ': ' . $error->message . '<br/>';
            $valid = false;
        }
        libxml_use_internal_errors(false);
        $claml->close();
        return $valid;
    }
    
    
    function parseExtension($db)
    {
        $this->errors = '';
        $valid = true;
        $claml = new XMLReader;
        $claml->open($this->file);
        libxml_use_internal_errors(true);
        if ($this->isClaML($claml)) {
            while ($claml->read()) {
                if (($claml->depth == 1) and ($claml->nodeType == XMLReader::ELEMENT)) {
                    if ($claml->name == "Title") {
                        //
                    }
                    elseif ($claml->name == "ClassKinds") {
                        //addClassKinds($db, $claml);
                    }
                    elseif ($claml->name == "RubricKinds") {
                        //addRubricKinds($db, $claml);
                    }
                    elseif ($claml->name == "Modifier") {
                        //addModifier($db, $claml);
                    }
                    elseif ($claml->name == "ModifierClass") {
                        //addModifierClass($db, $claml);
                    }
                    elseif ($claml->name == "Class") {
                        $this->addExtensionClass($db, $claml);
                    }
                }
            }
        }
        else {
            $this->errors = "This is not a valid ClaML file.<br/>";
            $valid = false;
        }
        foreach(libxml_get_errors() as $error) {
            $this->errors .= 'At line: ' . $error->line . ', column: ' . $error->column . ': ' . $error->message . '<br/>';
            $valid = false;
        }
        libxml_use_internal_errors(false);
        $claml->close();
        return $valid;
    }
    
    
    private function isClaML($claml)
    {
        $valid = false;
        while (!$valid and $claml->read() and ($claml->depth == 0)) {
            $valid = (($claml->nodeType == XMLReader::ELEMENT) and ($claml->name == 'ClaML'));
        }
        return $valid;
    }
    
    
    private function addTitle($db, $class)
    {
        $name = $class->getAttribute("name");
        $date = $class->getAttribute("date");
        $version = $class->getAttribute("version");
        $db->setClassificationName($name, $date, $version);
    }
    
    
    private function addAuthors($db, $authors)
    {
        $valid = true;
        while ($valid and $authors->read()) {
            if ($authors->nodeType == XMLReader::END_ELEMENT) {
                if ($authors->name == "Authors") {
                    $valid = false;
                }
            }
            elseif ($authors->nodeType == XMLReader::ELEMENT) {
                // okay
            }
            elseif ($authors->nodeType == XMLReader::TEXT) {
                $db->rubrics->insert(0, 101, DEFAULT_LANGUAGE, $authors->value);
            }
        }
    }
    
    
    private function addModifier($db, $modifier)
    {
        $code = $modifier->getAttribute("code");
        $classId = $db->nodes->insert($db->ckinds->getId('modifier'), $code);
        $this->addSuperClass($db, $classId, TOPMODIFIER);
        
        $valid = true;
        while ($valid and $modifier->read()) {
            if ($modifier->nodeType == XMLReader::END_ELEMENT) {
                if ($modifier->name == "Modifier") {
                    $valid = false;
                }
            }
            elseif ($modifier->nodeType == XMLReader::ELEMENT) {
                if ($modifier->name == "Rubric") {
                    $rkind = $modifier->getAttribute("kind");
                }
                elseif ($modifier->name == "Label") {
                    $language = $modifier->getAttribute("xml:lang");
                    $db->rubrics->insert($classId, $db->rkinds->getId($rkind), $language, $this->parseLabel($modifier));
                }
            }
            elseif ($modifier->nodeType == XMLReader::TEXT) {
                $label = $modifier->value;
            }
        }
    }
    
    
    private function addModifierClass($db, $submod)
    {
        $modifier = $submod->getAttribute("modifier");
        $code = $modifier . ':' . $submod->getAttribute("code");
        $classId = $db->nodes->insert($db->ckinds->getId('modifier'), $code);
        $this->addSuperClass($db, $classId, $modifier);
        
        $valid = true;
        while ($valid and $submod->read()) {
            if ($submod->nodeType == XMLReader::END_ELEMENT) {
                if ($submod->name == "ModifierClass") {
                    $valid = false;
                }
            }
            elseif ($submod->nodeType == XMLReader::ELEMENT) {
                if ($submod->name == "Rubric") {
                    $rkind = $submod->getAttribute("kind");
                }
                elseif ($submod->name == "Label") {
                    $language = $submod->getAttribute("xml:lang");
                    $db->rubrics->insert($classId, $db->rkinds->getId($rkind), $language, $this->parseLabel($submod));
                }
            }
            elseif ($submod->nodeType == XMLReader::TEXT) {
                $label = $submod->value;
            }
        }
    }
    
    
    private function addClass($db, $class)
    {
        $code = $class->getAttribute("code");
        $kind = $class->getAttribute("kind");
        $addBelowTop = true;
        $id = $db->nodes->import($db->ckinds->getId($kind), $code);
        
        $valid = true;
        $label = "";
        while ($valid and $class->read()) {
            if ($class->nodeType == XMLReader::END_ELEMENT) {
                if ($class->name == "Class") {
                    $valid = false;
                }
            }
            elseif ($class->nodeType == XMLReader::ELEMENT) {
                if ($class->name == "SuperClass") {
                    $addBelowTop = false;
                }
                elseif ($class->name == "SubClass") {
                    $sub = $class->getAttribute("code");
                    $this->addSubClass($db, $id, $sub);
                }
                elseif ($class->name == "ModifiedBy") {
                    $modifier = $db->nodes->find($class->getAttribute("code"));
                    $db->modders->insert($id, $modifier->id);
                }
                elseif ($class->name == "Rubric") {
                    $rkind = $class->getAttribute("kind");
                }
                elseif ($class->name == "Label") {
                    $language = $class->getAttribute("xml:lang");
                    $db->rubrics->insert($id, $db->rkinds->getId($rkind), $language, $this->parseLabel($class));
                }
            }
        }
        if ($addBelowTop) $this->addSuperClass($db, $id, TOPCATEGORY);
    }
    
    
    private function addExtensionClass($db, $class)
    {
        $code = $class->getAttribute("code");
        $kind = $class->getAttribute("kind");
        $addBelowTop = true;
        $id = $db->nodes->import($db->ckinds->getId($kind), $code);
        
        $valid = true;
        $label = "";
        while ($valid and $class->read()) {
            if ($class->nodeType == XMLReader::END_ELEMENT) {
                if ($class->name == "Class") {
                    $valid = false;
                }
            }
            elseif ($class->nodeType == XMLReader::ELEMENT) {
                if ($class->name == "SuperClass") {
                    $super = $class->getAttribute("code");
                    $this->addSuperClass($db, $id, $super);
                    $addBelowTop = false;
                }
                elseif ($class->name == "SubClass") {
                    $sub = $class->getAttribute("code");
                    $this->addSubClass($db, $id, $sub);
                }
                elseif ($class->name == "ModifiedBy") {
                    $modifier = $db->nodes->find($class->getAttribute("code"));
                    $db->modders->insert($id, $modifier->id);
                }
                elseif ($class->name == "Rubric") {
                    $rkind = $class->getAttribute("kind");
                }
                elseif ($class->name == "Label") {
                    $language = $class->getAttribute("xml:lang");
                    $label = $this->parseLabel($class);
                    $db->rubrics->insert($id, $db->rkinds->getId($rkind), $language, $label);
                }
            }
        }
        if ($addBelowTop) $this->addSuperClass($db, $id, TOPCATEGORY);
    }
    
    
    private function parseLabel($claml)
    {
        //$label = $claml->readInnerXml(); same as below, but below more flexibility for future extensions...
        
        $label = '';
        $valid = true;
        while ($valid and $claml->read()) {
            if ($claml->nodeType == XMLReader::END_ELEMENT) {
                if ($claml->name == "Label") {
                    $valid = false;
                }
                else {
                    $label .= '</' . $claml->name . '>';
                }
            }
            elseif ($claml->nodeType == XMLReader::ELEMENT) {
                $label .= '<' . $claml->name;
                if ($claml->hasAttributes) {
                    if ($claml->moveToFirstAttribute()) {
                        $label .= ' ' . $claml->name . '="' . $claml->value . '"';
                        while ($claml->moveToNextAttribute()) {
                            $label .= ' ' . $claml->name . '="' . $claml->value . '"';
                        }
                    }
                    $claml->moveToElement();
                }
                if ($claml->isEmptyElement) $label .= '/';
                $label .= '>';
            }
            elseif ($claml->nodeType == XMLReader::TEXT) {
                $label .= $claml->value;
            }
        }
        return $label;
    }
    
    
    private function addSubClass($db, $parent, $subCode)
    {
        $subNode = $db->nodes->import(CATEGORY_ID, $subCode);
        $db->links->insert($subNode, $parent);
        $db->nodes->setHasChildren($parent);
        $db->nodes->setHasParent($subNode);
    }
    
    
    private function addSuperClass($db, $class, $parent)
    {
        if ($node = $db->nodes->find($parent)) {
            $db->links->insert($class, $node->id);
            $db->nodes->setHasChildren($node->id);
            $db->nodes->setHasParent($class);
        }
        else {
            $debugger = new Debugger();
            $debugger->write("uploader->addSuperClass, could not find: " . $parent);
        }
    }
    
    
    private function addClassKinds($db, $ckinds)
    {
        $valid = true;
        $name = "";
        $label = "";
        $lang = DEFAULT_LANGUAGE;
        while ($valid and $ckinds->read()) {
            if ($ckinds->nodeType == XMLReader::END_ELEMENT) {
                if ($ckinds->name == "ClassKinds") {
                    $valid = false;
                }
                elseif ($ckinds->name == "ClassKind") {
                    $db->ckinds->insert($name, $label);
                    $name = "";
                    $label = "";
                    $lang = DEFAULT_LANGUAGE;
                }
            }
            elseif ($ckinds->nodeType == XMLReader::ELEMENT) {
                if ($ckinds->name == "ClassKind") {
                    if ($name != "") {
                        $db->ckinds->insert($name, $label);
                        $name = "";
                        $label = "";
                        $lang = DEFAULT_LANGUAGE;
                    }
                    $name = $ckinds->getAttribute("name");
                }
                elseif ($ckinds->name == "Display") {
                    $lang = $ckinds->getAttribute("xml:lang");
                }
            }
            elseif ($ckinds->nodeType == XMLReader::TEXT) {
                $label = $ckinds->value;
            }
        }
    }
    
    
    private function addRubricKinds($db, $rkinds)
    {
        $valid = true;
        $name = "";
        $label = "";
        $lang = DEFAULT_LANGUAGE;
        while ($valid and $rkinds->read()) {
            if ($rkinds->nodeType == XMLReader::END_ELEMENT) {
                if ($rkinds->name == "RubricKinds") {
                    $valid = false;
                }
                elseif ($rkinds->name == "RubricKind") {
                    $db->rkinds->insert($name, $label);
                    $name = "";
                    $label = "";
                    $lang = DEFAULT_LANGUAGE;
                }
            }
            elseif ($rkinds->nodeType == XMLReader::ELEMENT) {
                if ($rkinds->name == "RubricKind") {
                    if ($name != "") {
                        $db->rkinds->insert($name, $label);
                        $name = "";
                        $label = "";
                        $lang = DEFAULT_LANGUAGE;
                    }
                    $name = $rkinds->getAttribute("name");
                }
                elseif ($rkinds->name == "Display") {
                    $lang = $rkinds->getAttribute("xml:lang");
                }
            }
            elseif ($rkinds->nodeType == XMLReader::TEXT) {
                $label = $rkinds->value;
            }
        }
    }
}
?>
