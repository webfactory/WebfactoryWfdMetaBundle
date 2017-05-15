# WebfactoryWfdMetaBundle

## What is this cruft? 

This bundle provides services and tools to work with content metadata as tracked by the [wfDynamic Content Management System](http://www.wfdynamic.de). This data is kept in a database table named `wfd_meta`. Among other things, it holds timestamps for creation, last update and possibly deletion of all managed records.

We have open-sourced it less for direct usage: You probably don't have the necessary information readily available in the format required by this bundle. 

We rather found it might be useful for others thinking about a similar solution. Especially using a custom `ConfigCacheFactory` implementation, custom database-aware resources and the annotation to make `Controller` results cacheable might be interesting examples how you can hook into details of the [Symfony Framework](http://www.symfony.com) to implement more specialized features.

## Services and APIs exposed by this bundle

The following sections try to describe those parts of the bundle meant to serve as the public API. Everything else should be considered the moving parts you are not supposed to interfere with.

### `webfactory_wfd_meta.provider` service

An instance of `\Webfactory\Bundle\WfdMetaBundle\Provider` that can be used to query the timestamp of the last change (change or deletion) in one or several tables identified by their table names or wfDynamic table IDs. Can also be used to query this information for a single database row.

The special table name `*` denotes "any change/table".

### The `\Webfactory\Bundle\WfdMetaBundle\MetaQuery` class
 
Use a `MetaQuery` to separate concerns when the code that knows *what* changes to track is distributed and/or different from the code acting upon that information. More precisely, one or several clients can call the `MetaQuery` to add the Doctrine entity instance, entity class, database table name or wfDynamic table IDs to track for changes. Then, the query can be passed along and eventually be executed. Again, the special tablename `*` denotes "any table".

Either create a `MetaQuery` instance for a particular purpose as a DIC service; you can inherit from the abstract `webfactory_wfd_meta.meta_query` service to do so. Alternatively, call the `create()` method on the `webfactory_wfd_meta.meta_query_factory` service.

### The `Send304IfNotModified` annotation to allow HTTP cache validation based on `wfd_meta` data

Let's start with an example:

```php
<?php
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Webfactory\Bundle\WfdMetaBundle\Caching\Annotation as WfdMeta;
 
class MyController extends Controller {
     
    /**
     * ...
     * @WfdMeta\Send304IfNotModified(tables = {"*", "tablename", "42", ...}, entities = {"AcmeBundle:BlogPost"}, tableIdConstants = {"MEDIA_TABLE_ID"})
     * @Cache(smaxage="0") // think before copy! See note below.
     */
    public function someAction(...) { ... 
```

This annotation does two things:

* It uses `wfd_meta` data to obtain the last change in one of the given tables. This information will be sent in the `Last-Modified` header.
* **If `kernel.debug` is `false`** and the client sent an `If-Modified-Since` header, `wfd_meta` information will be used for cache validation: If no change was recorded in `wfd_meta`, the controller will *not*  be executed and a 304 (not modified) response be returned instead.
 
The annotation can be used to specify table names or IDs (the `tables` attribute), Doctrine entity FQCNs (`entities`) or table IDs defined as constants (`tableIdConstants`).

*Note:* `@Send304IfNotModified` does not alter or add `Cache-Control` header settings. So, by default your response will remain `private` and end up in browser caches only. If you want it to be kept in surrogate caches (like Varnish or the Symfony Http Cache), you can add `@Cache(smaxage="0")` as shown above. This will make the response `public`, but also requires a revalidation on every request as the response is *always*  considered stale. [Learn more about Symonfy's HTTP caching mechanisms.](http://symfony.com/doc/current/book/http_cache.html)
 
#### Using a `resetInterval`

The annotation features an additional setting named `resetInterval` with defaults to 2419200 seconds (28 days).

The `Last-Modified` header added by the annotation will be shifted to the nearest (past) multiple  of this value. This effectively makes the response never seem older than the given interval and has the net effect of expiring the cache (and re-running the controller once) after this interval has expired, even when no change has been recorded in `wfd_meta`.
 
This can come in handy when you know that your response depends on data that can be tracked via `wfd_meta`, but it also includes (computed) elements that change over time. For example, set `resetInterval` to `3600` to regenerate the response every hour, use a cached response as long as possible but also immediately re-generate the response when `wfd_meta` tracks a change.
 
### `webfactory_wfd_meta.controller.template:templateAction` controller-as-a-service

Just like the [FrameworkBundle:Template-Controller](http://symfony.com/doc/current/cookbook/templating/render_without_controller.html), this can be used to render arbitrary Twig templates that do not need any additional controller for processing. But, in addition to the basic caching properties, `wfd_meta` settings and change tracking can be added. Here's an example:
 
```yaml
# routing.yml
# At /demo, render the MyBundle:Foo:bar.html.twig template. Keep the response in public caches, revalidating it every 10s. Whenever wfd_meta tracks any change, generate a fresh response.
demo:
    path: /demo
    defaults:
        _controller:  webfactory_wfd_meta.controller.template:templateAction
        template:     'MyBundle:Foo:bar.html.twig'
        sharedAge:    10
        metaTables:   *
``` 

### The `\Webfactory\Bundle\WfdMetaBundle\Util\CriticalSection` utility class

This class implements a [critical section](https://en.wikipedia.org/wiki/Critical_section). Use it whenever two processes (on the same machine/host) do possibly interfering stuff or attempt to do the same thing. The critical section will give one process way and block the others until the first one has finished its task.
  
### A custom `ConfigCacheFactory` implementation and specialized resource types

In the full-stack Symfony framework, several components use the `ConfigCache` mechanism to cache expensive-to-generate things. This includes translation catalogues as well as the URL router and matcher components.
 
This bundle adds two new types of resources, `\Webfactory\Bundle\WfdMetaBundle\Config\DoctrineEntityClassResource` and `\Webfactory\Bundle\WfdMetaBundle\Config\WfdTableResource`. Add instances of those resources to classes like the `MessageCatalogue` (translation component) or the `RouteCollection` (routing component) when you generate translations or routes based on database content.
 
This bundle will replace, or more precisely: decorate, the `config_cache_factory` service implementation. It will include a check for those new resource types and make sure that the cache will be refreshed whenever a relevant change is tracked in `wfd_meta`. This will *also happen in `kernel.debug = false`*, i. e. in production mode!
 
 The `CriticalSection` will be used to make sure only one process at a time tries to re-create the cache, while others wait and re-use the result.

## Tests

Run the tests with

    vendor/bin/phpunit

## Credits, Copyright and License

This project was started at webfactory GmbH, Bonn.

- <https://www.webfactory.de>
- <https://twitter.com/webfactory>

Copyright 2015-2017 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
