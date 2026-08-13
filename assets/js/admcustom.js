"use strict";

$(document).ready(function () {
    load_notitoast();
    setInterval(function () {
        load_notitoast();
    }, 90000);

    $(document).ready(function() {
        $('#allfindkw').on('keyup', function() {
            var keyword = $(this).val();
            if (keyword.length >= 3) {
                $.get('doallfindkw.php?keyword=' + keyword, function(data) {
                    $('#allfindrslt').html(data);
                });
            }
        });
    });

});

function load_notitoast() {
    $.ajax({
        url: "dtfetch.php",
        method: "POST",
        dataType: "json",
        success: function (data) {
            if (data.notitoaststr !== '') {
                var strdat = data.notitoaststr;
                var resarr = strdat.split("|");
                var index;
                for (index = 0; index < resarr.length; ++index) {
                    var subitem = resarr[index];
                    var valarr = subitem.split(":");
                    notifytoast(valarr[0], valarr[1], valarr[2]);
                }
            }
        }
    });
}

function do_livesearch(lswhat, lsearchid, lresultid, tbfield = '') {
    $('#' + lsearchid + ' input[type="text"]').on("keyup input", function () {
        /* Get input value on change */
        var inputVal = $(this).val();
        var resultDropdown = $(this).siblings('#' + lresultid);
        if (inputVal.length) {
            $.get("loadlivesearch.php", {
                findwhat: lswhat,
                findkey: inputVal,
                findontable: tbfield
            }).done(function (data) {
                // Display the returned data in browser
                resultDropdown.html(data);
            });
        } else {
            resultDropdown.empty();
        }
    });

    // Set search input value on click of result item
    $(document).on('click', '#' + lresultid + ' option', function () {
        $(this).parents('#' + lsearchid).find('input[type="text"]').val($(this).text());
        $(this).parent('#' + lresultid).empty();
    });
}
