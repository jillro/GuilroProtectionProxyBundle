GuilroProtectionProxyBundle
===========================

Installation
------------

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
// app/config/config.yml

guilro_protection_proxy:
    protected_classes:
        Acme\BlogBundle\Comment:
            methods:
                getTitle: attribute
		setAuthor: attribute2
```

Typicall usage in your controllers and views:

```php
$em->getRepository('AcmeBlogBundle:Comment')->find(342);

$manager = $this->get('guilro.protection_proxy');

$proxy_comment = $manager->getProxy($comment);

$this->render('AcmeBlogBundle:Comment:show.twig.html', array('comment' => $proxy_comment));
```

When called by the view, methods `getTitle()` and `setAuthor()` and of `$comment` will only be
really executed if `$this->get('security.context')->isGranted('attribute', $comment)`
returns `true`. Otherwise nothing will happen and the methods return null.
You should probably implements your own Voter in order to grant access or not to users.



