<?php

namespace Starfruit\PostBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Starfruit\PostBundle\Service\HelperService;

class BaseController extends \Pimcore\Controller\FrontendController
{
    protected $hideFeatureByAdmin;
    protected $config;
    protected $allowClasses;

    protected const DEFAULT_IMPORT_MAX_FILE_SIZE_MB = 5;
    protected const IMPORT_MAX_FILE_SIZE_SETTING = 'post_import_max_file_size';

    public function __construct()
    {
        $this->hideFeatureByAdmin = $this->hideFeatureByAdmin();
        $this->config = HelperService::getConfig();
        $this->allowClasses = $this->getAllowClasses();
    }

    public function sendResponse($response = [])
    {
        return new JsonResponse($response, Response::HTTP_OK);
    }

    protected function hideFeatureByAdmin()
    {
        $hideFeatureByAdmin = (bool) \Pimcore\Config::getWebsiteConfigValue('post_hide_feature');
        return $hideFeatureByAdmin;
    }

    protected function getAllowClasses()
    {
        if (!isset($this->config['target']['class_object'])) return [];

        return (array) $this->config['target']['class_object'];
    }

    protected function getContentField($classname)
    {
        if (isset($this->config['target']['class_object'][$classname]['content_field'])) {
            return $this->config['target']['class_object'][$classname]['content_field'];
        };

        return null;
    }

    protected function getLastVersionField($classname)
    {
        if (isset($this->config['target']['class_object'][$classname]['last_version_field'])) {
            return $this->config['target']['class_object'][$classname]['last_version_field'];
        };

        return null;
    }

    protected function getImportMaxFileSizeMb(): float
    {
        $value = \Pimcore\Config::getWebsiteConfigValue(
            self::IMPORT_MAX_FILE_SIZE_SETTING
        );

        if (
            $value === null
            || $value === ''
            || !is_numeric($value)
            || (float) $value <= 0
        ) {
            return self::DEFAULT_IMPORT_MAX_FILE_SIZE_MB;
        }

        return (float) $value;
    }

    protected function getImportMaxFileSizeBytes(): int
    {
        return (int) round(
            $this->getImportMaxFileSizeMb()
            * 1024
            * 1024
        );
    }

    protected function getImportAllowedExtensions(): array
    {
        return [
            'doc',
            'docx',
            'tmp',
        ];
    }
}