<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Lang\Service\TranslationService;

/**
 * Language API Command Controller
 *
 */
class LanguageCommandController extends CommandController
{

    /**
     * @var \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository
     * @inject
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Lang\Service\TranslationService
     * @inject
     */
    protected $translationService;

    /**
     * Update translations for $languages
     *
     * @param string $languages comma separated language value
     * @cli
     * @return void
     */
    public function updateTranslationsCommand($languages)
    {
        $updateLanguages = $this->cleanLanguageArgument($languages);
        $this->outputLine(
            sprintf(
                'Updating language packs of all activated extensions for locales "%s"',
                implode(', ', $updateLanguages)
            )
        );

        /** @var $extensions \TYPO3\CMS\Lang\Domain\Model\Extension[] */
        $extensions = $this->extensionRepository->findAll();
        $this->output->progressStart(count($updateLanguages) * count($extensions));

        foreach ($extensions as $extension) {
            $extensionKey = $extension->getKey();

            foreach ($updateLanguages as $locale) {
                $result = $this->translationService->updateTranslation($extensionKey, $locale);
                $this->output->progressAdvance();
                $this->outputLine(
                    ' Extension: "%s", Locale: "%s", Result: "%s", Error: "%s"',
                    [
                        $extensionKey,
                        $locale,
                        $this->mapTranslationStatusToString($result[$locale]['state']),
                        isset($result[$locale]['error']) ? $result[$locale]['error'] : ''
                    ]
                );
            }
        }
        $this->output->progressFinish();
    }

    /**
     * Cleanup languages
     *
     * @param string $languages languages to check. comma separated values
     * @return array
     */
    protected function cleanLanguageArgument($languages)
    {
        $updateLanguages = [];
        $languages = GeneralUtility::trimExplode(',', $languages);

        /** @var $locales \TYPO3\CMS\Core\Localization\Locales */
        $locales = $this->objectManager->get('TYPO3\CMS\Core\Localization\Locales');
        $availableLanguages = $locales->getLocales();
        // drop default
        array_shift($availableLanguages);

        foreach ($languages as $language) {
            if (in_array($language, $availableLanguages, true)) {
                $updateLanguages[] = $language;
            } else {
                $this->outputLine('Language "%s" no found', [$language]);
            }
        }

        return $updateLanguages;
    }

    /**
     * Map translation status to string for output
     *
     * @param int $state On of TranslationService::TRANSLATION_* constant
     * @return string
     */
    protected function mapTranslationStatusToString($state)
    {
        $mapping = [
            TranslationService::TRANSLATION_NOT_AVAILABLE => 'not available',
            TranslationService::TRANSLATION_AVAILABLE => 'available',
            TranslationService::TRANSLATION_FAILED => 'failed',
            TranslationService::TRANSLATION_OK => 'ok',
            TranslationService::TRANSLATION_INVALID => 'invalid',
            TranslationService::TRANSLATION_UPDATED => 'updated',
        ];

        return isset($mapping[$state]) ? $mapping[$state] : 'undefined';
    }
}
