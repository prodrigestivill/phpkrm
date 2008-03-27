<? 
/*
 * PHP-Web Keyring admin using GPG command line.
 * Configuration File
 *
 * This software is licensed under the GNU General Public License.
 * See index.php for authoring details.
 * See COPYING for details.
 */

$gpgbin="/usr/bin/gpg";
$keyserver=""; //Specify FQDN if you want to syncronize with a keyserver
$sendkeyserver=""; //Specify FQDN if you want to send new keys to a keyserver
$dbpath="/var/keyrings/"; //Where to save the keyrings with end /
$basehref="";//Path in HTTP to use if you use rewrite else don't use.

/* End of Configuration File */
?>
