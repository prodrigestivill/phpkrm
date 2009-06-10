<? ob_start(); ?>
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

      //Show keyring logo / $keyringid-logo.png
      $logoimg = ("$dbpath$keyringid-logo.png");
      if (file_exists($logoimg))
      print("<img src=\"$dbpath/$keyringid-logo.png\" width=\"150\" height=\"100\" align=\"right\" vspace=\"10\"></a>");
      else   

?>
<div class="keyringoptions"><a class="download" href="<? echo $linkbase.$keyringid; ?>/download">Download all</a>&nbsp;|&nbsp;<a class="print" href="<? echo $linkbase.$keyringid; ?>/print">Printing version</a>&nbsp;|&nbsp;<a class="keyring" href="<? echo $linkbase; ?>">List of keyrings</a></div><div class="bodykeyring" id="keyring<? echo $keyringid; ?>">
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

      //Generate photos
      $torun=$gpgbin." --display-charset utf-8 --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire,show-photos --photo-viewer \"/bin/cat > $dbpath/photo-%K.%t\" --no-default-keyring --keyring ".$keyringid;
      $handle=popen($torun,"r");
      pclose($handle);

      //Show all small photos
      $torun=$gpgbin." --display-charset utf-8 --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --with-colons --no-default-keyring --keyring ".$keyringid;
      $handle=popen($torun,"r");
      while(!feof($handle)) {
	  $line=htmlspecialchars(fgets($handle,4096));
          $field=split(":",$line);
	  if ($field[0]=="pub") {
	     if (file_exists("$dbpath/photo-$field[4].jpg")) {
		list($width, $height, $type, $attr) = getimagesize("$dbpath/photo-$field[4].jpg");
		print("<a href=\"javascript: PHOTO=window.open('$dbpath/photo-$field[4].jpg', 'foto', 'width=$width, height=$height, toolbar=no, status=no, location=no, menubar=no, resizable=no, scrollbars=no'); PHOTO.focus();\"><img src=\"$dbpath/photo-$field[4].jpg\" width=\"75\" height=\"75\"></a>");
		print("&nbsp");
	     } 
          }
      }
      pclose($handle);
      
      // List Keys
      printf("<br><br>");
      $torun=$gpgbin." --display-charset utf-8 --list-sig --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --with-colons --no-default-keyring --keyring ".$keyringid;
      $fistpub=TRUE;
      $handle=popen($torun,"r");
      while(!feof($handle))
         print_keyring(htmlspecialchars(fgets($handle,4096)));
      pclose($handle);
      if ($fistpub==FALSE)
         print("</li></ul>");
?>
</div><div class="addkey">
<form method='post' action='<? echo $linkbase.$keyringid; ?>' enctype='multipart/form-data'>
<div class="formaddkey" style='width: 40em'>Paste an ascii-armored of the key(s) to add in this keyring:<br />
<textarea name='pastekey' cols='65' rows='5' style='width: 100%;'></textarea><br />
<span style='float:left;'>Or upload your key file directly:</span><span style='float: right;'><input type='file' name='upkey' /></span></div>
<? if ($recaptcha_form_pubkey!="") { ?>
<div class="capchaaddkey">
<?    if ($captchavalid==FALSE)
         print("<p class=\"error\">reCAPTCHA said: ".$res->error.".</p>");
      echo recaptcha_get_html($recaptcha_form_pubkey); ?></div>
<? }?>
<div class="clear"><input type='submit' /><br /></div></form></div>
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
	   
?> 
<div class="login"><ul>
<form action ="<? echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
<b>Login:</b><input type="text" name="userid" size="10" maxlength="20" />
<b>Pass:</b><input type="password" name="userpass" size="10" maxlength="20"/>
<input type="submit" name="Manage keyrings" id="submit" value="Manage keyrings" onClick="window.location.href=window.location.href" style="boarder: lpx solid gray; background-color: white" />

<?	

	if ((isset($_POST['userid'])) && (isset($_POST['userpass']))){
		if (($_POST['userid']!="") && ($_POST['userpass']!="")){
			if (($_POST['userid']=="$login") && ($_POST['userpass']=="$pass")){
				header("Location: admin.php");
				}
				else {
					if (($_POST['userid']!="$login") || ($_POST['userpass']!="$pass"))		
						print("<li class=\"loginerror\">Wrong login.</li>");
					}
				
		}
	} else print("");
	
?></div></ul><?
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
         printf("<br><br><hr>");

	 //Show small photo
	 global $dbpath;
	 if (file_exists("$dbpath/photo-$field[4].jpg")) {
		list($width, $height, $type, $attr) = getimagesize("$dbpath/photo-$field[4].jpg");
		print("<a href=\"javascript: PHOTO=window.open('$dbpath/photo-$field[4].jpg', 'foto', 'width=$width, height=$height, toolbar=no, status=no, location=no, menubar=no, resizable=no, scrollbars=no'); PHOTO.focus();\"><img src=\"$dbpath/photo-$field[4].jpg\" width=\"75\" height=\"75\" align=\"left\"></a>");
	 } 
	 else {
	   print("<img src=\"css/nophoto.jpg\" width=\"79\" height=\"79\" align=\"left\"></a>");
	 }

         print("\n<ul><li class=\"pub\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<del class=\"".(($rev=="r") ? "rev" : "exp")."\">".tag_namefield("span","class=\"pubname\" title=\"".(($rev=="r") ? "[REVOCKED]" : "[EXPIRED]").$field[4]."\"",$field[9])." (".$field[5]." / ".$field[6].") [".$field[2]."bits]</del>");
         else
            print(tag_namefield("a","class=\"pubname\" href=\"".$linkbase.$keyringid."/".$field[4]."\" title=\"".$field[4]."\"",$field[9])." (".$field[5]."".$field[6].") [".$field[2]."bits]");
         $fistpub=FALSE;
	 printf(" $field[4] ");
         break;
      case "sub":
         print("\n<ul><li class=\"sub\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<del class=\"".(($rev=="r") ? "rev" : "exp")."\">");
         print("<span class=\"subname\" title=\"".(($rev=="r") ? "[REVOCKED]" : (($rev=="e") ? "[EXPIRED]" : "")).$field[4]."\">Subkey</span> (".$field[5]." / ".$field[6].") [".$field[2]."bits]");
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
      case "sig":
	print("\n<ul><li class=\"sig\">");
	$adress="http://pgpkeys.pca.dfn.de/pks/lookup?search=0x";
	$rev=substr($field[1], 0, 1);
	if ($rev=="r" || $rev=="e")
	    print("<del class=\"".(($rev=="r") ? "rev" : "exp")."\">");
	if ($field[9]=="[User ID not found]"){
	?><a href="<? print ($adress.$field[4]."&op=index") ?>">UNKNOWN </a><?
	}
	print(tag_namefield("span","class=\"signame\" title=\"".(($rev=="r") ? "[REVOCKED]" : (($rev=="e") ? "[EXPIRED]" : "")).$field[7]."\"",$field[9])." (".$field[5]." / ".$field[6].")");
	if ($rev=="r" || $rev=="e")
	    print("</del>");
	print("</li></ul>");
	break;

   }
}

function print_header($title){
   global $basehref;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><title><? echo $title; ?></title>
<style type="text/css" media="all">@import '<? echo $basehref; ?>css/style.css';</style></head><body>
<h1 class="title"><? echo $title; ?></h1>
<?
}

function print_footer(){
?>
<div class="clear"><br /></div>
<div class="footer">Keyring manager created by &copy;Pau Rodriguez-Estivill<br />
PHPkrm project is licensed under GNU/GPL and source is <a href="http://code.google.com/p/phpkrm/">avaliable</a>.</div>
</body></html>
<?
}
?>
<? ob_flush(); ?>

