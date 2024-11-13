# OOPSpam Anti-Spam for Craft CMS

A privacy friendly anti-spam utility to safeguard your website and customers.

![Screenshot](resources/oopspam.png)

## Requirements

This plugin requires Craft CMS 4.0.0 or later.

## Installation

`composer require cloudgrayau/oopspam`

## OOPSpam Overview

OOPSpam is a privacy friendly anti-spam utility for protecting forms, user registrations and comments in Craft CMS.

OOPSpam is a modern spam filter that uses machine learning to analyse messages, checking each submission against an extensive database of over 500 million IPs and emails to effectively detect and block spam. The OOPSpam API protects over 3.5 million websites daily.

A valid API key from the [OOPSpam Service](https://oopspam.com/?ref=cloudgray) is required to use this plugin.

![Screenshot](resources/craftoopspam.jpg)

## Protection

The OOPSpam plugin protects the following services from spam and includes optional logging and reporting in the Craft CMS dashboard.

### User Registration Protection

Protects user registrations from spam.

### Form Protection

Protects form submissions from spam. The current form integrations are protected:

**✓ Formie** - [https://plugins.craftcms.com/formie](https://plugins.craftcms.com/formie)  
**✓ Freeform** - [https://plugins.craftcms.com/freeform](https://plugins.craftcms.com/freeform)  
**✓ Contact Form** - [https://plugins.craftcms.com/contact-form](https://plugins.craftcms.com/contact-form)  
**✓ Wheel Form** (> 4.0.2) - [https://plugins.craftcms.com/wheelform](https://plugins.craftcms.com/wheelform)  
**✓ Express Forms** (no longer maintained) - [https://plugins.craftcms.com/express-forms](https://plugins.craftcms.com/express-forms)  
**✓ Custom Forms** - requires custom programming

### Comment Protection

Protects comment submissions from spam. The current comment integrations are protected:

**✓ Comments** - [https://plugins.craftcms.com/comments](https://plugins.craftcms.com/comments)  
**✓ Custom Comments** - requires custom programming

## Custom Protection

Any form or comment logic can be protected by OOPSpam via a custom plugin/module controller.

The `email` and `content` params are required. The `checkForLength` parameter is optional and can be set to override the configuration value.

    <?php    
    $params = [
      'email' => '<EMAIL>',
      'content' => '<MESSAGE>',
      'checkForLength' => true /* optional */
    ];
    if (\cloudgrayau\oopspam\OOPSpam::checkSpam($params, '<FORM LABEL>')){ /* passed */
    }
    ?>

Brought to you by [Cloud Gray Pty Ltd](https://cloudgray.com.au/)