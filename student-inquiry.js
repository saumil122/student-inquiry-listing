/*(function (window, document, $, undefined) {

    var snadmin = {};

    snadmin.init = function () {
        var selected=$('#to[type="email"]').val();
        $('#to[type="text"]').suggest(window.ajaxurl + "?action=student_search&selected="+selected, { multiple: true, multipleSep: "," });

    }

    snadmin.init();

})(window, document, jQuery);*/
/* globals global */
var selectedIDs=[];
function split(val) {
    return val.split(/,\s*/);
}
function extractLast(term) {
    return split(term).pop();
}

function removeItemOnce(arr, value) { 
    var index = arr.indexOf(value);
    if (index > -1) {
        arr.splice(index, 1);
    }
    return arr;
}
jQuery(function ($) {
    $(function () {
        $('#to[type="text"]')
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function (event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function (request, response) {
                    $.getJSON(window.ajaxurl + "?action=student_search", {
                        term: extractLast(request.term),
                        selected: selectedIDs
                    }, response);
                },
                search: function () {
                    // custom minLength
                    var term = extractLast(this.value);
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function () {
                    // prevent value inserted on focus
                    return false;
                },
                select: function (event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    //this.value = terms.join(", ");
                    this.value = '';
                    selectedIDs.push(ui.item.id);
                    $(this).next('.selected-tags').append('<div class="selected-student" id="student_'+ui.item.id+'" data-value="'+ui.item.id+'">'+ui.item.value+'<span class="close">x</span></div>');
                    $('#selected_ids').val(selectedIDs.join(", "));
                    return false;
                }
            });
    });

    $(document).on('click','.selected-tags .close',function(){
        var tagId=$(this).parent().attr('data-value');
        console.log(tagId);
        $(this).parent().remove();
        selectedIDs=removeItemOnce(selectedIDs,tagId);
    });

    $(document).on('change','#invite_type', function(){
        var invite_type=$(this).val();
        $('.conditional_field').hide();
        $('.conditional_field.'+invite_type).show();
    })
});

