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
                    deny_value: Title hidden ! #optional setting, default will return null on deny
                getAuthor:
                    expression: '"ROLE_ADMIN" in roles or (user and user.isSuperAdmin())'
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

* If 'attribute' is set, when using the generated proxy, original methods `getTitle()` and `setAuthor()` of `$comment` will only be really executed if `$securityContext->isGranted('attribute', $comment)` returns `true`.
* If 'expression' is set, when using the generated proxy, original methods will only be really executed if `$securityContext->isGranted(new Expression($expression)` returns `true`.
* If both are set, both test are performed.
* If `$securityContext->isGranted()` returns false, the original method will not be executed. It will return `null`, or `deny_value` if set.
* If the original method returns an object of a pretected class, it will return the raw object or its protected proxy depending on `return_proxy` setting. Default for this setting is `false`.

If you use attribute, you should probably implements your own [Voter](http://symfony.com/doc/current/cookbook/security/voters.html) in order to grant access or not to users, if you are not using roles only.
