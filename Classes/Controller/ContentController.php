<?php

declare(strict_types=1);

namespace Controller;

use Psr\Http\Message\ResponseInterface;
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
        try {
            $data = $this->configurationManager->getContentObject()->data;
            $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
            $variables = [
                'object' => $dataMapper->map($this->settings['modelNamespace'] . '\\' . $this->settings['modelName'], [$data])[0],
                'settings' => $this->settings,
            ];
            if (array_key_exists('dataProcessing', $this->settings)) {
                $contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
                $dataProcessingAsTypoScriptArray = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class)->convertPlainArrayToTypoScriptArray($this->settings['dataProcessing']);
                $variables = $contentDataProcessor->process(
                    $this->configurationManager->getContentObject(),
                    ['dataProcessing.' => $dataProcessingAsTypoScriptArray ?? null],
                    ['data' => $data]
                );
            }

            $context = $this->view->getRenderingContext();
            $context->setControllerAction($this->settings['modelName']);
            $this->view->setRenderingContext($context);
            $this->view->setTemplateRootPaths([$this->settings['view']['templateRootPath']]);
            $this->view->assignMultiple(
                $variables
            );

            return $this->htmlResponse();
        } catch (\Exception $ex) {
            return 'Exception in content rendering: ' . $ex->getMessage();
        }
    }
}
