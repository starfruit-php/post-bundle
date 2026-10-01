<?php

namespace Starfruit\PostBundle\Service;

use PhpOffice\PhpWord\IOFactory;

class PostService
{
    public static function renderWordToHtml(
        string $filePath,
        $mainObject
    ): ?string {
        try {
            if (!is_file($filePath)) {
                \Pimcore\Logger::warning(
                    'Word file does not exist: '
                    . $filePath
                );

                return null;
            }

            $extension = strtolower(
                pathinfo(
                    $filePath,
                    PATHINFO_EXTENSION
                )
            );

            if ($extension === 'docx') {
                $reader =
                    IOFactory::createReader(
                        'Word2007'
                    );
            }

            elseif ($extension === 'doc') {
                $reader =
                    IOFactory::createReader(
                        'MsDoc'
                    );
            }

            elseif ($extension === 'tmp') {
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
                }

                elseif (
                    $mimeType ===
                    'application/msword'
                ) {
                    $reader =
                        IOFactory::createReader(
                            'MsDoc'
                        );
                }

                elseif (
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
                }

                else {
                    \Pimcore\Logger::warning(
                        sprintf(
                            'Unsupported temporary Word file. Path: %s, MIME: %s',
                            $filePath,
                            $mimeType
                        )
                    );

                    return null;
                }
            }

            else {
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