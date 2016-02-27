function signIn(noClose) {
  var form = '<form id="signin" onsubmit="return validateSignIn();"><div class="img"></div><div class="text">' + Drupal.t("Sign in") + '</div><input id="email" name="email" class="form-text" placeholder="' + Drupal.t("Email") + '"><br /><br /><input id="password" type="password"  class="form-text" name="password" placeholder="' + Drupal.t("Password") + '"><p dialog="password" class="forgot link dialog" onclick="forgotPassword();">' + Drupal.t("I forgot my password") + '</p><div class="right"><input id="sign_in" type="submit" class="green_button" value="' + Drupal.t("Sign in") + '"></div></form>';
  jQuery("#modal_dialog").attr("title", Drupal.t("Sign in"));
  
  jQuery("#modal_dialog_content").html(form);
  //setTimeout("aa()", 50);
  if (noClose) {
    jQuery("#modal_dialog").dialog({
      modal: true,
      width: getWidth(480),
      clickOut: false,
      responsive: true,
      scaleW: getScaleWidth(),
      scaleH: 1,
      resizable: false,
      draggable: true,
      dialogClass: "noclose signin",
      closeOnEscape: false,
      open: function(event, ui) {jQuery('#email').focus();},
      buttons: null
    });
  }else{
    jQuery("#modal_dialog").attr("title", Drupal.t("Sign in"));
    jQuery("#modal_dialog_content").html(form);
    jQuery("#modal_dialog").dialog({
      modal: true,
      width: getWidth(480),
      clickOut: true,
      responsive: true,
      scaleW: getScaleWidth(),
      scaleH: 1,
      resizable: false,
      draggable: true,
      dialogClass: "signin",
      open: function(event, ui) {jQuery('#email').focus();},
      buttons: null
    });
  }
  jQuery(".ui-dialog.signin .ui-dialog-titlebar .ui-dialog-title").text(Drupal.t("Sign in"));
  jQuery("#email, #password").placeholder();
  return false;
}

function validateSignIn() {
  var msg = "";
  var focus = null;
  if (!jQuery("#modal_dialog_content #email").val().length) {
    msg += Drupal.t("Enter your email address.\n");
    if (!focus) {
      focus = jQuery("#modal_dialog_content #email");
    }
  }
  if (!jQuery("#modal_dialog_content #password").val().length) {
    msg += Drupal.t("Enter your password.");
    if (!focus) {
      focus = jQuery("#modal_dialog_content #email");
    }
  }
  if (msg) {
    alert(msg);
    focus.focus();
    return false;
  }
  jQuery("#sign_in").addClass("disabled");
  jQuery.ajax({
    url: languageURL() + "/user-login/" + encodeURIComponent(jQuery("#modal_dialog_content #email").val()) + "/" + encodeURIComponent(jQuery("#modal_dialog_content #password").val()) ,
    type: "GET",
    dataType: "html",
    cache: false,
    timeout: 60000,
    error: function(XMLHttpRequest, textStatus, errorThrown){
    },
    success: function(result){
      jQuery("body").removeClass("ajaxwait");
      if (result == "BAD") {
        jQuery("#sign_in").removeClass("disabled");
        jQuery("body").removeClass("ajaxwait");
        alert("Invalid email/password!");
        jQuery("#modal_dialog_content #edit-mail").focus();
      }else{
        jQuery("#modal_dialog").dialog('close');
        window.location.reload();
      }
    }
  });
  return false;
}

function forgotPassword() {
  jQuery("#modal_dialog").attr("title", Drupal.t("Forgotten Password"));
  jQuery(".ui-dialog-title").text(Drupal.t("Forgotten Password"));
  jQuery("#modal_dialog_content").html('<form onsubmit="return validateForgotPassword();"><div class="text"><div class="center"><p dialog="password" class="forgot dialog">' + Drupal.t("Enter your email into the box below and we will send you a new password.") + '</p><input id="email" name="email" type="email" placeholder=' + Drupal.t("Email") + '><br /><br /><div class="green_button forgot_password_submit" onclick="validateForgotPassword();">' + Drupal.t("Send Me My Password") + '</div></div></form>');
  jQuery("#modal_dialog").dialog({
    modal: true,
    width: getWidth(440),
    clickOut: true,
    responsive: true,
    scaleW: getScaleWidth(),
    scaleH: 1,
    resizable: false,
    draggable: true,
    dialogClass: "error",
    open: function(event, ui) {},
    buttons: null
  });
}

function validateForgotPassword() {
  var msg = "";
  var focus = null;
  if (!jQuery("#email").val().length) {
    msg += Drupal.t("Enter your email address.");
    if (!focus) {
      focus = jQuery("#email");
    }
  }
  if (msg) {
    alert(msg);
    focus.focus();
    return false;
  }
  jQuery.ajax({
    url: "/check.php?m=e&e=" + encodeURIComponent(jQuery("#email").val()),
    type: "GET",
    dataType: "html",
    cache: false,
    timeout: 60000,
    error: function(XMLHttpRequest, textStatus, errorThrown){
    },
    success: function(result){
      jQuery("body").removeClass("ajaxwait");
      if (result.indexOf("email") == -1) {
        jQuery("body").removeClass("ajaxwait");
        alert(Drupal.t("Email address not found!"));
        jQuery("#email").focus();
      }else{
        jQuery("#modal_dialog").dialog('close');
        alert(Drupal.t("A new password link has been sent to your email address"));
        jQuery.ajax({
          url: languageURL() + "/reset-password/" + encodeURIComponent(jQuery("#email").val()),
          type: "GET",
          dataType: "html",
          cache: false,
          timeout: 60000,
          error: function(XMLHttpRequest, textStatus, errorThrown){
          },
          success: function(result){
          }
        });
      }
    }
  });
  return false;
}