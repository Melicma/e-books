/**
 * Created by martin on 1.3.18.
 */

$(document).ready( function () {
    $('#tableWorks').DataTable({
        "columns": [
            null,
            null,
            null,
            null,
            {"orderable": false},
            {"orderable": false}
        ],
        "language": {
            "decimal":        "",
            "emptyTable":     "Žádné data k dispozici",
            "info":           "Zobrazeno _START_ až _END_ z _TOTAL_ výsledků",
            "infoEmpty":      "Zobrazeno 0 až 0 z 0 výsledků",
            "infoFiltered":   "(filtrováno z _MAX_ celkových výsledků)",
            "infoPostFix":    "",
            "thousands":      ",",
            "lengthMenu":     '<img src="help.svg" alt="help" class="icon" title="Vyberte počet zobrazených děl na stránce." aria-hidden="true" data-toggle="tooltip">Zobrazit _MENU_ záznamů',
            "loadingRecords": "Načítání...",
            "processing":     "Processing...",
            "search":         '<img src="help.svg" alt="help" class="icon" title="Zadejte výraz pro rychlé vyhledávání v tabulce." aria-hidden="true" data-toggle="tooltip">Vyhledat:',
            "zeroRecords":    "Výsledek nenalezen",
            "paginate": {
                "first":      "První",
                    "last":       "Poslední",
                    "next":       "Další",
                    "previous":   "Předchozí"
            },
            "aria": {
                "sortAscending":  ": aktivovat pro třídění sloupců vzestupně",
                    "sortDescending": ": aktivovat pro třídění sloupců sestupně"
            }
        }
    });

    $('#tableList').DataTable({
        "columns": [
            null,
            null,
            null,
            null,
            {"orderable": false}
        ],
        "language": {
            "decimal":        "",
            "emptyTable":     "Žádné data k dispozici",
            "info":           "Zobrazeno _START_ až _END_ z _TOTAL_ výsledků",
            "infoEmpty":      "Zobrazeno 0 až 0 z 0 výsledků",
            "infoFiltered":   "(filtrováno z _MAX_ celkových výsledků)",
            "infoPostFix":    "",
            "thousands":      ",",
            "lengthMenu":     '<img src="help.svg" alt="help" class="icon" title="Vyberte počet zobrazených záznamu na stránce." aria-hidden="true" data-toggle="tooltip">Zobrazit _MENU_ záznamů',
            "loadingRecords": "Načítání...",
            "processing":     "Processing...",
            "search":         '<img src="help.svg" alt="help" class="icon" title="Zadejte výraz pro rychlé vyhledávání v tabulce." aria-hidden="true" data-toggle="tooltip">Vyhledat:',
            "zeroRecords":    "Výsledek nenalezen",
            "paginate": {
                "first":      "První",
                "last":       "Poslední",
                "next":       "Další",
                "previous":   "Předchozí"
            },
            "aria": {
                "sortAscending":  ": aktivovat pro třídění sloupců vzestupně",
                "sortDescending": ": aktivovat pro třídění sloupců sestupně"
            }
        }
    });

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    $('#authorsName').selectivity({
        multiple: true,
        placeholder: 'Zadejte pro vyhledání autorů'
    });

    $('#authorsNameSecond').selectivity({
        multiple: true,
        placeholder: 'Zadejte pro vyhledání autorů'
    });

    $('#publishersName').selectivity({
        multiple: true,
        placeholder: 'Zadejte pro vyhledání autorů'
    });

    $('#pseudonymName').selectivity({
        allowClear: true,
        placeholder: 'Zadejte autora pseudonymu'
    });

    $('#status').selectivity({
        allowClear: true
    });

    $('#status2').selectivity({
        allowClear: true
    });

    $('input[type="file"]').change(function(e){
        var number = e.target.files.length;
        var fileName = '';
        if (number > 1) {
            if (number < 5) {
                fileName = number + ' soubory vybrány';
            } else {
                fileName = number + ' souborů vybráno';
            }
        } else {
            fileName = e.target.files[0].name;
        }

        $("label[for='" + this.id + "']").text(fileName);
    });

} );

function doImgModal(id) {
    $('.imagepreview').attr('src', $('#myImg'+id).attr('src').replace('_small', ''));
    $('#imagemodal').modal('show');
}

function doTag(tag) {
    var displaytext = document.getElementById("editable");
    var objOffset = getSelectionCharacterOffsetWithin(displaytext);
    var text = displaytext.textContent;
    var sel = getSelection();

    var rng, startSel, endSel;
    if (!sel.rangeCount
        || displaytext.compareDocumentPosition((rng = sel.getRangeAt(0)).startContainer) === Node.DOCUMENT_POSITION_PRECEDING
        || displaytext.compareDocumentPosition(rng.endContainer) === Node.DOCUMENT_POSITION_FOLLOWING)
        sel = "";
    else {
        startSel = objOffset.start;
        endSel = objOffset.end;
        // startSel = displaytext.compareDocumentPosition(rng.startContainer) === Node.DOCUMENT_POSITION_FOLLOWING ? 0 : rng.startOffset;
        // endSel = displaytext.compareDocumentPosition(rng.endContainer) === Node.DOCUMENT_POSITION_PRECEDING ? displaytext.textContent.length : rng.endOffset;
        sel = displaytext.textContent.substring(startSel, endSel);
    }
    if (sel != '' && sel != undefined) {
        var editorCharArray = text.split("");
        editorCharArray.splice(endSel, 0, '</' + tag + '>');
        editorCharArray.splice(startSel, 0, '<' + tag + '>'); //must do End first
        text = editorCharArray.join("");
        displaytext.textContent = text;
        $('pre code').each(function(i, block) {
            hljs.highlightBlock(block);
        });
    }

}

function doBlockTag(tag, numOfSpaces) {
    var spaces = '';
    for (var l = 0; l < numOfSpaces; ++l) {
        spaces += ' ';
    }
    var displaytext = document.getElementById("editable");
    var text = displaytext.textContent;
    var sel = getSelection();
    var objOffset = getSelectionCharacterOffsetWithin(displaytext);

    var rng, startSel, endSel;
    if (!sel.rangeCount
        || displaytext.compareDocumentPosition((rng = sel.getRangeAt(0)).startContainer) === Node.DOCUMENT_POSITION_PRECEDING
        || displaytext.compareDocumentPosition(rng.endContainer) === Node.DOCUMENT_POSITION_FOLLOWING)
        sel = "";
    else {
        startSel = objOffset.start;
        endSel = objOffset.end;
        // startSel = displaytext.compareDocumentPosition(rng.startContainer) === Node.DOCUMENT_POSITION_FOLLOWING ? 0 : rng.startOffset;
        // endSel = displaytext.compareDocumentPosition(rng.endContainer) === Node.DOCUMENT_POSITION_PRECEDING ? displaytext.textContent.length : rng.endOffset;
        sel = displaytext.textContent.substring(startSel, endSel);
    }

    if (sel != '' && sel != undefined) {
        var editorCharArray = text.split("");
        editorCharArray.splice(endSel, 0, '\n' + spaces + '</' + tag + '>\n');
        editorCharArray.splice(startSel, 0, '\n' + spaces + '<' + tag + '>\n' + spaces + '  '); //must do End first
        var tmpArr = [];
        for (var k = startSel - 1; k != endSel; ++k) {
            if (editorCharArray[k] == ' ' && (editorCharArray[k-1] == '\n' || editorCharArray[k-1] == '\r\n')) {
                editorCharArray.splice(k, 1);
                --k;
                --endSel;
            }
        }
        for (var i = startSel - 1; i != endSel; ++i) {
            if (editorCharArray[i] == '\n' || editorCharArray[i] == '\r\n') {
                tmpArr.push(i);
            }
        }
        for (var y = tmpArr.length; y != 0; --y) {
            editorCharArray.splice(tmpArr[y-1] + 1, 0, spaces + '  ');
        }
        text = editorCharArray.join("");
        displaytext.textContent = text;

        $('pre code').each(function(i, block) {
            hljs.highlightBlock(block);
        });
    }

    // var editor = document.getElementById("editable");
    // var editorHTML = editor.value;
    // var selectionStart = 0, selectionEnd = 0;
    // if (editor.selectionStart) selectionStart = editor.selectionStart;
    // if (editor.selectionEnd) selectionEnd = editor.selectionEnd;
    // if (selectionStart != selectionEnd) {
    //     var editorCharArray = editorHTML.split("");
    //     editorCharArray.splice(selectionEnd, 0, '\n        </' + tag + '>\n');
    //     editorCharArray.splice(selectionStart, 0, '\n        <' + tag + '>\n          '); //must do End first
    //     var tmpArr = [];
    //     for (var k = selectionStart - 1; k != selectionEnd; ++k) {
    //         if (editorCharArray[k] == ' ' && (editorCharArray[k-1] == '\n' || editorCharArray[k-1] == '\r\n')) {
    //             editorCharArray.splice(k, 1);
    //             --k;
    //             --selectionEnd;
    //         }
    //     }
    //     for (var i = selectionStart - 1; i != selectionEnd; ++i) {
    //         if (editorCharArray[i] == '\n' || editorCharArray[i] == '\r\n') {
    //             tmpArr.push(i);
    //         }
    //     }
    //     for (var y = tmpArr.length; y != 0; --y) {
    //         console.log('doing');
    //         editorCharArray.splice(tmpArr[y-1] + 1, 0, '          ');
    //     }
    //     editorHTML = editorCharArray.join("");
    //     editor.value = editorHTML;
    // }
}

function doRow() {
    var displaytext = document.getElementById("editable");
    var objOffset = getSelectionCharacterOffsetWithin(displaytext);
    var text = displaytext.textContent;

    var textBefore = text.substring(0,  objOffset.start);
    var textAfter  = text.substring(objOffset.start, text.length);
    displaytext.textContent = textBefore + '\n' + textAfter;
    $('pre code').each(function(i, block) {
        hljs.highlightBlock(block);
    });

    // var cursorPos = $('#editable').prop('selectionStart');
    // var v = $('#editable').val();
    // var textBefore = v.substring(0,  cursorPos);
    // var textAfter  = v.substring(cursorPos, v.length);

    // $('#editable').val(textBefore + '\n' + textAfter);
}

function doEl(el) {

    var displaytext = document.getElementById("editable");
    var text = displaytext.textContent;
    var sel = getSelection();
    var objOffset = getSelectionCharacterOffsetWithin(displaytext);

    var rng, startSel, endSel;
    if (!sel.rangeCount
        || displaytext.compareDocumentPosition((rng = sel.getRangeAt(0)).startContainer) === Node.DOCUMENT_POSITION_PRECEDING
        || displaytext.compareDocumentPosition(rng.endContainer) === Node.DOCUMENT_POSITION_FOLLOWING)
        sel = "";
    else {
        startSel = objOffset.start;
        endSel = objOffset.end;
        // startSel = displaytext.compareDocumentPosition(rng.startContainer) === Node.DOCUMENT_POSITION_FOLLOWING ? 0 : rng.startOffset;
        // endSel = displaytext.compareDocumentPosition(rng.endContainer) === Node.DOCUMENT_POSITION_PRECEDING ? displaytext.textContent.length : rng.endOffset;
        sel = displaytext.textContent.substring(startSel, endSel);
    }

    if (sel != '' && sel != undefined) {
        var editorCharArray = text.split("");
        editorCharArray.splice(endSel, 0, el);
        editorCharArray.splice(startSel, 0, el); //must do End first
        text = editorCharArray.join("");
        displaytext.textContent = text;
        $('pre code').each(function(i, block) {
            hljs.highlightBlock(block);
        });
    }

    // var editor = document.getElementById("editable");
    // var editorHTML = editor.value;
    // var selectionStart = 0, selectionEnd = 0;
    // if (editor.selectionStart) selectionStart = editor.selectionStart;
    // if (editor.selectionEnd) selectionEnd = editor.selectionEnd;
    // if (selectionStart != selectionEnd) {
    //     var editorCharArray = editorHTML.split("");
    //     editorCharArray.splice(selectionEnd, 0, el);
    //     editorCharArray.splice(selectionStart, 0, el); //must do End first
    //     editorHTML = editorCharArray.join("");
    //     editor.value = editorHTML;
    // }
}


function getSelectionCharacterOffsetWithin(element) {
    var start = 0;
    var end = 0;
    var doc = element.ownerDocument || element.document;
    var win = doc.defaultView || doc.parentWindow;
    var sel;
    if (typeof win.getSelection != "undefined") {
        sel = win.getSelection();
        if (sel.rangeCount > 0) {
            var range = win.getSelection().getRangeAt(0);
            var preCaretRange = range.cloneRange();
            preCaretRange.selectNodeContents(element);
            preCaretRange.setEnd(range.startContainer, range.startOffset);
            start = preCaretRange.toString().length;
            preCaretRange.setEnd(range.endContainer, range.endOffset);
            end = preCaretRange.toString().length;
        }
    } else if ( (sel = doc.selection) && sel.type != "Control") {
        var textRange = sel.createRange();
        var preCaretTextRange = doc.body.createTextRange();
        preCaretTextRange.moveToElementText(element);
        preCaretTextRange.setEndPoint("EndToStart", textRange);
        start = preCaretTextRange.text.length;
        preCaretTextRange.setEndPoint("EndToEnd", textRange);
        end = preCaretTextRange.text.length;
    }
    return { start: start, end: end };
}

function post(id) {
    // path, params
    var method = "post"; // Set method to post by default if not specified.
    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", '/text/' + id);

    var displaytext = document.getElementById("editable");
    var text = displaytext.innerText;

    var hiddenField = document.createElement("textarea");
    hiddenField.setAttribute("type", "hidden");
    hiddenField.setAttribute("name", 'text');
    hiddenField.innerHTML = text;
    form.appendChild(hiddenField);

    var hiddenInput = document.createElement("input");
    hiddenInput.setAttribute("value", document.getElementById('status').value);
    hiddenInput.setAttribute("type", 'hidden');
    hiddenInput.setAttribute("name", 'status');
    form.appendChild(hiddenInput);

    document.body.appendChild(form);
    form.submit();
}