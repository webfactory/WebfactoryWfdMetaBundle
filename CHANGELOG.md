## Version 3.2.0

* Also accept table-IDs in Provider::getLastTouchedRow()

## Version 3.1.3

* Bugfix: Used wrong variable name in WfdTableResource

## Vesion 3.1.2

* Merge pull request #9 from webfactory/fix-chmod

## Version 3.1.1

* Made `webfactory_wfd_meta.doctrine_metadata_helper` service public again. MetaQuery instances need to perform a lazy lookup of this service in the DIC.

## Version 3.1.0

* Added ```Provider->getLastTouchedOfEachRow($tableName)``` and tests. 

## Version 3.0.0

* Removed internal caching in the `Provider` class.  
* Added the custom `ConfigCacheFactory` implementation and new WfdMetaResource types like `DoctrineEntityClassResource` or `WfdTableResource`. Add instances of these resource to `RouteCollection` and `MessageCatalogue` instances to track changes.
* Removed `RefreshingRouter` and `RefreshingTranslator` classes, `webfactory_wfd_meta.refreshing_router` and `webfactory_wfd_meta.refreshing_translator` services and the `webfactory_wfd_meta.refresh_router` and `webfactory_wfd_meta.refresh_translator` configuration keys.
* Made the `webfactory_wfd_meta.doctrine_metadata_helper` service private. It is not considered part of this bundle's public API.


## Version 2.6.0

* Added a new `MetadataFacade`

## Version 2.4

* Added the `webfactory_wfd_meta.controller.template` controller service to render static templates with `wfd_meta` based cache validation

## Version 2.3

* Added the `resetInterval` setting in the `Send304IfNotModified` annotation

## Version 2.2

* BC break: Renamed `webfactory.wfd_meta.provider` service to `webfactory_wfd_meta.provider`
* Added the `MetaQuery` class
* Allow usage of Doctrine entity class FQCNs in the `Send304IfNotModified` annotation
