<? 
/*
 * PHPkrm is a Web-based GnuPG keyring manager.
 * To administrate the keyrings use the gpg command line tool.
 * Configuration File Template.
 *
 * This software is licensed under the GNU General Public License.
 * See index.php for authoring details.
 * See COPYING for details.
 */

$gpgbin="/usr/bin/gpg";
$keyserver=""; //Specify FQDN if you want to syncronize with a keyserver
$sendkeyserver=""; //Specify FQDN if you want to send new keys to a keyserver
$dbpath="keyrings/"; //Where to save the keyrings with end /
$basehref="";//Path in HTTP to use if you use rewrite else don't use.
$login=""; //Type user name who can manage with keyrings
$pass=""; //Type password to login to manage window

//Anti-SPAM substitutions in html and txt mode
$AThtml="<b>&nbsp;&nbsp;AT&nbsp;&nbsp;</b>";
$DOThtml="<b>&nbsp;&nbsp;DOT&nbsp;&nbsp;</b>";
$ATtxt="  AT  ";
$DOTtxt="  DOT  ";

/* Begin reCAPTCHA (optional) - http://recaptcha.net/ */
//Hide e-mails using capcha (e-mails in text mode disabled)
//Get the keys at http://mailhide.recaptcha.net/apikey
$recaptcha_mail_pubkey="";
$recaptcha_mail_privkey="";

//Protect form submit using CAPCHA
//Get the keys at http://recaptcha.net/api/getkey
$recaptcha_form_pubkey="";
$recaptcha_form_privkey="";
/* End reCAPTCHA */

/* End of Configuration File */
?>
