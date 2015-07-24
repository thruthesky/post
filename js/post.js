var $ = jQuery;
$(function(){
    $("section[role='search'] form").submit(function(){
        var qn = $("[name='qn']").prop('checked');
        var qt = $("[name='qt']").prop('checked');
        var qc = $("[name='qc']").prop('checked');
        if ( qn == false && qt == false && qc == false ) {
            $("[name='qt']").prop('checked',true);
        }
    });
    $(".search-box [name='qn']").click(function(){
        if ( $(this).prop('checked') ) {
            $(".search-box [name='qt']").prop('checked', false);
            $(".search-box [name='qc']").prop('checked', false);
        }
    });
    $(".search-box [name='qt'],.search-box [name='qc']").click(function(){
        if ( $(this).prop('checked') ) {
            $(".search-box [name='qn']").prop('checked', false);
        }
    });


    $('body').on('click', ".uploaded-files .delete", function(){
        var fid = $(this).parent().attr('fid');
        console.log('fid:'+fid);
        var url = "/post/api?call=fileDelete&fid="+fid;
        ajax_api( url, function(re) {
            if ( re.code == 0 ) {
                $(".uploaded-files div[fid='"+re.fid+"']").slideUp();
            }
        } );
    });
});


function ajax_file_upload($form, callback_function)
{
    var $upload_progress = $(".ajax-file-upload-progress-bar");
    $form.ajaxSubmit({
        beforeSend: function() {
            //console.log("seforeSend:");
            $upload_progress.show();
            var percentVal = '0%';
            $upload_progress.find('.percent').width(percentVal);
            $upload_progress.find('.caption').html('Upload: 0%');
        },
        uploadProgress: function(event, position, total, percentComplete) {
            //console.log("while uploadProgress:" + percentComplete + '%');
            var percentVal = percentComplete + '%';
            $upload_progress.find('.percent').width(percentVal);
            $upload_progress.find('.caption').html('Upload: ' + percentVal);
        },
        success: function() {
            //console.log("upload success:");
            var percentVal = '100%';
            $upload_progress.find('.percent').width(percentVal);
            $upload_progress.find('.caption').html('Upload: ' + percentVal);
        },
        complete: function(xhr) {
            //console.log("Upload completed!!");
            var re;
            try {
                re = JSON.parse(xhr.responseText);
            }
            catch ( e ) {
                return alert( xhr.responseText );
            }
            // console.log(re);
            callback_function( $form, re );
            setTimeout(function(){
                $upload_progress.hide();
            }, 500);
            $.each($form.find("input[type='file']"), function(i, v){
                var name = $(this).prop('name');
                var markup = "<input type='file' name='" + name + "' multiple onchange='jQuery(this).parent().submit();'>";
                $(this).replaceWith(markup);
            });
        }
    });
}


function callback_ajax_file_upload($form, re)
{
    console.log("callback_ajax_file_upload() begin");
    var data;
    try {
        data = JSON.parse(re);
    }
    catch (e) {
        alert(re);
        return;
    }
    console.log(data['files']);
    var i;
    for( i in data['files'] ) {
        var file = data['files'][i];
        console.log(file['fid']);
        var val = $form.find('[name="fid"]').val();
        val += ',' + file['fid'];
        $form.find('[name="fid"]').val( val );
    }
    if ( typeof callback_file_upload_complete == 'function' ) callback_file_upload_complete($form, data['files']);
}