var $ = jQuery;
$(function(){
    var $form_edit = $(".post form[name='edit']");
    var form_submit = false;
    $(".form-edit-submit").click(function(){
        form_submit = true;
        $form_edit.submit();
    });
    $form_edit.submit(function(){
        if ( form_submit ) {
            var config_name = $form_edit.find("[name='post_config_name']").val();
            $form_edit.prop('action', '/post/'+config_name+'/add');
            return true;
        }
        else {
            $form_edit.prop('action', '/post/api?call=fileUpload');
            ajax_file_upload($form_edit, callback_ajax_file_upload);
            return false;
        }
    });
});
