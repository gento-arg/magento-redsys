<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Model;

// use Magento\Cms\Block\BlockFactory;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

// use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class DefaultConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        // ScopeConfigInterface $scopeConfig,
        // BlockFactory $blockFactory,
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        // $this->scopeConfig = $scopeConfig;
        // $this->blockFactory = $blockFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        // $showCmsBlock = $this->scopeConfig->getValue('payment/redsys/show_cms_block', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        // $blockHtml = '';
        // if ($showCmsBlock) {
        //     $blockId = $this->scopeConfig->getValue('payment/redsys/cms_block_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        //     /** @var \Magento\Cms\Model\Block */
        //     $block = $this->blockFactory->create()->setBlockId($blockId);
        //     $blockHtml = $block->toHtml();
        // }

        return [
            'redsysFormDataUrl' => $this->getFormDataUrl(),
            // 'redsysShowCmsBlock' => $showCmsBlock,
            // 'redsysBlockHtml' => $blockHtml,
        ];
    }

    public function getFormDataUrl()
    {
        return $this->urlBuilder->getUrl('redsys/form/redirect');
    }
}
