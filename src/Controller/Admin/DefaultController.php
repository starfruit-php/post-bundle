<?php

namespace Starfruit\PostBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;
use Pimcore\Model\Version;
use Starfruit\PostBundle\Service\PostService;

class DefaultController extends BaseController
{
    private function checkOrGetAction(
        $id,
        $returnObject = false
    ) {
        if ($this->hideFeatureByAdmin) {
            return $returnObject ? null : false;
        }

        if (empty($id)) {
            return $returnObject ? null : false;
        }

        if (empty($this->allowClasses)) {
            return $returnObject ? null : false;
        }

        $object = DataObject::getById(
            (int) $id
        );

        $validObject =
            $object
            && !($object instanceof DataObject\Folder);

        if (!$validObject) {
            return $returnObject ? null : false;
        }

        $classname = $object->getClassname();

        $canInit = array_key_exists(
            $classname,
            $this->allowClasses
        );

        if (!$returnObject) {
            if (!$canInit) {
                return false;
            }

            return [
                'canInit' => true
            ];
        }

        return $canInit ? $object : null;
    }

    public function checkToInit($id)
    {
        $checkData = $this->checkOrGetAction($id);

        $response = is_array($checkData)
            ? $checkData
            : [
                'canInit' => $checkData
            ];

        if (!empty($response['canInit'])) {
            $response['maxFileSizeMb'] =
                $this->getImportMaxFileSizeMb();

            $response['allowedExtensions'] =
                $this->getImportAllowedExtensions();
        }

        return $this->sendResponse(
            $response
        );
    }

    public function startAction(Request $request)
    {
        try {
            $id = $request->request->get('id');

            if (empty($id)) {
                return $this->sendResponse([
                    'success' => false,
                    'message' => 'Object ID không hợp lệ'
                ]);
            }

            $object = $this->checkOrGetAction(
                $id,
                true
            );

            if (!$object) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Object không hợp lệ hoặc không được phép xử lý'
                ]);
            }

            $classname = $object->getClassname();

            $contentField =
                $this->getContentField(
                    $classname
                );

            if (!$contentField) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Chưa cấu hình content field'
                ]);
            }

            $setFunction =
                'set'
                . ucfirst($contentField);

            if (!method_exists(
                $object,
                $setFunction
            )) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Content field không tồn tại'
                ]);
            }

            $file = $request->files->get(
                'file'
            );

            if (!$file) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Chưa upload file'
                ]);
            }

            if (!$file->isValid()) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'File upload không hợp lệ'
                ]);
            }

            $fileSize = $file->getSize();

            if (
                $fileSize === null
                || $fileSize <= 0
            ) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Không xác định được dung lượng file'
                ]);
            }

            $maxFileSizeBytes =
                $this->getImportMaxFileSizeBytes();

            $maxFileSizeMb =
                $this->getImportMaxFileSizeMb();

            if (
                $fileSize
                > $maxFileSizeBytes
            ) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        sprintf(
                            'Dung lượng file vượt quá giới hạn %.2fMB. Dung lượng file hiện tại: %s.',
                            $maxFileSizeMb,
                            $this->formatFileSize(
                                $fileSize
                            )
                        )
                ]);
            }

            $extension = strtolower(
                $file->getClientOriginalExtension()
            );

            $allowedExtensions =
                $this->getImportAllowedExtensions();

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Định dạng file không được hỗ trợ. '
                        . 'Chỉ hỗ trợ file .doc, .docx hoặc .tmp.'
                ]);
            }

            if ($extension === 'tmp') {
                $mimeType =
                    $file->getMimeType();

                $allowedTmpMimeTypes = [
                    'application/msword',

                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                    'application/octet-stream',
                ];

                if (
                    !$mimeType
                    || !in_array(
                        $mimeType,
                        $allowedTmpMimeTypes,
                        true
                    )
                ) {
                    return $this->sendResponse([
                        'success' => false,
                        'message' =>
                            'File .tmp không phải là file Word hợp lệ.'
                    ]);
                }
            }

            if (
                $extension === 'doc'
                || $extension === 'docx'
            ) {
                $mimeType =
                    $file->getMimeType();

                if ($extension === 'docx') {
                    $allowedDocxMimeTypes = [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                        'application/octet-stream',
                    ];

                    if (
                        $mimeType
                        && !in_array(
                            $mimeType,
                            $allowedDocxMimeTypes,
                            true
                        )
                    ) {
                        \Pimcore\Logger::warning(
                            sprintf(
                                'DOCX MIME type is unusual. MIME: %s, File: %s',
                                $mimeType,
                                $file->getClientOriginalName()
                            )
                        );
                    }
                }

                if ($extension === 'doc') {
                    $allowedDocMimeTypes = [
                        'application/msword',
                        'application/octet-stream',
                    ];

                    if (
                        $mimeType
                        && !in_array(
                            $mimeType,
                            $allowedDocMimeTypes,
                            true
                        )
                    ) {
                        \Pimcore\Logger::warning(
                            sprintf(
                                'DOC MIME type is unusual. MIME: %s, File: %s',
                                $mimeType,
                                $file->getClientOriginalName()
                            )
                        );
                    }
                }
            }

            $html =
                PostService::renderWordToHtml(
                    $file->getPathname(),
                    $object
                );

            if (!$html) {
                return $this->sendResponse([
                    'success' => false,
                    'message' =>
                        'Không thể đọc nội dung file Word'
                ]);
            }

            $locale = $request->get(
                'locale',
                'vi'
            );

            $object->{$setFunction}(
                $html,
                $locale
            );

            $lastVersionField =
                $this->getLastVersionField(
                    $classname
                );

            if ($lastVersionField) {
                $setLastVersionFunction =
                    'set'
                    . ucfirst(
                        $lastVersionField
                    );

                if (
                    method_exists(
                        $object,
                        $setLastVersionFunction
                    )
                ) {
                    $object->{$setLastVersionFunction}(
                        $file->getClientOriginalName()
                    );
                }
            }

            /**
             * Save as new version.
             */
            Version::enable();

            $object->saveVersion();

            return $this->sendResponse([
                'success' => true,
                'message' =>
                    'Đã lấy dữ liệu từ file Word',
                'file' => [
                    'name' =>
                        $file->getClientOriginalName(),
                    'extension' =>
                        $extension,
                    'size' =>
                        $this->formatFileSize(
                            $fileSize
                        ),
                    'maxSize' =>
                        $maxFileSizeMb . 'MB'
                ]
            ]);

        } catch (\Throwable $e) {
            \Pimcore\Logger::error(
                'STF Post import Word error: '
                . $e->getMessage()
            );

            return $this->sendResponse([
                'success' => false,
                'message' =>
                    'Không thể xử lý file Word: '
                    . $e->getMessage()
            ]);
        }
    }

    private function formatFileSize(
        int $bytes
    ): string {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round(
                $bytes / 1024,
                2
            ) . ' KB';
        }

        if (
            $bytes
            < 1024 * 1024 * 1024
        ) {
            return round(
                $bytes / 1024 / 1024,
                2
            ) . ' MB';
        }

        return round(
            $bytes
            / 1024
            / 1024
            / 1024,
            2
        ) . ' GB';
    }
}