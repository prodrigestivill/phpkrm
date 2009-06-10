<? ob_start(); ?>
<? 
require('config.php');
if ($recaptcha_mail_pubkey!="" || $recaptcha_mail_privkey!="" || $recaptcha_form_pubkey!="" || $recaptcha_form_privkey!="")
   require_once ("recaptchalib.php");
$pastekey=((isset($_POST['pastekey'])) ? $_POST['pastekey'] : "");
$filekey=((isset($_FILES['upkey']['tmp_name'])) ? $_FILES['upkey']['tmp_name'] : "");
$newkeyring=((isset($_POST['newkeyring'])) ? $_POST['newkeyring'] : "");
$oldkeyring=((isset($_POST['oldkeyring'])) ? $_POST['oldkeyring'] : "");
$q=((isset($_GET['q'])) ? $_GET['q'] : "");
$param=split("/", $q);
$keyringid=$param[0];
//Check if keyring is correct.
if (!($keyringid!="" && preg_match("/^[A-Za-z0-9_-]*$/", $keyringid) && file_exists($dbpath.$keyringid))){
   $keyringid="";
}
$linkbase=(($basehref=="") ? "?q=" : $basehref);
putenv("GNUPGHOME=".$dbpath);

print_header("Manage keyrings");

?>
<div class="bodykeyrings" id="keyringslist"><ul>
<?
   if ($handle=opendir($dbpath)) {
    while(false !== ($lkrid=readdir($handle))){
     if (preg_match("/^[A-Za-z0-9]+$/", $lkrid)){
		print("<li><a class='keyring' href='".$linkbase.$lkrid."'>".$lkrid."</a> keyring</li>");
	}
      }
   
    closedir($handle);
   }else
     print("<li class=\"error\">Can't list directory.</li>");

?>
</ul></div>

<div class="manage"><ul>

<form method="post" enctype="multipart/form-data"><p><b>Please write name of keyring:</b><br/><input type="text" input name="newkeyring" value="" size="20" maxlength="40"/><input type="submit" value="Add new keyring" style="boarder: lpx solid gray; background-color: white" />
</form></p>
<?
	if (isset($newkeyring)){
		if ($newkeyring!="") {
			$file = ("$dbpath/$newkeyring");
			$fileHandle = fopen($file, "w");
			fclose($fileHandle);
			header("Location: admin.php");
		}
	}
?>
<p>
      <b>Click to keyring to remove</b><br/></form>
</p>
<?
	$new_article = ("?q=");
	$old_article = ($_SERVER['QUERY_STRING']);
	$diff=substr($old_article, 2);
	if($diff!=""){
			//print("$diff");
			Unlink("$dbpath/$diff");
			header("Location: admin.php");
		}

?></ul></div><div class="exitbutton"><form method="post"><input type="button" value="Close Window" onclick='window.location="index.php"' style="boarder: lpx solid gray; background-color: white"></form>
</ul></div><?


function print_header($title){
   global $basehref;
   
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><title><? echo $title; ?></title>
<style type="text/css" media="all">@import '<? echo $basehref; ?>css/style.css';</style></head><body>
<h1 class="title"><? echo $title; ?></h1>
<?
}
?>
<? ob_flush(); ?>




