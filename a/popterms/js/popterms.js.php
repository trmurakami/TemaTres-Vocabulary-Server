<?php
header('Content-Type: application/javascript');
include_once('../config.ws.php');
?>
var options, a;
var onSelect = function(val, data) { $('#searchform #id').val(data); $('#searchform').submit(); };   
jQuery(function(){
options = {
    serviceUrl:'<?php echo WEBTHES_PATH;?>common/proxy.php' ,
    minChars:2,
    delimiter: /(,|;)\s*/, // regex or character
    maxHeight:400,
    width:600,
    zIndex: 9999,
    deferRequestBy: 0, //miliseconds
    params: { v:'<?php echo $v;?>' }, //aditional parameters
    noCache: false, //default is false, set to true to disable caching
    // callback function:
    onSelect: onSelect,
	};
  a = $('#query').autocomplete(options);
}); 

$(function(){
    $('#treeTerm').tree({
      dragAndDrop: false,
      autoEscape: false
  });
});	

function PopTermsWrite (term, target){
  var data = { term:term, index:target};
  window.opener.postMessage(data, "*");
}

