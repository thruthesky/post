var $ = jQuery;
var CKEditorID;
$(function(){
    var $form = $("form[name='comment-add']");
    var form_submit = false;
    $(".form-comment-add-submit").click(function(){
        form_submit = true;
        $(this).parent().submit();
    });
    $form.submit(function(){
        if ( form_submit ) {
            $form.prop('action', '/post/comment/submit');
            return true;
        }
        else {
            $form.prop('action', '/post/api?call=fileUpload');
            // console.log('parent_id' + $(this).find('[name="parent_id"]').val());

            var $this = $(this);
            CKEditorID = $this.find("[name='content']").prop('id');
            console.log("CKEditorID: " + CKEditorID);
            ajax_file_upload($this, callback_ajax_file_upload);
            return false;
        }
    });
});

function callback_file_upload_complete($form, files) {
    var parent_id = $form.find("[name='parent_id']").val();
    console.log("callback_file_upload_complete() for comment. parent_id:" + parent_id);
    console.log(files);

    for( var i in files ) {
        var file = files[i];
        var markup = "<div fid='"+file['fid']+"' class='file'>";
        markup += "<a href='"+file['url']+"' target='_blank'>";
        markup += "<span class='photo'><img src='"+file['url']+"'></span>";
        markup += "<span class='name'>"+file['name']+"</span>";
        markup += "</a>";
        markup += "<span class='delete'>DELETE</span>";
        markup += "</div>";
        $(".uploaded-files[parent_id='"+parent_id+"']").append(markup);
        //console.log("CKEditorID: " + CKEditorID);


        var insert = $form.attr('insert-image');
        if ( insert != 'no' ) CKEditorArray[CKEditorID].insertHtml('<img src="'+file['url']+'"/>');

    }
}