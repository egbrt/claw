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

import Tree from "./tree.js";
import Rubrics from "./rubrics.js";
import Comments from "./comments.js";

var urlBrowser = "";
var windowExternal = Object();
var urlFollower = "";
var nameFollower = "follower";
var urlLastChange = "";
var currentMode = "editor";
var currentCode = "";
var currentClassKind = '';
var userSelected = "allUsers";
var selectedNode;
var selectedTreeClass;
var selectedSearchClass;
var selectAfterRefresh;
var previousSearchText = "";
var validRubrics = true;

let comments = null;
let rubrics = null;
let tree = null;

export {currentMode, currentCode, currentClassKind};

$(function() {
    var to = false;
    var url = "//" + location.hostname;
    if (url.indexOf("localhost") > 0) {
        urlFollower = "https://localhost/icpc3-browser";
        url += "/claw";
    }
    else {
        urlFollower = "https://browser.icpc-3.info";
    }
    urlBrowser = url + "/browse.php";
    urlLastChange = url + "/lastchange.php";

    getLastChange();
    $('#editor').show();
    $('#preview').hide();
    $('#comments').hide();
    comments =new Comments(urlBrowser);
    rubrics = new Rubrics(urlBrowser, "#textEditor", "#preview");
    tree = new Tree(urlBrowser, "#treeview");
    tree.comments = comments;
    tree.rubrics = rubrics;
    comments.setTree(tree);
    rubrics.tree = tree;
    tree.show(0);

    $('#searchText').keyup(function() {
        if (to) {clearTimeout(to)};
        var searchText = $('#searchText').val().trim();
        if ((searchText.length > 2) && (searchText != previousSearchText)) {
            to = setTimeout(function(){tree.search(searchText)}, 500);
        }
    });
    
    $('#searchText').focus(function() {
        var searchText = $('#searchText').val().trim();
        if (searchText.length > 2) tree.search(searchText);
    });

    $('#saveChangedRubrics').click(function() {
        validRubrics = rubrics.check();
        if (validRubrics) rubrics.save();
    });
    
    $('#saveComments').click(function() {
        comments.save();
    });
    
    $('#newRubric').keyup(function() {
        var text = $('#newRubric').val();
        if (text == '') {
            $("#buttonAddComment").attr('disabled', true);
        }
        else {
            $("#buttonAddComment").removeAttr('disabled');
        }
    });
    
    $("#textEditor").keyup(function(e) {
        if (!validRubrics) {
            $("#errorInRubrics").hide();
        }
        else if ((e.key == '<') || (e.key == '"')) {
            var cursor = $(this).prop("selectionStart");
            var beforeCursor = $(this).val().substring(0, cursor);
            var afterCursor = $(this).val().substring(cursor);
            var insert = e.key;
            if (e.key == '<') insert = '>';
            $(this).val(beforeCursor+insert+afterCursor);
            $(this).prop("selectionStart", cursor);
            $(this).prop("selectionEnd", cursor);
        }
        $("#saveChangedRubrics").show();
    });

    $("#commentsEditor").keyup(function(e) {
        $("#saveComments").show();
    });

    // handle click on comment
    $("#allComments ul li").click(function() {
        alert("Misschien een extra tab openen? En dan een message posten met de code? Of hier naar editor.php?code gaan?");
    });

    $('#buttonFollower').click(function() {
        rubrics.windowFollower = window.open(urlFollower, nameFollower);
        $('#buttonFollower').hide();
    });

    window.addEventListener("message", function(event) {
        if ((event.origin == "https://localhost") || (event.origin.includes(".icpc-3.info"))) {
            tree.jumpTo(event.data);
        }
    });
});


function getLastChange()
{
    if ((rubrics) && (rubrics.windowFollower) && (rubrics.windowFollower.closed)) {
        $('#buttonFollower').show();
    }
    
    if ($('#lastChange')) {
        $.ajax ({
            url: urlLastChange,
            data: {},
            type: "GET",
            dataType: "json",
        })
    
        .done (function(json) {
            var i = 0;
            var text = '';
            while (i < json.changes.length) {
                text += json.changes[i].time + '<br/>';
                text += '&nbsp;&nbsp;' + json.changes[i].user + ' at <span id=\"' + json.changes[i].where + '\">';
                text += json.changes[i].where + '</span><br/>';
                text += '&nbsp;&nbsp;' + json.changes[i].what + "<br/>";
                i++;
            }
            if (text == '') {
                $('#lastChange').html(text).hide();
            }
            else {
                $('#lastChange').html(text).show();
                $('#lastChange span').click(function() {
                    jumpTo(this.id);
                });
            }
        })        
        setTimeout(function(){getLastChange()}, 60000); // 60 seconds
    }
}


export function showRelevantCommands(mode)
{
    tree.mode = mode;
    tree.showRelevantCommands();
}


function closeWindow()
{
    tmp = window.open('', '_self', '');
    tmp.close();
}
