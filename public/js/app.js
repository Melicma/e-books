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
} );