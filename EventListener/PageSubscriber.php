<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecommenderBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\LeadBundle\LeadEvent;
use MauticPlugin\MauticRecommenderBundle\Helper\RecommenderHelper;
use MauticPlugin\MauticRecommenderBundle\Service\RecommenderTokenReplacer;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @var RecommenderTokenReplacer
     */
    private $recommenderTokenReplacer;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * PageSubscriber constructor.
     *
     * @param RecommenderTokenReplacer $recommenderTokenReplacer
     * @param ContactTracker           $contactTracker
     */
    public function __construct(
        RecommenderTokenReplacer $recommenderTokenReplacer,
        ContactTracker $contactTracker,
        IntegrationHelper $integrationHelper        
    ) {
        $this->recommenderTokenReplacer = $recommenderTokenReplacer;
        $this->contactTracker = $contactTracker;
        $this->integrationHelper = $integrationHelper;    
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_BUILD   => ['onPageBuild', 0],
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 200],
        ];
    }

    /**
     * Add forms to available page tokens.
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild(Events\PageBuilderEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('Recommender');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }

        if ($event->tokensRequested(RecommenderHelper::$recommenderRegex)) {
            $tokenHelper = new BuilderTokenHelper($this->factory, 'recommender');
            $event->addTokensFromHelper($tokenHelper, RecommenderHelper::$recommenderRegex, 'name', 'id', true);
        }
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(Events\PageDisplayEvent $event)
    {
        $integration = $this->integrationHelper->getIntegrationObject('Recommender');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        
        $lead    = $this->contactTracker->getContact();
        $leadId  = ($lead) ? $lead->getId() : null;
        if ($leadId && $event->getPage()) {
            $this->recommenderTokenReplacer->getRecommenderToken()->setUserId($leadId);
            $this->recommenderTokenReplacer->getRecommenderToken()->setContent($event->getContent());
            $event->setContent($this->recommenderTokenReplacer->getReplacedContent());
        }
    }
}
