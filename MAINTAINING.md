### Overview of Code Structures
- /abstract: contains abstract/interface class
  - abstract.midtrans-gateway.php: refer to that file comments. Main blueprint implementation for the main gateway
  - abstract.midtrans-gateway-sub.php: refer to that file comments. main blueprint implementation for the Separated Payment Buttons gateway, see below section.
- /class: the concrete class implementations for each of
- /midtrans-gateway.php: refer to that file comments.
- /lib: Midtrans API PHP library, commited dependency, need be manually updated to latest Midtrans PHP library on github/composer
- /public: public asset folder for images, css, js on user facing UI
  - /images/payment-methods: folder of payment method icons
    - all image directly used should not be prefixed with `alt_`
    - if filename is prefixed with `alt_`, it is not directly used, and only there as alternative image.
- /readme: required file, act as WP plugin manifest, see [this reference](https://wordpress.org/plugins/readme.txt).

Other:
- WC/WP have hook functions that will be auto called when certain action are triggered on WC system, e.g: when payment is initiated, when refund occurs, when thank you page showed, etc.
  - see ref of "docs WC Payment Gateway" below, to know which functions are built-in function from WC. If not defined there, likely it is our own custom/helper function.
  - so most of functions implemented in the code are to implement those built-in functions.

#### References:
- WC PG development guide: https://docs.woocommerce.com/document/payment-gateway-api/
- docs WC Order: https://woocommerce.github.io/code-reference/classes/WC-Order.html
- docs WC Payment Gateway: https://woocommerce.github.io/code-reference/classes/WC-Payment-Gateway.html
- WC hooks guide: https://woocommerce.github.io/code-reference/hooks/hooks.html
- official WP plugin dev guide: https://developer.wordpress.org/plugins/intro/
- sample [PG plugin implementation](https://github.com/woocommerce/woocommerce-gateway-stripe/) from official WC team. [Some other](https://github.com/woocommerce?q=gateway&type=&language=&sort=).
- External WC PG dev guide: https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/
- WP get_options() functions: https://developer.wordpress.org/reference/functions/get_option/

### Attention
- Due to the feature of "custom order_id suffix to prevent duplicated order_id", order_id input and output may need to be handled non-traditionally, look for `@TAG: order-suffix-separator` in the code comments. e.g:
  - when sending order_id to Midtrans, it may need to go thru func `WC_Midtrans_Utils::generate_non_duplicate_order_id`
  - when receiving order_id, it may need to go thru func `WC_Midtrans_Utils::check_and_restore_original_order_id`

### Separted Payment Buttons
To implement separated payment buttons (separate WC payment gateway) for each of Midtrans' supported payment methods, the following implementations are made:
- within `/class/sub-specific-buttons` those are the class files
  - these class extends abstract `/abstract/abstract.midtrans-gateway-sub.php`
  - which extends main gateway/button `/class/abstract.midtrans-gateway.php`, and most of the core logic are using the logic in implemented in this file. Like: Snap API calling, Notif handling, etc.
  - which extends main abstract `/abstract/abstract.midtrans-gateway.php`
  - so becareful when modifying these chain of files, as it may impact many other files.
- each of them is imported into `/midtrans-gateway.php` to register the buttons into WC

Quick guide to add new separate button for future payment methods:
- copy one of the file at `/class/sub-specific-buttons`, e.g: `class.midtrans-gateway-sub-gopay.php` as template. Rename the new file into e.g: `class.midtrans-gateway-sub-bni-va.php`
- within the file, replace all the `gopay` keyword with the new payment method's keyword e.g: `bni_va`
  - mind the upper/lower case
- within file `/midtrans-gateway.php`: 
  - add code to import `
require_once dirname( __FILE__ ) . '/class/sub-specific-buttons/class.midtrans-gateway-sub-bni-va.php';`
  - add code to register WC gateway `
  $methods[] = 'WC_Gateway_Midtrans_Sub_BNI_VA';`
- add new image files (for the payment method's icon) into `/public/images/payment-methods`. e.g: `bni_va.png`
- also change the image file names values, you can refer to the file's code comments.

Alternatively, you can also refer to commit history of when a separate button is added, for example one commit w/ msg: `add basic separate button gateway: card`.

Note: this section may not be frequently updated and may become outdated. In the case the code itself is more updated than this section, please refer to the code itself.

### User Guide
- Some of specific featues of this plugins are documented on Github wiki: https://github.com/veritrans/SNAP-Woocommerce/wiki. @TODO: need to centralize this to Midtrans tech docs?
- Some are in [README.md](./README.md)
- Some are in [Midtrans tech docs](https://docs.midtrans.com/en/snap/with-plugins?id=wordpress-woocommerce)

### Releasing / Publishing Plugins to Wordpress
Plugin WP Hosted url: https://wordpress.org/plugins/midtrans-woocommerce/

**HOW TO:**

#### Prepare: Clone svn repo from Wordpress to local
- This step only required once, to make sure you have the svn cloned on local folder
- Prepare a separated folder from this github repo folder on your local
- Clone the SVN Repo URL: https://plugins.svn.wordpress.org/midtrans-woocommerce/
  - Ref on SVN guidelines:
    - https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
    - https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
    - https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/
    - https://wordpress.org/plugins/about/readme.txt
    - https://wordpress.org/plugins/about/validator/
    - https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
- Run `svn update` to get update from remote repo to local repo

#### Update plugin
- On Github repo folder, update version compatibility & tested up to in these files:
  - `midtrans-gateway.php`:
    - `Version:` {current plugin version: x.x.x}
    - `WC tested up to:` {latest WC version: x.x.x}
  - `readme.txt`:
    - `Requires at least:` {min version of WP, rarely changes: x.x.x}
    - `Tested up to:` {latest WP version: x.x.x}
    - `Stable tag:` {latest/stable version of this plugin (must have its own /trunk folder): x.x.x}
- Copy contents of Github root folder `Snap-Woocommerce` into your SVN folder, under `trunk/` folder
- Create new folder under `tags/` folder, name it with the plugin version. e.g: `2.6.3`
  - or alternatively, better use [SVN command to do it](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#create-tags-from-trunk) by running script `svn copy trunk tags/2.6.3`. SVN will copy the trunk folder into new version tag folder.
- Ensure `Stable tag` value within `readme.txt` in folder `trunk/` have the same value as above e.g: `2.6.3`
  - values that need to be consistent:
    - stable tag in readme.txt `trunk/`
    - stable tag in readme.txt `tags/[new version folder]/`
    - version in `midtrans-gateway.php`

Note, alternatively can also:
- First, svn push the `tags/[new version folder]/`, ensure have been pushed on WP svn.
- Then, edit the readme / trunks version value to match the new version, then push svn again.

Note, if you are deleting commited files:
- SVN will not auto remove files commited in repo, removing commited files in SVN need to be 1by1, here is helper script to bulk remove commited files:
  - run script in terminal from your svn repo: `svn rm $( svn status | sed -e '/^!/!d' -e 's/^!//' )`
    - src: https://stackoverflow.com/a/9600437

#### Push to SVN
Run terminal command:
- `svn add tags/*`
- `svn add trunk/*`
- `svn add assets/*`
- `svn ci -m '<commit message>' --username <your WP SVN username> --password <your WP SVN password>`

Alternatively, can also create helper script `update_and_push_to_wp.sh` file:
```
#!/usr/bin/env bash
svn add tags/* --force; 
svn add trunk/* --force; 
svn add assets/* --force;
# svn up; 
svn ci -m 'update' <your WP SVN username> --password <your WP SVN password>;
```