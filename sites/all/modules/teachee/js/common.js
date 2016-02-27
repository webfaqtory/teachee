function getWidth(width) {
  var winWidth = jQuery(window).width();
  if (winWidth < width) {
    return winWidth;
  }else{
    return width;
  }
}
function getScaleWidth(width) {
  if((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i))) {
    return 0.85;
  }else{
    return 1;
  }
}

function languageURL() {
  var l = getCookie("language");
  if (l) {
    if (l == 'en') {
      return "";
    }else{
      return "/" + l;
    }
  }else{
    return "";
  }
}

function getCookie(c_name){
  var i,x,y,ARRcookies=document.cookie.split(";");
  for (i=0;i<ARRcookies.length;i++)
  {
    x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
    y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
    x=x.replace(/^\s+|\s+$/g,"");
    if (x==c_name)
      {
      return unescape(y);
      }
    }
  return null;
}
function setCookie(c_name, value, exdays) {
  var exdate = new Date();
  exdate.setDate(exdate.getDate() + exdays);
  var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
  document.cookie = c_name + "=" + c_value + "; path=/";
}