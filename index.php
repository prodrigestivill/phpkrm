<? 
/*
 * PHP-Web Keyring admin using GPG command line.
 *
 * Copyright 2005 Pau Rodriguez-Estivill<ensllegim@gmail.com>
 *
 * This software is licensed under the GNU General Public License.
 * See COPYING for details.
 */
// BEGIN OF CONFIGURATION
$gpgbin="/usr/local/bin/gpg";
$keyserver=""; //Specify FQDN if you want to syncronize with a keyserver
$sendkeyserver=""; //Specify FQDN if you want to send new keys to a keyserver
$dbpath="/var/keyrings/"; //Where to save the keyrings with end /
$basehref="";//Path in HTTP to use if you use rewrite else don't use.
// END OF CONFIGURATION
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
   Header("Content-type: text/plain"); 
   $keyid=substr($q,strlen($keyringid)+1);
   $torun=$gpgbin." --fingerprint --no-default-keyring --keyring ".$keyringid." --list-public-keys";
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html><head><title>
<?
echo $keyringid." Keyring"
?>
</title><style type="text/css" media="all">
<?
print("@import '".(($basehref=="") ? "" : $basehref)."style.css';");
?>
</style></head><body><p><h1>
<?
echo $keyringid." keyring"
?>
</h1><br />
<?
   print("<blockquote><a href='".(($basehref=="") ? "?q=" : $basehref).$keyringid."/download'>Download keyring</a></blockquote><br />");

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
          print("<i>Error sending to the keyserver. Contact admin.</i>");
     }
     print("<b>Key added</b><br />"); 
   }
   
   // List Keys
   $torun=$gpgbin." --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --with-colons --no-default-keyring --keyring ".$keyringid;
   $patterns = array(
       "/tru\:(.*?)*$/",
       "/pub\:r(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\<(.*?)\@(.*?)\>(.*?)\:(.*?)\:(.*?)\:(.*?)$/",
       "/pub\:e(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\<(.*?)\@(.*?)\>(.*?)\:(.*?)\:(.*?)\:(.*?)$/",
       "/pub\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\<(.*?)\@(.*?)\>(.*?)\:(.*?)\:(.*?)\:(.*?)$/",
       "/sub\:r(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)$/",
       "/sub\:e(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)$/",
       "/sub\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)$/",
       "/uid\:r(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\<(.*?)\@(.*?)\>(.*?)\:(.*?)$/",
       "/uid\:e(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\<(.*?)\@(.*?)\>(.*?)\:(.*?)$/",
       "/uid\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\:(.*?)\<(.*?)\@(.*?)\>(.*?)\:(.*?)$/",
       "/uat\:(.*?)*$/"
   );
   $replacements = array(
       "",
       "</ul><ul><li><s><b><a title=\"[REVOCKED]\\4\">\\9</a></b> &lt;\\10 &quot;AT&quot; \\11&gt; (\\5/\\6) [\\2bits]</s></li>",
       "</ul><ul><li><s><b><a title=\"[EXPIRED]\\4\">\\9</a></b> &lt;\\10 &quot;AT&quot; \\11&gt; (\\5/\\6) [\\2bits]</s></li>",
       "</ul><ul><li><b><a href=\"".(($basehref=="") ? "?q=" : $basehref).$keyringid."/\\4\" title=\"\\4\">\\9</a></b> &lt;\\10 &quot;AT&quot; \\11&gt; (\\5/\\6) [\\2bits]</li>",
       "<ul><li><s><i><a title=\"[REVOCKED]\\4\">Subkey</a> (\\5/\\6) [\\2bits]</i></s></li></ul>",
       "<ul><li><s><i><a title=\"[EXPIRED]\\4\">Subkey</a> (\\5/\\6) [\\2bits]</i></s></li></ul>",
       "<ul><li><i><a title=\"\\4\">Subkey</a> (\\5/\\6) [\\2bits]</i></li></ul>",
       "<ul><li><i><s><b><a title=\"[REVOCKED]\\7\">\\9</a></b> &lt;\\10 &quot;AT&quot; \\11&gt; (\\5/\\6)</s></i></li></ul>",
       "<ul><li><i><s><b><a title=\"[EXPIRED]\\7\">\\9</a></b> &lt;\\10 &quot;AT&quot; \\11&gt; (\\5/\\6)</s></i></li></ul>",
       "<ul><li><i><b><a title=\"\\7\">\\9</a></b> &lt;\\10 &quot;AT&quot; \\11&gt; (\\5/\\6)</i></li></ul>",
       ""
   );
   print("</p><ul>");
   $handle=popen($torun,"r");
   while( !feof($handle) )
   {
       print(preg_replace($patterns,$replacements, fgets($handle,4096)));
   }
   pclose($handle);
   print ("</ul>");

/*   echo "<pre>";
   $torun=$gpgbin." --list-public-keys --list-options show-uid-validity,show-unusable-uids,show-unusable-subkeys,show-sig-expire --no-default-keyring --keyring ".$keyringid;
   system($torun);
   echo "</pre>";*/

?>
<p>Paste an ascii-armored of the key(s) that you wish to add to the keyring.</p><p>
<?
   print("<form method='post' action='".(($basehref=="") ? "?q=" : $basehref).$keyringid."' enctype='multipart/form-data'>");
?>
<textarea name='pastekey' cols='55' rows='6'></textarea>
<br />Upload your file directly: <input type='file' name='upkey' /><br />
<input type='submit' /></form><br />
<hr width='75%' /><center><i><font size=-3>Keyring manager created by &copy;Pau Rodriguez-Estivill
<br />PHPkrm project is licensed under GNU/GPL and source is <a href="http://asterx.upc.es/keyrings/source/">avaliable</a>.</i></font>
</center></p></body></html>
<?
}else{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html><head><title>Keyrings</title>
<style type="text/css" media="all">
<?
print("@import '".(($basehref=="") ? "" : $basehref)."style.css';");
?>
</style></head><body><p><h1>List of keyrings</h1><br /><ul>
<?
   if ($handle = opendir($dbpath)) {
    while (false !== ($lkrid = readdir($handle))) {
     if (preg_match("/^[\w]*$/", $lkrid))
         print("<li><a href='".(($basehref=="") ? "?q=" : $basehref).$lkrid."'>".$lkrid." keyring</a></li>");
    }
   closedir($handle);
   }else
     print("Can't list directory.");
?>
</ul><br /></p><p>
<hr width='75%' /><center><i><font size=-3>Keyring manager created by &copy;Pau Rodriguez-Estivill
<br />PHPkrm project is licensed under GNU/GPL and source is <a href="http://asterx.upc.es/keyrings/source/">avaliable</a>.</i></font>
</center></p></body></html>
<?
}
?>


