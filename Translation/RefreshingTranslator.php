<?php

namespace Webfactory\Bundle\WfdMetaBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webfactory\Bundle\WfdMetaBundle\Provider;

/**
 * RefreshingTranslator ist wie Symfony\Bundle\FrameworkBundle\Translation\Translator mit der
 * Besonderheit, dass er zusätzlich den wfd_meta.last_touched-Timestamp berücksichtigt
 * und seinen Cache invalidiert, wenn darüber Änderungen der Datenbank bemerkt werden.
 *
 * Da wir nicht (wie beim RefreshingRouter) gezielt an den Code im parent::loadCatalogue ran kommen,
 * der den unzureichenden ConfigCache erzeugt, löschen wir hier einfach abhängig vom wfd_meta.last_touched-Timestamp
 * die Cache-Datei, da dann auch der normale ConfigCache invalide wird.
 */
class RefreshingTranslator extends BaseTranslator {

    protected $metaProvider;
    protected $tableDeps = array();

    public function setWfdMetaProvider(Provider $p) {
        $this->metaProvider = $p;
    }

    public function addWfdTableDependency($tables) {
        $this->tableDeps += array_fill_keys((array)$tables, true);
    }

    protected function loadCatalogue($locale) {
        // Wissen aus der Elternklasse
        $cacheFile = $this->options['cache_dir'].'/catalogue.'.$locale.'.php';

        // Schauen, ob die Cache-Datei älter als wfd_meta.last_touced ist
        if (file_exists($cacheFile))
            if (filemtime($cacheFile) < $this->metaProvider->getLastTouched(array_keys($this->tableDeps)))
                @unlink($cacheFile);

        parent::loadCatalogue($locale);
    }

}
