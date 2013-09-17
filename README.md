GuilroProtectionProxyBundle
===========================

Installation
------------


Add this bundle to your `composer.json` file:
```json
{
    "require": {
        "guilro/protection-proxy-bundle": "dev-master"
    }
}
```

Register the bundle in app/AppKernel.php:

```php
<?php

// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Guilro\ProtectionProxyBundle\GuilroProtectionProxyBundle(),
    );
}
```

Usage
-----

You have to configure the protected classes and methods (for the moment in config.yml).

```yaml
# app/config/config.yml

guilro_protection_proxy:
    protected_classes:
        Acme\BlogBundle\Comment:
            methods:
                getTitle:
			attribute: ROLE_USER #can be a role, or any attribute that a voter can handle
                setAuthor:
			attribute: attribute2
			return_proxy: true

```

Typicall usage in your controllers and views:

```php
$em->getRepository('AcmeBlogBundle:Comment')->find(342);

$manager = $this->get('guilro.protection_proxy');

$proxy_comment = $manager->getProxy($comment);

$this->render(
    'AcmeBlogBundle:Comment:show.twig.html',
    array('comment' => $proxy_comment)
);
```

When called by the views, methods `getTitle()` and `setAuthor()` of `$comment` will only be
really executed if `$this->get('security.context')->isGranted('attribute', $comment)`
returns `true`. Otherwise nothing will happen and the methods return null.
If the original method returns a protected object, it will return the object or its protection proxy
depending on `return_proxy` setting. Default for this setting is `false`.

You should probably implements your own [Voter](http://symfony.com/doc/current/cookbook/security/voters.html)
in order to grant access or not to users.
