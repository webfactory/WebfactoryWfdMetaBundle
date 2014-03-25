<?php


namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

use Webfactory\Bundle\WfdMetaBundle\MetaQuery;

/**
 * @Annotation
 */
class Send304IfNotModified
{
    protected $values;

    public function __construct($values)
    {
        if ($wrong = array_diff_key($values, array_flip(array('tables', 'tableIdConstants', 'entities', 'resetInterval')))) {
            $key = key($wrong);
            throw new \Exception('Die Annotation ' . get_class($this) . ' kennt die Eigentschaft "' . $key . '" nicht.');
        }

        if (!isset($values['resetInterval'])) {
            $values['resetInterval'] = false;
        } else if (!is_int($values['resetInterval'])) {
            throw new \Exception('Die Annotation ' . get_class($this) . ' erwartet für die Eigenschaft resetInterval einen Integer (in Sekunden).');
        }
        
        $this->values = $values;
    }

    public function configure(MetaQuery $metaQuery)
    {

        try {

            if (isset($this->values['tables'])) {
                $metaQuery->addTable($this->values['tables']);
            }

            if (isset($this->values['tableIdConstants'])) {
                $metaQuery->addTable(
                    array_map(
                        function ($x) {
                            return constant($x);
                        },
                        $this->values['tableIdConstants']
                    )
                );
            }

            if (isset($this->values['entities'])) {
                $metaQuery->addEntity($this->values['entities']);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Exception während der Konfiguration von MetaQuery durch die Annotation " . get_class(
                    $this
                ), 0, $e);
        }
    }

    public function getResetInterval()
    {
        return $this->values['resetInterval'];
    }
    
}
