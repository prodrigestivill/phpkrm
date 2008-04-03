<? 
/*
 * PHPkrm is a Web-based GnuPG keyring manager.
 * To administrate the keyrings use the gpg command line tool.
 *
 * Copyright 2005 Pau Rodriguez-Estivill <prodrigestivill@gmail.com>
 *
 * This software is licensed under the GNU General Public License.
 * See COPYING for details.
 */
require('config.php');
if ($recaptcha_mail_pubkey!="" || $recaptcha_mail_privkey!="" || $recaptcha_form_pubkey!="" || $recaptcha_form_privkey!="")
   require_once ("recaptchalib.php");
$pastekey=((isset($_POST['pastekey'])) ? $_POST['pastekey'] : "");
$filekey=((isset($_FILES['upkey']['tmp_name'])) ? $_FILES['upkey']['tmp_name'] : "");
$q=((isset($_GET['q'])) ? $_GET['q'] : "");
$param=split("/", $q);
$keyringid=$param[0];
//Check if keyring is correct.
if (!($keyringid!="" && file_exists($dbpath.$keyringid) && 
    preg_match("/^[A-Za-z0-9]*$/", $keyringid))){
   $keyringid="";
}
$linkbase=(($basehref=="") ? "?q=" : $basehref);
putenv("GNUPGHOME=".$dbpath);

if ($keyringid!=""){
   if (isset($param[1]) && preg_match("/^[A-Za-z0-9]+$/", $param[1]))
   switch ($param[1]){
    case "download":
       //Download Keyring
       main_download($keyringid,"");
       break;
    case "print":
       //List of keys
       main_print($keyringid,"");
       break;
    case "refresh":
      //Refresh keys from keyserver
      main_refresh($keyringid);
      break;
    default:
      if (!isset($param[2]))
         $param[2]="download";
      switch ($param[2]){
         case "print":
            main_print($keyringid, $param[1]);
            break;
         case "download":
            main_download($keyringid,$param[1]);
            break;
      }
      break;
   }
   else{
      Header("Content-type: text/html; charset=UTF-8");
      if (file_exists($dbpath.$keyringid.".php")){
          include($dbpath.$keyringid.".php");
          print("\n<!-- End header file ".$keyringid.".php -->\n");
      }else{
          print_header($keyringid." keyring");
      }
?>
<div class="keyringoptions"><a class="download" href="<? echo $linkbase.$keyringid; ?>/download">Download all</a>&nbsp;|&nbsp;<a class="print" href="<? echo $linkbase.$keyringid; ?>/print">Printing version</a></div><div class="bodykeyring" id="keyring<? echo $keyringid; ?>">
<?
      if ($recaptcha_form_privkey!="" && ($pastekey!="" || $filekey!="")){
         $res=recaptcha_check_answer($recaptcha_form_privkey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
         $captchavalid=$res->is_valid;
      }else
         $captchavalid=TRUE;
      if ($captchavalid==FALSE)
         print("<p class=\"error\">The CAPTCHA wasn't entered correctly.</p>");
      else{
      //Save pasted key
      if ($pastekey!="") {
        $filekey=tempnam("/tmp", "pastekey.asc");
        $fp=fopen($filekey, "w");
        fwrite($fp, $pastekey);
        fclose($fp);
      }

      //Import key
      if ($filekey!="") {
        $torun=$gpgbin." --no-default-keyring --keyring ".$keyringid." --import ".$filekey;
        exec($torun,$out,$result);
        unlink($filekey);

        if ($sendkeyserver!=""){ //Send to keyserver
           $torun=$gpgbin." --send-keys --keyserver ".$sendkeyserver." --no-default-keyring --keyring ".$keyringid;
           system($torun,$result);
           if ($result!=0)
             print("<p class=\"error\">Error sending to the keyserver. Contact admin.</p>");
        }
        print("<p class=\"info\">Key added<br /></p>"); 
      }
      }

      // List Keys
      $torun=$gpgbin." --display-charset utf-8 --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --with-colons --no-default-keyring --keyring ".$keyringid;
      $fistpub=TRUE;
      $handle=popen($torun,"r");
      while(!feof($handle))
         print_keyring(htmlspecialchars(fgets($handle,4096)));
      pclose($handle);
      if ($fistpub==FALSE)
         print("</li></ul>");
?>
</div><div class="formaddkey">
<p>Paste an ascii-armored of the key(s) that you wish to add to the keyring.</p>
<form method='post' action='<? echo $keyringid; ?>' enctype='multipart/form-data'><p>
<textarea name='pastekey' cols='55' rows='6'></textarea>
<br />Upload your file directly: <input type='file' name='upkey' /><br />
<? if ($recaptcha_form_pubkey!="") {
      if ($captchavalid==FALSE)
         print("<p class=\"error\">reCAPTCHA said: ".$res->error.".</p>");
      echo recaptcha_get_html($recaptcha_form_pubkey)."<br />";
   }?>
<input type='submit' /><br /></p></form></div>
<?
      print_footer();
   }
}else{
   Header("Content-type: text/html; charset=UTF-8");
   if (file_exists("list.php")){
       include("list.php");
       print("\n<!-- End header file list.php -->\n");
   }else{
       print_header("List of keyrings");
   }
?>
<div class="bodykeyrings" id="keyringslist"><ul>
<?
   if ($handle=opendir($dbpath)) {
    while(false !== ($lkrid=readdir($handle))){
     if (preg_match("/^[A-Za-z0-9]+$/", $lkrid))
         print("<li><a class='keyring' href='".$linkbase.$lkrid."'>".$lkrid." keyring</a></li>");
    }
   closedir($handle);
   }else
     print("<li class=\"error\">Can't list directory.</li>");
?>
</ul></div>
<?
     print_footer();
}

function main_download($keyringid,$keyid){
   global $gpgbin;
   Header("Content-type: text/plain"); 
   $torun=$gpgbin." --no-version --comment \"".(($keyid!="")?"From ":"").$keyringid." phpkrm keyring: ".get_keyringurl()."\" -a --export --no-default-keyring --keyring ".$keyringid." --export ".$keyid;
   system($torun,$result);
   if ($result!=0)
      print("Error using gpg. Contact admin.");
}

function main_refresh($keyringid){
   global $gpgbin,$keyserver,$sendkeyserver;
   if ($keyserver!=""){
      Header("Content-type: text/plain"); 
      print("Actualiting keys from ".$keyringid." keyring with ".$keyserver." keyserver.\n");
      $torun=$gpgbin." -v --refresh-keys --keyserver ".$keyserver." --no-default-keyring --keyring ".$keyringid;
      system($torun,$result);
      if ($sendkeyserver!="") {
          print("Sending keys from ".$keyringid." keyring to ".$sendkeyserver." keyserver.\n");
          $torun=$gpgbin." -v --send-keys --keyserver ".$sendkeyserver." --no-default-keyring --keyring ".$keyringid;
          system($torun,$result);
      }
   }
}

function main_print($keyringid,$keyid){
   global $gpgbin,$dbpath,$recaptcha_mail_pubkey,$recaptcha_mail_privkey,$ATtxt,$DOTtxt;
   Header("Content-type: text/plain; charset=UTF-8");
   if (file_exists($dbpath.$keyringid.".txt"))
       include($dbpath.$keyringid.".txt");
   else
       print((($keyid!="")?"From ":"").$keyringid." phpkrm keyring: ".get_keyringurl()."\n");
   if ($recaptcha_mail_pubkey!="" && $recaptcha_mail_privkey!="")
      $replacestr="";
   else
      $replacestr=" <\\1".$ATtxt."\\2".$DOTtxt."\\3>";
   $torun=$gpgbin." --display-charset utf-8 --fingerprint --no-default-keyring --keyring ".$keyringid." --list-public-keys ".$keyid;
   $handle=popen($torun,"r");
   if (!feof($handle))
      fgets($handle,4096); //Fist line lost
   while(!feof($handle))
      print(preg_replace("/ \<(.+)@(.+)\.(.+)\>/",$replacestr,fgets($handle,4096)));
   pclose($handle);
}

function get_keyringurl(){
   global $keyringid, $basehref;
   if ($keyringid=="")
      return "";
   $url="http";
   if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') $url.="s";
   $url.="://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/";
   if (preg_match("/\"/", $url)) //Ilegal security issue
      $url="";
   else
      $url.=(($basehref=="") ? "?q=" : "").$keyringid;
   return $url;
}

function tag_namefield($tag,$param,$namem){
   global $AThtml,$DOThtml,$recaptcha_mail_pubkey,$recaptcha_mail_privkey,$preg_mail;
   $noname=stristr($namem, " &lt;");
   if ($noname==FALSE)
      $name=$namem;
   else
      $name=substr($namem, 0, -(strlen($noname)));
   if ($recaptcha_mail_pubkey!="" && $recaptcha_mail_privkey!=""){
      $res=preg_match("/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i",$namem,$mail);
      if ($res!=0)
         $replacestr="&lt;<a href=\"".htmlspecialchars(recaptcha_mailhide_url($recaptcha_mail_pubkey,$recaptcha_mail_privkey,$mail[0]))."\" onclick=\"javascript:window.open(this.href,'','toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" class=\"mail\">...</a>@\\2.\\3&gt;";
      else
         $replacestr="";
   }else
      $replacestr="&lt;<span class=\"mail\">\\1</span>".$AThtml."\\2".$DOThtml."\\3&gt;";
   return "<".$tag." ".$param.">".$name."</".$tag.">".preg_replace("/\&lt\;(.+)@(.+)\.(.+)\&gt\;/",$replacestr,$noname,1);
}

function print_keyring($line){
   global $linkbase,$keyringid,$fistpub;
   $field=split(":",$line);
   switch($field[0]){
      case "pub":
         if ($fistpub==FALSE)
            print("</li></ul>");
         print("\n<ul><li class=\"pub\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<del class=\"".(($rev=="r") ? "rev" : "exp")."\">".tag_namefield("span","class=\"pubname\" title=\"".(($rev=="r") ? "[REVOCKED]" : "[EXPIRED]").$field[4]."\"",$field[9])." (".$field[5]."/".$field[6].") [".$field[2]."bits]</del>");
         else
            print(tag_namefield("a","class=\"pubname\" href=\"".$linkbase.$keyringid."/".$field[4]."\" title=\"".$field[4]."\"",$field[9])." (".$field[5]."/".$field[6].") [".$field[2]."bits]");
         $fistpub=FALSE;
         break;
      case "sub":
         print("\n<ul><li class=\"sub\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<del class=\"".(($rev=="r") ? "rev" : "exp")."\">");
         print("<span class=\"subname\" title=\"".(($rev=="r") ? "[REVOCKED]" : (($rev=="e") ? "[EXPIRED]" : "")).$field[4]."\">Subkey</span> (".$field[5]."/".$field[6].") [".$field[2]."bits]");
         if ($rev=="r" || $rev=="e")
            print("</del>");
         print("</li></ul>");
         break;
      case "uid":
         print("\n<ul><li class=\"uid\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<del class=\"".(($rev=="r") ? "rev" : "exp")."\">");
         print(tag_namefield("span","class=\"uidname\" title=\"".(($rev=="r") ? "[REVOCKED]" : (($rev=="e") ? "[EXPIRED]" : "")).$field[7]."\"",$field[9])." (".$field[5]."/".$field[6].")");
         if ($rev=="r" || $rev=="e")
            print("</del>");
         print("</li></ul>");
         break;
   }
}

function print_header($title){
   global $linkbase;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><title><? echo $title; ?></title>
<style type="text/css" media="all">@import '<? echo $linkbase; ?>css/style.css';</style></head><body>
<h1 class="title"><? echo $title; ?></h1>
<?
}

function print_footer(){
?>
<div class='footer'>Keyring manager created by &copy;Pau Rodriguez-Estivill<br />
PHPkrm project is licensed under GNU/GPL and source is <a href="http://code.google.com/p/phpkrm/">avaliable</a>.</div>
</body></html>
<?
}
?>
