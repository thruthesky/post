var $ = jQuery;
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