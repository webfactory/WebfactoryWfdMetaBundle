<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Webfactory\Bundle\WfdMetaBundle\MetaQuery;

/**
 * RefreshingTranslator ist wie Symfony\Bundle\FrameworkBundle\Translation\Translator mit der
 * Besonderheit, dass er zusätzlich den wfd_meta.last_touched-Timestamp berücksichtigt
 * und seinen Cache invalidiert, wenn darüber Änderungen der Datenbank bemerkt werden.
 *
 * Da wir nicht (wie beim RefreshingRouter) gezielt an den Code im parent::loadCatalogue ran kommen,
 * der den unzureichenden ConfigCache erzeugt, löschen wir hier einfach abhängig vom wfd_meta.last_touched-Timestamp
 * die Cache-Datei, da dann auch der normale ConfigCache invalide wird.
 */
class RefreshingTranslator extends BaseTranslator
{

    /** @var MetaQuery */
    protected $metaQuery;

    public function setMetaQuery(MetaQuery $metaQuery)
    {
        $this->metaQuery = $metaQuery;
    }

    public function addWfdTableDependency($tables)
    {
        trigger_error(
            'The addWfdTableDependency() setter is deprecated. Configure the MetaQuery instead.',
            E_USER_DEPRECATED
        );

        $this->metaQuery->addTable($tables);
    }

    protected function loadCatalogue($locale)
    {
        // Wissen aus der Elternklasse
        $cacheFile = $this->options['cache_dir'].'/catalogue.'.$locale.'.php';

        // Schauen, ob die Cache-Datei älter als wfd_meta.last_touced ist
        if (file_exists($cacheFile)) {
            if (filemtime($cacheFile) < $this->metaQuery->getLastTouched()) {
                @unlink($cacheFile);
            }
        }

        parent::loadCatalogue($locale);
    }
}
