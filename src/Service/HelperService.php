<?php

namespace Starfruit\PostBundle\Service;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Image as WordImage;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\PhpWord;

class HelperService
{
    const DEFAULT_CONFIG_NAME = 'starfruit_post';

    /**
     * Get bundle config.
     */
    public static function getConfig()
    {
        return \Pimcore::getContainer()->getParameter(
            self::DEFAULT_CONFIG_NAME
        );
    }

    /**
     * Convert PHPWord document to HTML.
     */
    public static function renderToHtml(
        PhpWord $document,
        $mainObject
    ): string {
        $html = '';

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $html .= self::renderPhpWordElement(
                    $element,
                    $mainObject
                );
            }
        }

        return $html;
    }

    /**
     * Render PHPWord element.
     */
    protected static function renderPhpWordElement(
        $element,
        $mainObject
    ): string {
        /**
         * TextRun.
         */
        if ($element instanceof TextRun) {
            return self::renderPhpWordTextRun(
                $element,
                $mainObject
            );
        }

        /**
         * Normal Text.
         */
        if ($element instanceof Text) {
            return self::renderPhpWordText($element);
        }

        /**
         * Link.
         */
        if ($element instanceof Link) {
            return self::renderPhpWordLink($element);
        }

        /**
         * Image.
         */
        if ($element instanceof WordImage) {
            return self::renderPhpWordImage(
                $element,
                $mainObject
            );
        }

        /**
         * Table.
         */
        if ($element instanceof Table) {
            return self::renderPhpWordTable(
                $element,
                $mainObject
            );
        }

        /**
         * Line break.
         */
        if ($element instanceof TextBreak) {
            return '<br>';
        }

        /**
         * Other nested PHPWord elements.
         */
        if (method_exists($element, 'getElements')) {
            $html = '';

            foreach ($element->getElements() as $child) {
                $html .= self::renderPhpWordElement(
                    $child,
                    $mainObject
                );
            }

            return $html;
        }

        return '';
    }

    /**
     * Render TextRun.
     *
     * A TextRun normally represents one paragraph.
     */
    protected static function renderPhpWordTextRun(
        TextRun $textRun,
        $mainObject
    ): string {
        $content = '';

        foreach ($textRun->getElements() as $element) {
            $content .= self::renderPhpWordElement(
                $element,
                $mainObject
            );
        }

        if ($content === '') {
            return '';
        }

        /**
         * Get paragraph style.
         */
        $paragraphStyle = null;

        if (method_exists($textRun, 'getParagraphStyle')) {
            try {
                $paragraphStyle = $textRun->getParagraphStyle();
            } catch (\Throwable $e) {
                $paragraphStyle = null;
            }
        }

        /**
         * Detect heading.
         */
        $tag = self::getPhpWordHeadingTag(
            $paragraphStyle
        );

        /**
         * Paragraph CSS.
         */
        $style = self::getPhpWordParagraphStyle(
            $paragraphStyle
        );

        $styleAttr = '';

        if ($style !== '') {
            $styleAttr = ' style="'
                . htmlspecialchars(
                    $style,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                )
                . '"';
        }

        return '<'
            . $tag
            . $styleAttr
            . '>'
            . $content
            . '</'
            . $tag
            . '>';
    }

    /**
     * Render normal text.
     */
    protected static function renderPhpWordText(
        Text $element
    ): string {
        $text = htmlspecialchars(
            (string) $element->getText(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        if ($text === '') {
            return '';
        }

        $fontStyle = null;

        if (method_exists($element, 'getFontStyle')) {
            try {
                $fontStyle = $element->getFontStyle();
            } catch (\Throwable $e) {
                $fontStyle = null;
            }
        }

        return self::applyPhpWordFontStyle(
            $text,
            $fontStyle
        );
    }

    /**
     * Render Word link.
     */
    protected static function renderPhpWordLink(
        Link $element
    ): string {
        $text = htmlspecialchars(
            (string) $element->getText(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $url = null;

        if (method_exists($element, 'getLink')) {
            try {
                $url = $element->getLink();
            } catch (\Throwable $e) {
                $url = null;
            }
        }

        if (!$url) {
            return $text;
        }

        return '<a href="'
            . htmlspecialchars(
                $url,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . '" target="_blank" rel="noopener noreferrer">'
            . $text
            . '</a>';
    }

    /**
     * Apply Word font style.
     */
    protected static function applyPhpWordFontStyle(
        string $text,
        $fontStyle
    ): string {
        if (!$fontStyle) {
            return $text;
        }

        $openTags = '';
        $closeTags = '';

        /**
         * Bold.
         */
        if (
            method_exists($fontStyle, 'isBold')
            && $fontStyle->isBold()
        ) {
            $openTags .= '<strong>';
            $closeTags = '</strong>' . $closeTags;
        }

        /**
         * Italic.
         */
        if (
            method_exists($fontStyle, 'isItalic')
            && $fontStyle->isItalic()
        ) {
            $openTags .= '<em>';
            $closeTags = '</em>' . $closeTags;
        }

        /**
         * Underline.
         */
        if (method_exists($fontStyle, 'getUnderline')) {
            try {
                $underline = $fontStyle->getUnderline();

                if (
                    $underline
                    && $underline !== 'none'
                ) {
                    $openTags .= '<u>';
                    $closeTags = '</u>' . $closeTags;
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Strikethrough.
         */
        if (
            method_exists($fontStyle, 'isStrikethrough')
            && $fontStyle->isStrikethrough()
        ) {
            $openTags .= '<s>';
            $closeTags = '</s>' . $closeTags;
        }

        /**
         * Font color.
         */
        if (method_exists($fontStyle, 'getColor')) {
            try {
                $color = $fontStyle->getColor();

                if ($color) {
                    $color = ltrim(
                        (string) $color,
                        '#'
                    );

                    if (
                        preg_match(
                            '/^[a-fA-F0-9]{3,8}$/',
                            $color
                        )
                    ) {
                        $openTags .= '<span style="color:#'
                            . $color
                            . '">';

                        $closeTags =
                            '</span>'
                            . $closeTags;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Font size.
         */
        if (method_exists($fontStyle, 'getSize')) {
            try {
                $size = $fontStyle->getSize();

                if (
                    $size !== null
                    && is_numeric($size)
                ) {
                    $openTags .= '<span style="font-size:'
                        . (float) $size
                        . 'pt">';

                    $closeTags =
                        '</span>'
                        . $closeTags;
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Font family.
         */
        if (method_exists($fontStyle, 'getName')) {
            try {
                $fontName = $fontStyle->getName();

                if ($fontName) {
                    $fontName = htmlspecialchars(
                        (string) $fontName,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    );

                    $openTags =
                        '<span style="font-family:\''
                        . $fontName
                        . '\'">'
                        . $openTags;

                    $closeTags =
                        '</span>'
                        . $closeTags;
                }
            } catch (\Throwable $e) {
            }
        }

        return $openTags
            . $text
            . $closeTags;
    }

    /**
     * Render Word image.
     *
     * PHPWord can return image source like:
     *
     * zip://D:\xampp82\tmp\php9EDA.tmp#word/media/image1.jpg
     *
     * The image is actually inside the DOCX ZIP file.
     */
    protected static function renderPhpWordImage(
        WordImage $element,
        $mainObject
    ): string {
        if (!$mainObject) {
            return '';
        }

        try {
            $source = null;

            if (method_exists($element, 'getSource')) {
                $source = $element->getSource();
            }

            if (
                !is_string($source)
                || $source === ''
            ) {
                \Pimcore\Logger::warning(
                    'PHPWord image source is empty.'
                );

                return '';
            }

            /**
             * Read image binary.
             *
             * IMPORTANT:
             * Do not use file_exists() on zip:// source.
             */
            $data = self::readPhpWordImageSource(
                $source
            );

            if (
                $data === false
                || $data === ''
            ) {
                \Pimcore\Logger::warning(
                    'Unable to read PHPWord image source: '
                    . $source
                );

                return '';
            }

            /**
             * Detect extension.
             */
            $extension =
                self::getImageExtensionFromData(
                    $data,
                    $element,
                    $source
                );

            /**
             * Get image name.
             */
            $name = '';

            if (method_exists($element, 'getName')) {
                try {
                    $name = (string) $element->getName();
                } catch (\Throwable $e) {
                    $name = '';
                }
            }

            /**
             * Fallback to source name.
             */
            if (!$name) {
                $name = self::getImageNameFromSource(
                    $source
                );
            }

            /**
             * Fallback generated name.
             */
            if (!$name) {
                $name = 'word-image-' . uniqid();
            }

            /**
             * Remove extension.
             */
            $name = pathinfo(
                $name,
                PATHINFO_FILENAME
            );

            $name = self::sanitizeFileName(
                $name
            );

            $name .= '.' . $extension;

            /**
             * Save image into Pimcore.
             */
            $asset =
                self::getOrCreateAssetFromData(
                    $data,
                    $mainObject,
                    $name
                );

            if (!$asset) {
                return '';
            }

            $imageUrl =
                $asset->getFrontendFullPath();

            /**
             * Get Word image width.
             */
            $width = null;

            if (method_exists($element, 'getWidth')) {
                try {
                    $width = $element->getWidth();
                } catch (\Throwable $e) {
                    $width = null;
                }
            }

            $style = 'max-width:100%;height:auto;';

            if (
                $width !== null
                && is_numeric($width)
                && (float) $width > 0
            ) {
                $style =
                    'width:'
                    . (float) $width
                    . 'px;'
                    . 'max-width:100%;'
                    . 'height:auto;';
            }

            return '<p style="text-align:center;">'
                . '<img src="'
                . htmlspecialchars(
                    $imageUrl,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                )
                . '" style="'
                . $style
                . '" alt="" />'
                . '</p>';

        } catch (\Throwable $e) {
            \Pimcore\Logger::error(
                'Unable to render Word image: '
                . $e->getMessage()
            );

            return '';
        }
    }

    /**
     * Read PHPWord image source.
     *
     * Supports:
     *
     * - Normal filesystem path
     * - zip:// source
     * - DOCX temporary file
     */
    protected static function readPhpWordImageSource(
        string $source
    ) {
        /**
         * ZIP stream.
         */
        if (strpos($source, 'zip://') === 0) {
            /**
             * First try direct stream.
             */
            $data = @file_get_contents(
                $source
            );

            if (
                $data !== false
                && $data !== ''
            ) {
                return $data;
            }

            /**
             * Fallback to ZipArchive.
             */
            return self::readImageFromZipSource(
                $source
            );
        }

        /**
         * Normal filesystem path.
         */
        if (
            file_exists($source)
            && is_readable($source)
        ) {
            $data = @file_get_contents(
                $source
            );

            if (
                $data !== false
                && $data !== ''
            ) {
                return $data;
            }
        }

        /**
         * Last attempt.
         */
        $data = @file_get_contents(
            $source
        );

        if (
            $data !== false
            && $data !== ''
        ) {
            return $data;
        }

        return false;
    }

    /**
     * Read image directly from DOCX ZIP.
     *
     * Example:
     *
     * zip://D:\xampp82\tmp\php9EDA.tmp#word/media/image1.jpg
     */
    protected static function readImageFromZipSource(
        string $source
    ) {
        if (!class_exists('\ZipArchive')) {
            \Pimcore\Logger::error(
                'ZipArchive extension is not available.'
            );

            return false;
        }

        /**
         * Remove zip:// prefix.
         */
        $path = substr(
            $source,
            6
        );

        /**
         * Find # separator.
         */
        $separatorPosition = strrpos(
            $path,
            '#'
        );

        if ($separatorPosition === false) {
            return false;
        }

        /**
         * Example:
         *
         * D:\xampp82\tmp\php9EDA.tmp
         */
        $zipPath = substr(
            $path,
            0,
            $separatorPosition
        );

        /**
         * Example:
         *
         * word/media/image1.jpg
         */
        $internalPath = substr(
            $path,
            $separatorPosition + 1
        );

        if (
            $zipPath === ''
            || $internalPath === ''
        ) {
            return false;
        }

        if (
            !file_exists($zipPath)
            || !is_readable($zipPath)
        ) {
            \Pimcore\Logger::warning(
                'DOCX temporary file does not exist: '
                . $zipPath
            );

            return false;
        }

        $zip = new \ZipArchive();

        $result = $zip->open(
            $zipPath
        );

        if ($result !== true) {
            \Pimcore\Logger::warning(
                'Unable to open DOCX ZIP. Error code: '
                . $result
            );

            return false;
        }

        $data = $zip->getFromName(
            $internalPath
        );

        $zip->close();

        if (
            $data === false
            || $data === ''
        ) {
            \Pimcore\Logger::warning(
                'Unable to read image from DOCX ZIP: '
                . $internalPath
            );

            return false;
        }

        return $data;
    }

    /**
     * Get image name from source.
     */
    protected static function getImageNameFromSource(
        string $source
    ): string {
        /**
         * ZIP source.
         */
        if (strpos($source, 'zip://') === 0) {
            $path = substr(
                $source,
                6
            );

            $separatorPosition = strrpos(
                $path,
                '#'
            );

            if ($separatorPosition !== false) {
                $internalPath = substr(
                    $path,
                    $separatorPosition + 1
                );

                return basename(
                    $internalPath
                );
            }
        }

        return basename($source);
    }

    /**
     * Create or get Pimcore Image Asset.
     */
    private static function getOrCreateAssetFromData(
        string $data,
        $mainObject,
        string $name
    ) {
        try {
            if (
                !$mainObject
                || $data === ''
            ) {
                return null;
            }

            $config = self::getConfig();

            $folderPath =
                isset($config['asset_store_path'])
                    ? $config['asset_store_path']
                    : '/word_media';

            $classname = strtolower(
                $mainObject->getClassname()
            );

            $folderPath .=
                '/'
                . $classname
                . '/'
                . $classname
                . '-'
                . $mainObject->getId();

            $name = self::sanitizeFileName(
                $name
            );

            /**
             * Check existing image.
             */
            $asset = Image::getByPath(
                $folderPath . '/' . $name
            );

            if ($asset) {
                return $asset;
            }

            /**
             * Create folder.
             */
            $folder = Asset::getByPath(
                $folderPath
            );

            if (!$folder) {
                $folder =
                    Asset\Service::createFolderByPath(
                        $folderPath
                    );
            }

            if (!$folder) {
                \Pimcore\Logger::error(
                    'Unable to create asset folder: '
                    . $folderPath
                );

                return null;
            }

            /**
             * Create Pimcore image asset.
             */
            $asset = new Image();

            $asset->setFileName(
                $name
            );

            $asset->setData(
                $data
            );

            $asset->setParent(
                $folder
            );

            $asset->save();

            return $asset;

        } catch (\Throwable $e) {
            \Pimcore\Logger::error(
                'Unable to create Word image asset: '
                . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Detect image extension from binary.
     */
    protected static function getImageExtensionFromData(
        string $data,
        $element = null,
        $source = null
    ): string {
        /**
         * Detect MIME from image binary.
         */
        if (
            function_exists(
                'getimagesizefromstring'
            )
        ) {
            $imageInfo =
                @getimagesizefromstring(
                    $data
                );

            if (
                is_array($imageInfo)
                && isset($imageInfo['mime'])
            ) {
                $mimeMap = [
                    'image/jpeg' => 'jpg',
                    'image/jpg'  => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp',
                    'image/bmp'  => 'bmp',
                    'image/tiff' => 'tiff',
                ];

                $mime = strtolower(
                    (string) $imageInfo['mime']
                );

                if (
                    isset($mimeMap[$mime])
                ) {
                    return $mimeMap[$mime];
                }
            }
        }

        /**
         * PHPWord extension.
         */
        if (
            $element
            && method_exists(
                $element,
                'getImageExtension'
            )
        ) {
            try {
                $extension =
                    strtolower(
                        (string)
                        $element->getImageExtension()
                    );

                if ($extension === 'jpeg') {
                    return 'jpg';
                }

                if (
                    in_array(
                        $extension,
                        [
                            'jpg',
                            'png',
                            'gif',
                            'webp',
                            'bmp',
                            'tif',
                            'tiff',
                        ],
                        true
                    )
                ) {
                    return $extension;
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Source extension.
         */
        if (
            is_string($source)
            && $source !== ''
        ) {
            $extension =
                strtolower(
                    pathinfo(
                        $source,
                        PATHINFO_EXTENSION
                    )
                );

            if ($extension === 'jpeg') {
                return 'jpg';
            }

            if (
                in_array(
                    $extension,
                    [
                        'jpg',
                        'png',
                        'gif',
                        'webp',
                        'bmp',
                        'tif',
                        'tiff',
                    ],
                    true
                )
            ) {
                return $extension;
            }
        }

        return 'jpg';
    }

    /**
     * Render Word table.
     */
    protected static function renderPhpWordTable(
        Table $table,
        $mainObject
    ): string {
        $html =
            '<table '
            . 'border="1" '
            . 'cellspacing="0" '
            . 'cellpadding="5" '
            . 'style="border-collapse:collapse;width:auto;">';

        foreach ($table->getRows() as $row) {
            $html .= '<tr>';

            foreach ($row->getCells() as $cell) {
                $html .= self::renderPhpWordCell(
                    $cell,
                    $mainObject
                );
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Render Word table cell.
     */
    protected static function renderPhpWordCell(
        Cell $cell,
        $mainObject
    ): string {
        $html = '<td>';

        foreach ($cell->getElements() as $element) {
            $html .= self::renderPhpWordElement(
                $element,
                $mainObject
            );
        }

        $html .= '</td>';

        return $html;
    }

    /**
     * Convert Word paragraph style to CSS.
     */
    protected static function getPhpWordParagraphStyle(
        $style
    ): string {
        if (
            !$style
            || is_string($style)
        ) {
            return '';
        }

        $styles = [];

        /**
         * Alignment.
         */
        if (method_exists($style, 'getAlignment')) {
            try {
                $alignment =
                    $style->getAlignment();

                $alignmentMap = [
                    'left'    => 'left',
                    'center'  => 'center',
                    'right'   => 'right',
                    'both'    => 'justify',
                    'justify' => 'justify',
                ];

                $alignmentValue =
                    strtolower(
                        (string) $alignment
                    );

                if (
                    isset(
                        $alignmentMap[
                            $alignmentValue
                        ]
                    )
                ) {
                    $styles[] =
                        'text-align:'
                        . $alignmentMap[
                            $alignmentValue
                        ];
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Space before.
         */
        if (method_exists($style, 'getSpaceBefore')) {
            try {
                $spaceBefore =
                    $style->getSpaceBefore();

                if (
                    $spaceBefore !== null
                    && is_numeric($spaceBefore)
                ) {
                    $styles[] =
                        'margin-top:'
                        . (
                            (float) $spaceBefore
                            / 20
                        )
                        . 'pt';
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Space after.
         */
        if (method_exists($style, 'getSpaceAfter')) {
            try {
                $spaceAfter =
                    $style->getSpaceAfter();

                if (
                    $spaceAfter !== null
                    && is_numeric($spaceAfter)
                ) {
                    $styles[] =
                        'margin-bottom:'
                        . (
                            (float) $spaceAfter
                            / 20
                        )
                        . 'pt';
                }
            } catch (\Throwable $e) {
            }
        }

        /**
         * Line spacing.
         */
        if (method_exists($style, 'getLineSpacing')) {
            try {
                $lineSpacing =
                    $style->getLineSpacing();

                if (
                    $lineSpacing !== null
                    && is_numeric($lineSpacing)
                ) {
                    $styles[] =
                        'line-height:'
                        . (float) $lineSpacing;
                }
            } catch (\Throwable $e) {
            }
        }

        return implode(
            ';',
            $styles
        );
    }

    /**
     * Detect Word heading.
     *
     * Heading 1 -> h1
     * Heading 2 -> h2
     * Heading 3 -> h3
     * ...
     */
    protected static function getPhpWordHeadingTag(
        $style
    ): string {
        if (!$style) {
            return 'p';
        }
        
        /**
         * Style can be a string.
         */
        if (is_string($style)) {
            $styleName = trim($style);
        } else {
            $styleName = '';

            /**
             * Try getStyleName().
             */
            if (method_exists($style, 'getStyleName')) {
                try {
                    $styleName =
                        (string)
                        $style->getStyleName();
                } catch (\Throwable $e) {
                    $styleName = '';
                }
            }

            /**
             * Try getName().
             */
            if (
                !$styleName
                && method_exists($style, 'getName')
            ) {
                try {
                    $styleName =
                        (string)
                        $style->getName();
                } catch (\Throwable $e) {
                    $styleName = '';
                }
            }

            $styleName = trim(
                $styleName
            );
        }
        if (!$styleName) {
            return 'p';
        }

        /**
         * Normalize:
         *
         * Heading 1
         * Heading-1
         * Heading_1
         * Heading1
         *
         * => heading1
         */
        $normalized =
            preg_replace(
                '/[\s_-]+/',
                '',
                strtolower($styleName)
            );

        if (
            preg_match(
                '/^heading([1-6])$/',
                $normalized,
                $matches
            )
        ) {
            return 'h' . $matches[1];
        }

        /**
         * Custom styles containing Heading.
         */
        if (
            preg_match(
                '/heading[\s_-]*(1|2|3|4|5|6)/i',
                $styleName,
                $matches
            )
        ) {
            return 'h' . $matches[1];
        }

        return 'p';
    }

    /**
     * Sanitize asset filename.
     */
    protected static function sanitizeFileName(
        string $name
    ): string {
        $name = trim($name);

        /**
         * Remove path.
         */
        $name = basename($name);

        /**
         * Replace unsupported characters.
         */
        $name = preg_replace(
            '/[^\pL\pN\-_\.]+/u',
            '-',
            $name
        );

        /**
         * Remove duplicate hyphens.
         */
        $name = preg_replace(
            '/-+/',
            '-',
            $name
        );

        /**
         * Remove leading/trailing characters.
         */
        $name = trim(
            $name,
            '-.'
        );

        if (!$name) {
            return 'word-image-' . uniqid();
        }

        return $name;
    }
}