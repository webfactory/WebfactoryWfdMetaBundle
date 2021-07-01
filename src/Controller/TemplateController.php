<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Controller;

use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Webfactory\Bundle\WfdMetaBundle\Helper\LastmodHelper;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

/**
 * Ein generischer Controller zum Ausliefern von Templates, ähnlich dem
 * FrameworkBundle:Template:template-Controller
 * (http://symfony.com/doc/current/cookbook/templating/render_without_controller.html).
 *
 * Der Unterschied ist, dass diese Version hier noch wfd_meta-Informationen berücksichtigen
 * kann.
 *
 * Das ist sinnvoll, falls das Template an sich "statisch" ist und keinen dedizierten Controller
 * benötigt, aber zum Beispiel (über eine TwigExtension) Seiten-Content abruft. Dann kann nämlich eine
 * Auslieferung durch diesen Controller hier + einen Cache + z. B. einen s-maxage=0-Header die Seite
 * so lange im public cache halten, bis sich in wfd_meta etwas ändert.
 */
class TemplateController
{
    /** @var Environment */
    protected $twig;

    /** @var MetaQueryFactory */
    protected $metaQueryFactory;

    protected $debug;

    public function __construct(Environment $twig, MetaQueryFactory $metaQueryFactory, $debug)
    {
        $this->twig = $twig;
        $this->metaQueryFactory = $metaQueryFactory;
        $this->debug = $debug;
    }

    public function templateAction(
        Request $request,
        $template,
        $maxAge = null,
        $sharedAge = null,
        $private = null,
        $metaTables = null,
        $metaTableConstants = null,
        $metaEntities = null,
        $metaResetInterval = null
    ) {
        /** @var DateTime $lastmod */
        $lastmod = null;

        /** @var $response \Symfony\Component\HttpFoundation\Response */
        $response = null;

        if ($metaTables || $metaTableConstants || $metaEntities) {
            $lastmodHelper = new LastmodHelper();

            if ($metaTables) {
                $lastmodHelper->setTables($metaTables);
            }

            if ($metaTableConstants) {
                $lastmodHelper->setTableIdConstants($metaTableConstants);
            }

            if ($metaEntities) {
                $lastmodHelper->setEntities($metaEntities);
            }

            if ($metaResetInterval) {
                $lastmodHelper->setResetInterval($metaResetInterval);
            }

            $lastmod = $lastmodHelper->calculateLastModified($this->metaQueryFactory);
        }

        if (!$this->debug && $lastmod) {
            $response = new Response();
            $response->setLastModified($lastmod);

            if (!$response->isNotModified($request)) {
                $response = null;
            }
        }

        if (!$response) {
            $response = new Response($this->twig->render($template));

            if ($lastmod) {
                $response->setLastModified($lastmod);
            }
        }

        if (null !== $maxAge) {
            $response->setMaxAge($maxAge);
        }

        if (null !== $sharedAge) {
            $response->setSharedMaxAge($sharedAge);
        }

        if ($private) {
            $response->setPrivate();
        } elseif (false === $private || (null === $private && ($maxAge || $sharedAge))) {
            $response->setPublic();
        }

        return $response;
    }
}
