/*
EazyMatch functions
*/
(function( window, $, undefined ){

    $(function() {
        $("#emol-apply-form").validate({
            submitHandler:function(form) {
                form.submit();
            },
            rules: {
                firstname: "required",        // simple rule, converted to {required:true}
                lastname: "required",        // simple rule, converted to {required:true}
                email: {                // compound rule
                    required: true,
                    email: true
                }
            },
            messages: {
                comment: "Please enter a value."
            }
        });
    });

    $.validator.addMethod(
     "selectNone",
    function(value, element) {
        if (element.value == "none") {
            return false;
        }
        else return true;
    },
    "Please select an option."
    );

    //called from jobsearchwidget
    window.emolFreeSearch = function (baseSearchUrl){
        var freeVal = $('#emol-free-search').val();
        window.location = baseSearchUrl+'free,'+freeVal+'/';
    };

})( window, jQuery );


/**
* creates a serach url
*/
function emolSearch(baseUrl) {

    var seperator    = '/'
    var addStringVar    = ''
    var emolCleanUrl = location.href.substring(0, location.href.indexOf('/', 14));
    
    //check free search values
    if( jQuery('#emol-free-search-input').val() != ''){
        baseUrl = baseUrl + '/free,' + jQuery('#emol-free-search-input').val();
        seperator = ',';
    }
    
    //check free search values
    if( jQuery('#emol-zipcode-search-input').val() != ''){
        var range = 50;
        if(jQuery('#emol-range-search-input option:selected').val() > 0){
            range = jQuery('#emol-range-search-input option:selected').val();
        }
        baseUrl = baseUrl + seperator + 'location,' + jQuery('#emol-zipcode-search-input').val();
        baseUrl = baseUrl + ',' + range;
        seperator = ',';
    }
    
    //loop all selected selectboxes
    jQuery('.emol-search-competence option:selected').each(function(){
        if( jQuery(this).attr("value") != '')
            addStringVar += ',' +  jQuery(this).attr("value");
    });
    
    //check competences
    if( addStringVar != ''){
        baseUrl = ''+baseUrl+seperator+'competence'+addStringVar;
    } else if( jQuery('#emol-free-search-input').val() == '' && jQuery('#emol-zipcode-search-input').val() == '' ) {
        baseUrl = ''+baseUrl+'/all/';
    }
    
    //finalize url
    baseUrl = '/'+baseUrl;
    baseUrl =  baseUrl.replace('//','/');
    baseUrl =  emolCleanUrl + baseUrl + '/';
    
    //console.log(baseUrl);
    
    window.location =  baseUrl;
}