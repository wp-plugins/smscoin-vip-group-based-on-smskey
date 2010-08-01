SmsCoin  - VIP module based on sms:key.

-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
All information within this software product is the
intellectual property of Agregator ltd, Israel.

Given software can be used by http://smscoin.com/ clients
for sms:key service only. Any other use of the software 
is violation of the company's right and will be pursued
according to operating law.

Agregator ltd. Israel will not be held liable for any loss
or damage of any kind as a result of using this software,
including any lost revenues and/or data.
-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
This specific plug-in enables your visitors to pay in order
to access your services by sending an SMS message.
In return, user receives a short text password (key) and following its
activation user moves to VIP group. You determine the page where user will
produce the payment for moving to VIP group. Additionally, you determine which
posts will be closed with tags (Tag name) of your choice. You can do a paid
registration emulation by determining the time in VIP (VIP Time) group equals 999999 days.
In this case, following the payment for VIP status user receives the rights
to access the hidden content as long as the website exists.
The moment the time limit in VIP group has ended, user receives a letter,
the content of which is determined by you (tip: Include a link to the payment page in the letter).

To use this plug-in you have to be registered on smscoin.net
http://smscoin.net/account/register/ , approve
your e-mail account and add service sms:key.

Registration and set up process are free of charge.


Plugin installation:

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


====================================================================================================
	Attention!!! It is forbidden to display instructions for sending SMS on the same page twice
====================================================================================================


-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
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

