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
$pastekey=$_POST['pastekey'];
$filekey=$_FILES['upkey']['tmp_name'];
$q=$_GET['q'];
if (is_bool(strpos($q, "/")))
   $keyringid=$q;
else
   $keyringid=substr($q, 0 ,strpos($q, "/"));

//Check if keyring is correct.
if (!($keyringid!="" && file_exists($dbpath.$keyringid) && 
    preg_match("/^[\w]*$/", $keyringid))){
   $keyringid="";
}
putenv("GNUPGHOME=".$dbpath);

//Check to get URL
if ($keyringid!=""){
    $url="http";
    if ($_SERVER['HTTPS']=='on') $url.="s";
    $url.="://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/";
    if (preg_match("/\"/", $url)) //Ilegal security issue
       $url="";
    else
       $url.=(($basehref=="") ? "?q=".$keyringid : $keyringid);
}else
    $url="";

if ($keyringid!="" && strlen($q)>9 && substr($q,-9)=="/download") {
   //Download Keyring
   Header("Content-type: text/plain"); 
   $torun=$gpgbin." --no-version --comment \"".$keyringid." phpkrm keyring: ".$url."\" -a --export --no-default-keyring --keyring ".$keyringid;
   system($torun,$result);
   if ($result!=0)
      print("Error using gpg. Contact admin.");
}elseif ($keyringid!="" && $keyserver!="" && strlen($q)>8 && substr($q,-8)=="/refresh") {
   //Refresh keys from keyserver
   Header("Content-type: text/plain"); 
   print("Actualiting keys from ".$keyringid." keyring with ".$keyserver." keyserver.\n");
   $torun=$gpgbin." -v --refresh-keys --keyserver ".$keyserver." --no-default-keyring --keyring ".$keyringid;
   system($torun,$result);
   if ($sendkeyserver!="") {
       print("Sending keys from ".$keyringid." keyring to ".$sendkeyserver." keyserver.\n");
       $torun=$gpgbin." -v --send-keys --keyserver ".$sendkeyserver." --no-default-keyring --keyring ".$keyringid;
       system($torun,$result);
   }
}elseif ($keyringid!="" && $keyserver!="" && strlen($q)>6 && substr($q,-6)=="/print") {
   //List of keys
   Header("Content-type: text/plain; charset=UTF-8");
   if (file_exists($dbpath.$keyringid.".txt"))
       include($dbpath.$keyringid.".txt");
   $keyid=substr($q,strlen($keyringid)+1);
   $torun=$gpgbin." --display-charset utf-8 --fingerprint --no-default-keyring --keyring ".$keyringid." --list-public-keys";
   system($torun,$result);

}elseif ($keyringid!="" && strlen($q)>(strlen($keyringid)+1)){
   //Download a key
   Header("Content-type: text/plain"); 
   $keyid=substr($q,strlen($keyringid)+1);
   $torun=$gpgbin." --no-version --comment \"From ".$keyringid." phpkrm keyring ".$url."\" -a --no-default-keyring --keyring ".$keyringid." --export ".$keyid;
   if (preg_match("/^[\w]*$/", $keyid))
      system($torun,$result);
   else
      $result=0;

   if ($result!=0)
      print("Error using gpg. Contact admin.");

}elseif ($keyringid!=""){
   Header("Content-type: text/html; charset=UTF-8");
   if (file_exists($dbpath.$keyringid.".php")){
       include($dbpath.$keyringid.".php");
       print("\n<!-- End header file ".$dbpath.$keyringid.".php -->\n");
   }else{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><title><? echo $keyringid; ?> Keyring</title>
<style type="text/css" media="all">@import '<? echo (($basehref=="") ? "" : $basehref); ?>css/style.css';</style></head><body>
<h1><? echo $keyringid; ?> keyring</h1>
<?
   }
?>
<blockquote><p><a class="download" href="<? echo (($basehref=="") ? "?q=" : $basehref).$keyringid; ?>/download">Download all</a>&nbsp;|&nbsp;<a class="print" href="<? echo (($basehref=="") ? "?q=" : $basehref).$keyringid; ?>/print">Printing version</a></p></blockquote><p><br />
<?
   //Save pasted key
   if ($pastekey!="") {
     $filekey = tempnam("/tmp", "pastekey.asc");
     $fp = fopen($filekey, "w");
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
     print("<p class=\"info\">Key added</p>"); 
   }
   
   // List Keys
   $torun=$gpgbin." --display-charset utf-8 --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --with-colons --no-default-keyring --keyring ".$keyringid;
   $fistpub=TRUE;
   print("</p>");
   $handle=popen($torun,"r");
   while( !feof($handle) )
      $fistpub=print_keyring(htmlspecialchars(fgets($handle,4096)),$basehref,$keyringid,$fistpub);
   pclose($handle);
   if ($fistpub==FALSE)
      print("</ul>");



/*   echo "<pre>";
   $torun=$gpgbin." --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --no-default-keyring --keyring ".$keyringid;
   system($torun);
   echo "</pre>";*/

?>
<p>Paste an ascii-armored of the key(s) that you wish to add to the keyring.</p>
<form method='post' action='<? echo (($basehref=="") ? "?q=" : $basehref).$keyringid; ?>' enctype='multipart/form-data'><p>
<textarea name='pastekey' cols='55' rows='6'></textarea>
<br />Upload your file directly: <input type='file' name='upkey' /><br />
<input type='submit' /><br /></p></form>
<?
   print_footer();
}else{
   Header("Content-type: text/html; charset=UTF-8");
   if (file_exists("list.php")){
       include("list.php");
       print("\n<!-- End header file list.php -->\n");
   }else{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><title>List of keyrings</title>
<style type="text/css" media="all">@import '<? echo (($basehref=="") ? "" : $basehref); ?>css/style.css';</style></head><body>
<h1>List of keyrings</h1>
<?
   }
   print("<ul>");
   if ($handle = opendir($dbpath)) {
    while (false !== ($lkrid = readdir($handle))) {
     if (preg_match("/^[\w]*$/", $lkrid))
         print("<li><a class='keyring' href='".(($basehref=="") ? "?q=" : $basehref).$lkrid."'>".$lkrid." keyring</a></li>");
    }
   closedir($handle);
   }else
     print("<li class=\"error\">Can't list directory.</li>");
?>
</ul><p><br /></p>
<?
     print_footer();
}

function tag_namefield($tag,$param,$namem){
   $noname = stristr($namem, " &lt;");
   if ($noname==FALSE)
      $name = $namem;
   else
      $name = substr($namem, 0, -(strlen($noname)));
   return "<".$tag." ".$param.">".$name."</".$tag.">".preg_replace("/(.*)@(.*)\.(.*)/","\\1 at \\2 dot \\3",$noname, 1);
}

function print_keyring($line,$basehref,$keyringid,$ret){
   $fistpub=$ret;
   $field=split(":",$line);
   switch($field[0]){
      case "pub":
         if ($fistpub==FALSE)
            print("</ul>");
         print("\n<ul><li class=\"pub\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<span class=\"".(($rev=="r") ? "rev" : "exp")."\">".tag_namefield("span","class=\"pubname\" title=\"".(($rev=="r") ? "[REVOCKED]" : "[EXPIRED]").$field[4]."\"",$field[9])." (".$field[5]."/".$field[6].") [".$field[2]."bits]</span>");
         else
            print(tag_namefield("a","class=\"pubname\" href=\"".(($basehref=="") ? "?q=" : $basehref).$keyringid.$field[4]."\" title=\"".$field[4]."\"",$field[9])." (".$field[5]."/".$field[6].") [".$field[2]."bits]");
         print("</li>");
         $fistpub=FALSE;
         break;
      case "sub":
         print("\n<li><ul><li class=\"sub\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<span class=\"".(($rev=="r") ? "rev" : "exp")."\">");
         print("<span class=\"subname\" title=\"".(($rev=="r") ? "[REVOCKED]" : (($rev=="e") ? "[EXPIRED]" : "")).$field[4]."\">Subkey</span> (".$field[5]."/".$field[6].") [".$field[2]."bits]");
         if ($rev=="r" || $rev=="e")
            print("</span>");
         print("</li></ul></li>");
         break;
      case "uid":
         print("\n<li><ul><li class=\"uid\">");
         $rev=substr($field[1], 0, 1);
         if ($rev=="r" || $rev=="e")
            print("<span class=\"".(($rev=="r") ? "rev" : "exp")."\">");
         print(tag_namefield("span","class=\"uidname\" title=\"".(($rev=="r") ? "[REVOCKED]" : (($rev=="e") ? "[EXPIRED]" : "")).$field[7]."\"",$field[9])." (".$field[5]."/".$field[6].")");
         if ($rev=="r" || $rev=="e")
            print("</span>");
         print("</li></ul></li>");
         break;
   }
   return $fistpub;
}

function print_footer(){
?>
<hr /><p class='footer'>Keyring manager created by &copy;Pau Rodriguez-Estivill
<br />PHPkrm project is licensed under GNU/GPL and source is <a href="http://code.google.com/p/phpkrm/">avaliable</a>.</p>
</body></html>
<?
}
?>
