var LastCKEditor;
var CKEditorArray = [];
function loadCKEditor(id) {
    LastCKEditor = CKEDITOR.replace( id, {
        uiColor: '#f9f9f9',
        startupFocus : true,
        height:'12em',
        toolbar :
            [
                [
                    'Bold', 'Italic', 'Underline', 'Strike', "TextColor", "BGColor",
                    'NumberedList', 'BulletedList',
                    'Cut', 'Copy', 'Paste', 'Undo', 'Redo',
                    "Outdent", "Indent", "Blockquote", "Link", "Unlink", 'HorizontalRule',
                    "Table",
                    "Smiley",
                    'Source',
                    "Maximize",
                    'Font', 'FontSize',
                    'Format', 'Styles'
                ]
            ]
    } );

    CKEditorArray[id] = LastCKEditor;


    CKEDITOR.on("instanceReady", function(event) {
        var range = LastCKEditor.createRange();
        range.moveToElementEditablePosition( LastCKEditor.editable(), true );
        LastCKEditor.getSelection().selectRanges( [ range ] );
    });
}
function loadReplyCKEditor(id) {
    var $ = jQuery;
    $(".show-on-click").show();
    loadCKEditor(id);
}
function loadCommentCKEditor(id) {
    var $ = jQuery;
    $("#"+id).parent().show();
    loadCKEditor(id);
}