var $ = jQuery;
var post_edit_form_submit = true;
$(function(){
    var $form_edit = $(".post form[name='edit']");
    $(".form-edit-submit").click(function(){
        post_edit_form_submit = true;
        $form_edit.submit();
    });
    $('body').on('click', ".post form[name='edit'] [type='file']", function(){
        post_edit_form_submit = false;
        console.log("upload box clicked. post_edit_form_submit:" + post_edit_form_submit);
    });
    $form_edit.submit(function(){
        console.log("form_edit submit() begins with post_edit_form_submit:" + post_edit_form_submit);
        if ( post_edit_form_submit ) {
            var config_name = $form_edit.find("[name='post_config_name']").val();
            $form_edit.prop('action', '/post/'+config_name+'/add');
            console.log("action: " + $form_edit.prop('action'));
            return true;
        }
        else {
            $form_edit.prop('action', '/post/api?call=fileUpload');
            ajax_file_upload($form_edit, callback_ajax_file_upload);
            return false;
        }
    });
});
function callback_file_upload_complete($form, files) {

    post_edit_form_submit = true;
    var callback = $form.attr('insert-image-callback');
    console.log("callback: " + callback);
    if ( typeof callback != 'undefined' && callback != '' ) return window[callback]($form, files);


    console.log("callback_file_upload_complete();");
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
        $(".uploaded-files").append(markup);
        var insert = $form.attr('insert-image');
        if ( insert != 'no' ) CKEDITOR_EDIT.insertHtml('<img src="'+file['url']+'"/>');
    }
}