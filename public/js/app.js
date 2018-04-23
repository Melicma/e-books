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
            "lengthMenu":     "Zobrazit _MENU_ záznamů",
            "loadingRecords": "Načítání...",
            "processing":     "Processing...",
            "search":         "Vyhledat:",
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
            "lengthMenu":     "Zobrazit _MENU_ záznamů",
            "loadingRecords": "Načítání...",
            "processing":     "Processing...",
            "search":         "Vyhledat:",
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

    $.each($('textarea[data-autoresize]'), function() {
        var offset = this.offsetHeight - this.clientHeight;

        var resizeTextarea = function(el) {
            var tmp = $(window).scrollTop();

            $(el).css('height', 'auto').css('height', el.scrollHeight + offset);
            $(window).scrollTop(tmp);
        };
        $(this).on('keyup input', function() { resizeTextarea(this); }).removeAttr('data-autoresize');
    });

    $("textarea").each(function () {
        this.style.height = (this.scrollHeight+10)+'px';
    });



} );

function doImgModal(id) {
    $('.imagepreview').attr('src', $('#myImg'+id).attr('src').replace('_small', ''));
    $('#imagemodal').modal('show');
}

function doBold() {
    var editor = document.getElementById("texArea01");
    console.log(editor.value);
    var editorHTML = editor.value;
    var selectionStart = 0, selectionEnd = 0;
    if (editor.selectionStart) selectionStart = editor.selectionStart;
    if (editor.selectionEnd) selectionEnd = editor.selectionEnd;
    if (selectionStart != selectionEnd) {
        var editorCharArray = editorHTML.split("");
        editorCharArray.splice(selectionEnd, 0, "\</b>");
        editorCharArray.splice(selectionStart, 0, "\<b>"); //must do End first
        editorHTML = editorCharArray.join("");
        editor.value = editorHTML;
    }
}