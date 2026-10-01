<?php

namespace Starfruit\PostBundle\Service;

use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostService
{
    public static function renderWordToHtml(
        UploadedFile $file,
        $mainObject
    ): ?string {
        try {
            if (!$file->isValid()) {
                \Pimcore\Logger::warning(
                    'Invalid uploaded Word file: '
                        . $file->getErrorMessage()
                );

                return null;
            }

            $filePath = $file->getPathname();

            \Pimcore\Logger::info(
                sprintf(
                    'Word import - Original: %s | Path: %s | MIME: %s',
                    $file->getClientOriginalName(),
                    $filePath,
                    $file->getMimeType()
                )
            );

            if (!is_file($filePath)) {
                \Pimcore\Logger::warning(
                    'Word temporary file does not exist: '
                        . $filePath
                );

                return null;
            }


            $extension = strtolower(
                $file->getClientOriginalExtension()
            );

            if ($extension === 'docx') {
                $reader =
                    IOFactory::createReader(
                        'Word2007'
                    );
            } elseif ($extension === 'doc') {
                $reader =
                    IOFactory::createReader(
                        'MsDoc'
                    );
            } elseif ($extension === 'tmp') {
                $mimeType = null;

                if (
                    function_exists(
                        'mime_content_type'
                    )
                ) {
                    $mimeType =
                        mime_content_type(
                            $filePath
                        );
                }

                if (
                    $mimeType ===
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ) {
                    $reader =
                        IOFactory::createReader(
                            'Word2007'
                        );
                } elseif (
                    $mimeType ===
                    'application/msword'
                ) {
                    $reader =
                        IOFactory::createReader(
                            'MsDoc'
                        );
                } elseif (
                    $mimeType ===
                    'application/octet-stream'
                ) {
                    try {
                        $reader =
                            IOFactory::createReader(
                                'Word2007'
                            );

                        $phpWord =
                            $reader->load(
                                $filePath
                            );

                        return HelperService::renderToHtml(
                            $phpWord,
                            $mainObject
                        );
                    } catch (\Throwable $docxException) {
                        try {
                            $reader =
                                IOFactory::createReader(
                                    'MsDoc'
                                );

                            $phpWord =
                                $reader->load(
                                    $filePath
                                );

                            return HelperService::renderToHtml(
                                $phpWord,
                                $mainObject
                            );
                        } catch (\Throwable $docException) {
                            \Pimcore\Logger::warning(
                                sprintf(
                                    'Unable to identify temporary Word file. Path: %s, MIME: %s',
                                    $filePath,
                                    $mimeType
                                )
                            );

                            return null;
                        }
                    }
                } else {
                    \Pimcore\Logger::warning(
                        sprintf(
                            'Unsupported temporary Word file. Path: %s, MIME: %s',
                            $filePath,
                            $mimeType
                        )
                    );

                    return null;
                }
            } else {
                \Pimcore\Logger::warning(
                    'Unsupported Word file extension: '
                        . $extension
                );

                return null;
            }

            $phpWord =
                $reader->load(
                    $filePath
                );

            return HelperService::renderToHtml(
                $phpWord,
                $mainObject
            );
        } catch (\Throwable $e) {
            \Pimcore\Logger::error(
                'Unable to read Word file: '
                    . $e->getMessage()
            );

            return null;
        }
    }
}
