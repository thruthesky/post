var $ = jQuery;
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
            ajax_file_upload($(this), callback_ajax_file_upload);
            return false;
        }
    });
});
