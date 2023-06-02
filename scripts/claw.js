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

import {TouchMenu, closeMenu} from './touchMenu.js';
import {currentMode, currentCode, currentClassKind, showRelevantCommands} from './browser.js';

const CODE = '_code';
const CKIND = '_classkind';
const ADDSUBCLASS = '_addSubclass';
const DELSUBCLASS = '_deleteSubclass';
const SORTSUBS = "_sortSubclasses";
const MODIFIER = '_modifier';
const REMMODIFIER = '_removeModifier';

$(function() {
    TouchMenu('#menu', '#menuButton');
    
    // handle selection of class and rubric kinds
    $('.kinds ul li').click(function() {
        var text = $('#'+this.id).html();
        var parts = text.split(':');
        if (this.id.startsWith('ckind')) {
            $('#ckind_id').val(this.id.substr(6));
            $('#ckind_name').val(parts[0]);
            $('#ckind_display').val(parts[1]);
            $('#ckind_change').attr('disabled', false);
            $('#ckind_delete').attr('disabled', false);
        }
        else {
            $('#rkind_id').val(this.id.substr(6));
            $('#rkind_name').val(parts[0]);
            $('#rkind_display').val(parts[1]);
            $('#rkind_change').attr('disabled', false);
            $('#rkind_delete').attr('disabled', false);
        }
    });
    
    $('#ckind_name').keyup(function() {
        $('#ckind_add').attr('disabled', ($('#ckind_name').val() == ''));
    });
    
    $('#rkind_name').keyup(function() {
        $('#rkind_add').attr('disabled', ($('#rkind_name').val() == ''));
    });
    
    
    // handle click on rubric kind in editor
    $('.editorCommands ul li').click(function() {
        if (this.id) {
            var text = $('#textEditor').val();
            if (this.id.startsWith('ckind_')) {
                text = setInstruction(text, CKIND, $('#'+this.id).html());
                $('#allowedCKinds').hide(250);
            }
            else if (this.id == 'meta_1') {
                text = setInstruction(text, CODE, currentCode);
            }
            else if (this.id == 'meta_2') {
                $('#allowedCKinds').toggle(250);
                text = setInstruction(text, CKIND, currentClassKind);
            }
            else if (this.id == 'meta_8') {
                $('#subCommands').toggle(250);
            }
            else if (this.id == 'meta_3') {
                text += '\n' + ADDSUBCLASS + ' := ';
            }
            else if (this.id == 'meta_4') {
                text += '\n' + DELSUBCLASS + ' := ';
            }
            else if (this.id == 'meta_5') {
                text += '\n' + MODIFIER + ' := ';
            }
            else if (this.id == 'meta_6') {
                text += '\n' + REMMODIFIER + ' := ';
            }
            else if (this.id == 'meta_7') {
                text += '\n' + SORTSUBS;
            }
            else {
                text += '\n' + $('#'+this.id).html() + '\n';
            }
            $('#textEditor').val(text);
            $('#textEditor').focus();
        }
    });

    // handle click on user in changelog
    $('#changeLogUsers ul li').click(function() {
        if (this.id) {
            filterLog(this.id);
        }
    });
    
    // check if this is browser for user with reader rights
    if (!$('#editor').length) $('#preview').show();
    
    // handle click on viewer options
    $('#viewerOptions ul li').click(function() {
        if (this.id == 'buttonPreview') {
            showRelevantCommands('preview');
            $('#buttonEditor').attr('selected', false);
            $('#buttonPreview').attr('selected', true);
            $('#buttonComments').attr('selected', false);
            $('#editor').hide();
            $('#preview').show();
            $('#comments').hide();
        }
        else if (this.id == "buttonEditor") {
            showRelevantCommands('editor');
            $('#buttonEditor').attr('selected', true);
            $('#buttonPreview').attr('selected', false);
            $('#buttonComments').attr('selected', false);
            $('#editor').show();
            $('#preview').hide();
            $('#comments').hide();
        }
        else if (this.id == "buttonComments") {
            showRelevantCommands('comments');
            $('#buttonEditor').attr('selected', false);
            $('#buttonPreview').attr('selected', false);
            $('#buttonComments').attr('selected', true);
            $('#editor').hide();
            $('#preview').hide();
            $('#comments').show();
        }
    });

    // handle export
    $('#exportHTML').click(function() {
        if ($('#exportHTML').is(':checked')) {
            $('#exportSimilarity').prop('checked', false);
            $('#exportSimilarity').attr('disabled', true);
            $('#onlyLeafCodes').prop('checked', false);
            $('#onlyLeafCodes').attr('disabled', true);
            $('#onlyReferencedCode').prop('checked', false);
            $('#onlyReferencedCode').attr('disabled', true);
            $('#emptyLine').prop('checked', false);
            $('#emptyLine').attr('disabled', true);
        }
        else {
            $('#exportSimilarity').attr('disabled', false);
            $('#onlyLeafCodes').attr('disabled', false);
            $('#onlyReferencedCode').attr('disabled', false);
            $('#emptyLine').attr('disabled', false);
        }
    });
    $('#exportSimilarity').click(function() {
        if ($('#exportSimilarity').is(':checked')) {
            $('#exportHTML').prop('checked', false);
            $('#exportHTML').attr('disabled', true);
            $('#onlyLeafCodes').prop('checked', false);
            $('#onlyLeafCodes').attr('disabled', true);
            $('#onlyReferencedCode').prop('checked', false);
            $('#onlyReferencedCode').attr('disabled', true);
            $('#concatRubrics').prop('checked', false);
            $('#concatRubrics').attr('disabled', true);
        }
        else {
            $('#exportHTML').attr('disabled', false);
            $('#onlyLeafCodes').attr('disabled', false);
            $('#onlyReferencedCode').attr('disabled', false);
            $('#concatRubrics').attr('disabled', false);
        }
    });

    $('#exportRKindsAll ul li').click(function() {
        $(this).attr('selected', !$(this).attr('selected'));
    });
    $('#submitExport').click(function() {
        $('#submitExport').hide();
        $('#exportResults').hide();
        var rubrics = '';
        $('#exportRKindsAll ul li').each(function() {
            if ($(this).attr('selected')) rubrics += $(this).attr('id') + ' ';
        });
        $('#selectedRKinds').val(rubrics);
    });

    $('.switch').click(function() {
        var list = '#' + $(this).attr('for');
        $(list).toggle();
    });
})


function setInstruction(text, instruction, value)
{
    var i = 0;
    var done = false;
    var newText = '';
    var lines = text.split('\n');
    while (i < lines.length) {
        if (lines[i].startsWith(instruction)) {
            lines[i] = instruction + ' := ' + value;
            done = true;
        }
        newText += lines[i] + '\n';
        i++;
    }
    if (!done) {
        newText += '\n' + instruction + ' := ' + value;
    }
    return newText.trim();
}


function filterLog($author)
{
    
}

