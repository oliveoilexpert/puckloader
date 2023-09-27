<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use UBOS\Puckloader\Attribute\Plugin;

class ContentController extends ActionController
{
    #[Plugin("Content")]
    public function indexAction(): ResponseInterface
    {
        $data = $this->configurationManager->getContentObject()->data;
        if (array_key_exists('dataProcessing', $this->settings)) {
            $contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
            $dataProcessingAsTypoScriptArray = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class)->convertPlainArrayToTypoScriptArray($this->settings['dataProcessing']);
            $variables = $contentDataProcessor->process(
                $this->configurationManager->getContentObject(),
                ['dataProcessing.' => $dataProcessingAsTypoScriptArray ?? null],
                ['data' => $data]
            );
        }
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $variables['object'] = $dataMapper->map($this->settings['modelNamespace'] . $this->settings['modelName'], [$data])[0];
        $variables['settings'] = $this->settings;
        $context = $this->view->getRenderingContext();
        $context->setControllerAction($this->settings['modelName']);
        $this->view->setRenderingContext($context);
        $this->view->setTemplateRootPaths([$this->settings['view']['templateRootPath']]);
        $this->view->setPartialRootPaths([$this->settings['view']['partialRootPath']]);
        $this->view->setLayoutRootPaths([$this->settings['view']['layoutRootPath']]);
        $this->view->assignMultiple(
            $variables
        );

        return $this->htmlResponse();
    }
}
