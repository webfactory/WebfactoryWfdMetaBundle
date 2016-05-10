# wfdmeta-bundle #


## Motivation: Why does this project exist? ##
This bundle provides an interface to detect changes in a database administered by the wfDynamic Content Management System. Those changes are logged in the so-called `wfd_meta` table, which holds timestamps and corresponding user IDs.

Besides that, alternative implementations of `translator` and `router` are provided which recalculate the routes and update the translations when the database is altered.

The annotation `@WfdMeta\Send304IfNotModified` allows to return a special 304 return code if no entries were modified.

There is also a Controller-as-a-service `webfactory_wfd_meta.controller.template`, which is similar to the [TemplateController des FrameworkBundle](http://symfony.com/doc/current/cookbook/templating/render_without_controller.html) but also considers `wfd_meta` information.

## Installation ##

The bundle is installed like any other Symfony2 bundle.

## Tests ##

Run the tests with

    vendor/bin/phpunit

## Credits, Copyright and License ##

This project was started at webfactory GmbH, Bonn.

- <http://www.webfactory.de>
- <http://twitter.com/webfactory>

Copyright 2015 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
