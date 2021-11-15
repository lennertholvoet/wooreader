# Wooreader
## Show digital content to users who bought the corresponding item in the WooCommerce Webshop
## Work In Progress 
This plugin is a work in progress, and only tested on a local machine.
Though I plan to advance quickly in development, all commits until a version 1.0 is released are not adviced to install on a production Wordpress site.
Code is messy now, since I'm getting accustomed with Wordpress Plugin Development. So the plan is to first, get a working version, and then clean up the spaghetti towards version 1.0, whick will be released in a public repository.
## Yet another Wordpress Plugin
WooReader is a plugin to show digital files on your website to user who purchased something via your WooCommerce shop.
These files could be one on one for a digtal sale, but also to give extra documents to customers who purchased a fysical work.
## Functions
 - [x] Create a Digital Document
 - [x] Set basic metadata (Title And Author)
 - [x] Add more advanced metadata
   - [x] ISBN
 - [x] Upload Files to Digital Document
 - [x] Set Main File and Cover Image for Digital Document
 - [ ] Method to use subscription from WooCommerce to access all content
 - [x] Link Digital Document to WooCommerce Shop Item (!)
 	- [x] Create interface : searchable
 	- [x] Save connections to DB in UUID - SKU pairs
 - [ ] Create Page for Logged In Users with all Digital Documents linked to WooCommerce Shop Items they bought (!)
  - [x] First setup is done
  - [ ] Display bought items with digital content linked
 - [ ] Reader
   - [ ] Browsable by WooCommerce Category
 - [ ] Create Backup / Import option
 	- [ ] Export database
 	- [ ] Zip uploads/wooreader + SQL Files
 	- [ ] Extract ZIP + database import
 - [ ] localization of admin
 - [ ] localization of frontend