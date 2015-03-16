# PHPkrm #
PHPkrm is a PHP web-based [GnuPG](http://en.wikipedia.org/wiki/GNU_Privacy_Guard) (GPG, PGP) keyring manager.

It lets anonymous visitors add their own public keys and download all keyrings, and allows synchronization with external servers to refresh the keys and/or upload the keys.

![screenshot](https://raw.githubusercontent.com/prodrigestivill/phpkrm/master/screenshot.png)

## Usage ##
  * `http://localhost/PATH/`  List all keyrings
  * `http://localhost/PATH/?q=KEYRING/KEYID`  Download the key in ASCII mode
  * `http://localhost/PATH/?q=KEYRING/download`  Download all the keyring in ASCII mode
  * `http://localhost/PATH/?q=KEYRING/print`  Full list of all keys in `KEYRING` in text mode
  * `http://localhost/PATH/?q=KEYRING/refresh`  Synchronize with keyserver to refresh keys UID's and optionaly send all keys to it

## Install ##
  * Descompress in a empty web folder
  * Configure `config.php`
  * For Rewrite:
    * Configure `$basehref` at `config.php`
    * Configure `RewriteBase` at .htaccess
  * Create the keyrings folder (`$dbpath`) with permissions of www user
  * Create empty files in the previous folder with the name of keyrings
    * The keyring name must be only alphanumeric and/or the characters `-_`
    * Files must be owned by www user
  * Add to crontab `wget http://localhost/PATH/?q=KEYRING/refresh` to sync with the keyserver to refresh keys UID's and optionaly send all keys to it

## Requieres ##
  * [PHP](http://www.php.net/)
  * [GnuPG](http://www.gnupg.org/)
  * [Apache Rewrite](http://httpd.apache.org/docs/2.2/rewrite/) (Optional)
  * [reCAPTCHA](http://mailhide.recaptcha.net/) PHP library (Optional)

## Customizations ##
  * The headers can be customized by creating in the keyrings folder (`$dbpath`) the files named as the keyring name but ending with `.php` or `.txt` for html or print version respectively.
  * The headers of the main page with keyrings list can be customized by creating a file named `list.php` in the same folder as `index.php`.
  * Using [reCAPTCHA](http://mailhide.recaptcha.net/) (Anti-SPAM method)
    * [Download](http://code.google.com/p/recaptcha/downloads/list?q=label:phplib-Latest) and save `recaptchalib.php` in the same folder as `index.php`.
    * Configure `$recaptcha_*` variables with the reCAPTCHA [mail keys](http://mailhide.recaptcha.net/apikey) and [form keys](http://recaptcha.net/api/getkey).