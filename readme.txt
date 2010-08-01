=== SmsCoin - VIP group, based on sms:key ===
Contributors: smscoin
Donate link: http://smscoin.com/
Tags:plugin, vip, hide, group, payment, billing, sms, content, post
Requires at least: 2.5
Tested up to: 2.9.2
Stable tag: 0.2

The following plugin enables you to provide your users with paid access to the
content published on your website.

== Description ==

To the website page or blog post you need to add tags as follows [smscoin_vip] hidden content [/smscoin_vip].
To access the content hidden between these tags user have to send an SMS to a short code first, then user is
moved to VIP group. VIP user will be able to access the content you hide between the following tags
[smscoin_vip] hidden content [/smscoin_vip].

You choose your payment page by specifying in the text of the article or blog post the
following tag [SMSCOINPAY], and instead of the tag user will see the instructions to send an SMS.
The moment time limit in VIP group you set yourself in plugin settings expires, user no longer
can access the hidden content. User receives an e-mail written by you in plugin settings.

To use the following you must register first on smscoin.net website and to connect
sms:key service as well.

sms:key service registration and setup process are absolutely free.

Localization: 

	Русский, English

For using this module you have to be registered at sms billing site:

       English:	http://smscoin.net/account/register/
	   
       Русский: http://smscoin.com/account/register/
	   
	   
Plugin page:

       English:	http://smscoin.net/software/engine/WordPress/
	   
       Русский: http://smscoin.com/software/engine/WordPress/

== Installation ==

1. You have to copy "smscoin_rpayment" folder from archive to: wp-content/plugins plugin

2.  Go to admin panel of your "WordPress", in Plugins section, You will see
"SmsCoin VIP", activate the plugin.

3. Go to "SmsCoin VIP" menu choose "Settings" section, and follow the instructions for
plugin configuration.

	*Enter you sms:key ID
	*Choose default language
	*Enter secret code from sms:key settings
	*Enter tag for hiding text (default value is rpayment)
		example [rpayment] whatever you choose to hide [/rpayment]
	*Specify tag for payment page display
		example enter [SMSCOINPAY] on any page of your choice
	*Time limit on VIP group in days
	*Email subject
	*Email

4. Go to "SmsCoin VIP" menu, choose "Rates" section, and click the "Update rates" button.

5. Set write permissions (766) to the following files 
	/wp-content/plugins/smscoin_rpayment/data/local.js
	/wp-content/plugins/smscoin_rpayment/data/local.json

	Attention!!! It is forbidden to display instructions for sending SMS on the same page twice


-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-
 sms:key service set up:

 In control panel, on smscoin.com website, go to sms:key service settings

1. Activate option: Password transfer.

2. Specify the remote handler address:
	http://yoursite.com/wp-content/plugins/smscoin_rpayment/result.php

3. Specify Password for remote handler request signature.

-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-

 Support
-------------
 For further questions, please contact SmsCoin technical support
via support@smscoin.com

== Frequently Asked Questions ==

For using this module you have to be registered at sms billing site:

       English:	http://smscoin.net/account/register/
	   
       Русский: http://smscoin.com/account/register/
	   
Plugin page:

       English:	http://smscoin.net/software/engine/WordPress/
	   
       Русский: http://smscoin.com/software/engine/WordPress/

== Arbitrary section ==

For using this module you have to be registered at sms billing site:

       English:	http://smscoin.net/account/register/
	   
       Русский: http://smscoin.com/account/register/
	   
Plugin page:

       English:	http://smscoin.net/software/engine/WordPress/
	   
       Русский: http://smscoin.com/software/engine/WordPress/

