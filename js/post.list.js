window.addEventListener("load", function(){
    $ = jQuery;
    $(function(){
        var $author = $(".post .list article.post .author");
        $author.click(function(e){
            e.preventDefault();
            var name = $(this).text();
            alert(name + " clicked");
        });
    });
});