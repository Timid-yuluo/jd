<?php

declare(strict_types=1);

namespace App\Services\Resume\Builders;

final class DocxXmlBuilder
{
    private string $fontName = 'Microsoft YaHei';

    private int $baseFontSize = 22;

    private int $lineTwips = 360;

    private int $defaultParagraphSpacingAfter = 120;

    private ?string $defaultBodyColor = null;

    public function applyTypography(array $typography): void
    {
        $this->fontName = $this->sanitizeFontName((string) ($typography['font_name'] ?? 'Microsoft YaHei'));
        $this->baseFontSize = $this->clampInt((int) ($typography['base_font_size'] ?? 22), 18, 32);
        $this->lineTwips = $this->clampInt((int) ($typography['line_twips'] ?? 360), 240, 560);
        $this->defaultParagraphSpacingAfter = $this->clampInt((int) ($typography['paragraph_spacing_after'] ?? 120), 40, 260);
        $this->defaultBodyColor = $this->sanitizeHexColor((string) ($typography['body_color'] ?? ''));
    }

    public function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * @param  array{style?:string,center?:bool,spacing_before?:int,spacing_after?:int,indent_left?:int,size?:int,color?:string,shading_fill?:string,bold?:bool,line_twips?:int}  $options
     */
    public function paragraph(string $text, array $options = []): string
    {
        $paragraphProps = '';
        if (! empty($options['style'])) {
            $paragraphProps .= '<w:pStyle w:val="'.$this->xml((string) $options['style']).'"/>';
        }
        if (! empty($options['center'])) {
            $paragraphProps .= '<w:jc w:val="center"/>';
        }
        $spacingBefore = (int) ($options['spacing_before'] ?? 0);
        $spacingAfter = (int) ($options['spacing_after'] ?? $this->defaultParagraphSpacingAfter);
        $lineTwips = (int) ($options['line_twips'] ?? $this->lineTwips);
        $paragraphProps .= '<w:spacing w:before="'.$spacingBefore.'" w:after="'.$spacingAfter.'" w:line="'.$lineTwips.'" w:lineRule="auto"/>';
        $indentLeft = (int) ($options['indent_left'] ?? 0);
        if ($indentLeft > 0) {
            $paragraphProps .= '<w:ind w:left="'.$indentLeft.'"/>';
        }

        $fontSize = (int) ($options['size'] ?? $this->baseFontSize);
        $color = strtoupper((string) ($options['color'] ?? ($this->defaultBodyColor ?? '')));
        $shadingFill = strtoupper((string) ($options['shading_fill'] ?? ''));
        $runProps = $this->fontRun($fontSize);
        if (! empty($options['bold'])) {
            $runProps .= '<w:b/><w:bCs/>';
        }
        if ($color !== '') {
            $runProps .= '<w:color w:val="'.$this->xml($color).'"/>';
        }
        if ($shadingFill !== '') {
            $runProps .= '<w:shd w:val="clear" w:color="auto" w:fill="'.$this->xml($shadingFill).'"/>';
        }

        return '<w:p><w:pPr>'.$paragraphProps.'</w:pPr><w:r><w:rPr>'.$runProps.'</w:rPr><w:t xml:space="preserve">'.$this->xml($text).'</w:t></w:r></w:p>';
    }

    public function dividerParagraph(string $accent): string
    {
        return '<w:p><w:pPr><w:spacing w:after="160"/><w:pBdr><w:bottom w:val="single" w:sz="10" w:space="1" w:color="'.$this->xml(strtoupper($accent)).'"/></w:pBdr></w:pPr></w:p>';
    }

    public function infoCardTable(
        string $name,
        string $targetJob,
        string $contact,
        string $nameColor,
        string $fill,
        ?string $avatarRelationshipId = null,
        ?string $targetColor = null,
        ?string $contactColor = null
    ): string {
        $name = $this->xml($name !== '' ? $name : '简历');
        $targetJob = $this->xml($targetJob);
        $contact = $this->xml($contact);
        $nameColor = $this->xml(strtoupper($nameColor));
        $fill = $this->xml(strtoupper($fill));
        $targetColor = $this->xml(strtoupper((string) ($targetColor ?: '111827')));
        $contactColor = $this->xml(strtoupper((string) ($contactColor ?: '4B5563')));

        $nameSize = $this->clampInt((int) round($this->baseFontSize * 1.55), 26, 42);
        $targetSize = $this->clampInt((int) round($this->baseFontSize * 1.0), 18, 30);
        $contactSize = $this->clampInt((int) round($this->baseFontSize * 0.9), 16, 28);

        $textLines = [];
        $textLines[] = '<w:p><w:pPr><w:jc w:val="'.($avatarRelationshipId !== null ? 'left' : 'center').'"/><w:spacing w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:bCs/><w:color w:val="'.$nameColor.'"/>'.$this->fontRun($nameSize).'</w:rPr><w:t xml:space="preserve">'.$name.'</w:t></w:r></w:p>';

        if ($targetJob !== '') {
            $textLines[] = '<w:p><w:pPr><w:jc w:val="'.($avatarRelationshipId !== null ? 'left' : 'center').'"/><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:b/><w:bCs/><w:color w:val="'.$targetColor.'"/>'.$this->fontRun($targetSize).'</w:rPr><w:t xml:space="preserve">目标岗位：'.$targetJob.'</w:t></w:r></w:p>';
        }

        if ($contact !== '') {
            $textLines[] = '<w:p><w:pPr><w:jc w:val="'.($avatarRelationshipId !== null ? 'left' : 'center').'"/><w:spacing w:after="80"/></w:pPr><w:r><w:rPr><w:color w:val="'.$contactColor.'"/>'.$this->fontRun($contactSize).'</w:rPr><w:t xml:space="preserve">'.$contact.'</w:t></w:r></w:p>';
        }

        if ($avatarRelationshipId === null) {
            return '<w:tbl>'
                .'<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:jc w:val="center"/><w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders><w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="160" w:type="dxa"/><w:bottom w:w="40" w:type="dxa"/><w:right w:w="160" w:type="dxa"/></w:tblCellMar></w:tblPr>'
                .'<w:tblGrid><w:gridCol w:w="10400"/></w:tblGrid>'
                .'<w:tr><w:tc><w:tcPr><w:tcW w:w="10400" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/></w:tcPr>'
                .implode('', $textLines)
                .'</w:tc></w:tr>'
                .'</w:tbl>'
                .'<w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>';
        }

        return '<w:tbl>'
            .'<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:jc w:val="center"/><w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders><w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="160" w:type="dxa"/><w:bottom w:w="40" w:type="dxa"/><w:right w:w="160" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tblGrid><w:gridCol w:w="2200"/><w:gridCol w:w="8200"/></w:tblGrid>'
            .'<w:tr>'
            .'<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/><w:vAlign w:val="center"/><w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/></w:tcPr>'
            .'<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0"/></w:pPr>'.$this->imageRun($avatarRelationshipId, 900000, 900000).'</w:p>'
            .'</w:tc>'
            .'<w:tc><w:tcPr><w:tcW w:w="8200" w:type="dxa"/><w:vAlign w:val="center"/><w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/></w:tcPr>'
            .implode('', $textLines)
            .'</w:tc>'
            .'</w:tr>'
            .'</w:tbl>'
            .'<w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>';
    }

    public function entryHeaderTable(string $title, string $date, string $location, string $fill): string
    {
        $title = $this->xml($title !== '' ? $title : '项目/经历');
        $date = $this->xml($date);
        $location = $this->xml($location);
        $fill = $this->xml(strtoupper($fill));

        $titleSize = $this->clampInt((int) round($this->baseFontSize * 1.1), 20, 32);
        $metaSize = $this->clampInt((int) round($this->baseFontSize * 0.9), 16, 28);

        $rows = [];
        $rows[] = '<w:tr>'
            .'<w:tc>'
            .'<w:tcPr><w:tcW w:w="7600" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/></w:tcPr>'
            .'<w:p><w:pPr><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:b/><w:bCs/><w:color w:val="111827"/>'.$this->fontRun($titleSize).'</w:rPr><w:t xml:space="preserve">'.$title.'</w:t></w:r></w:p>'
            .'</w:tc>'
            .'<w:tc>'
            .'<w:tcPr><w:tcW w:w="2800" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/></w:tcPr>'
            .'<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:color w:val="6B7280"/>'.$this->fontRun($metaSize).'</w:rPr><w:t xml:space="preserve">'.$date.'</w:t></w:r></w:p>'
            .'</w:tc>'
            .'</w:tr>';

        if ($location !== '') {
            $rows[] = '<w:tr>'
                .'<w:tc>'
                .'<w:tcPr><w:tcW w:w="10400" w:type="dxa"/><w:gridSpan w:val="2"/></w:tcPr>'
                .'<w:p><w:pPr><w:spacing w:after="80"/></w:pPr><w:r><w:rPr><w:color w:val="6B7280"/>'.$this->fontRun($metaSize).'</w:rPr><w:t xml:space="preserve">'.$location.'</w:t></w:r></w:p>'
                .'</w:tc>'
                .'</w:tr>';
        }

        return '<w:tbl>'
            .'<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders><w:tblCellMar><w:top w:w="60" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="20" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tblGrid><w:gridCol w:w="7600"/><w:gridCol w:w="2800"/></w:tblGrid>'
            .implode('', $rows)
            .'</w:tbl>'
            .'<w:p><w:pPr><w:spacing w:after="40"/></w:pPr></w:p>';
    }

    public function imageRun(string $relationshipId, int $widthEmu, int $heightEmu): string
    {
        return '<w:r><w:drawing>'
            .'<wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$widthEmu.'" cy="'.$heightEmu.'"/>'
            .'<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            .'<wp:docPr id="1" name="Avatar"/>'
            .'<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="Avatar"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$this->xml($relationshipId).'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$widthEmu.'" cy="'.$heightEmu.'"/></a:xfrm>'
            .'<a:prstGeom prst="ellipse"><a:avLst/></a:prstGeom></pic:spPr></pic:pic>'
            .'</a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }

    /**
     * @param  array<int, array{path:string,content:string,extension:string}>  $media
     */
    public function contentTypesXml(array $media): string
    {
        $defaults = [
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>',
            '<Default Extension="xml" ContentType="application/xml"/>',
        ];

        $imageTypes = [];
        foreach ($media as $item) {
            $imageTypes[$item['extension']] = match ($item['extension']) {
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                default => null,
            };
        }

        foreach ($imageTypes as $extension => $mime) {
            if ($mime !== null) {
                $defaults[] = '<Default Extension="'.$extension.'" ContentType="'.$mime.'"/>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .implode('', $defaults)
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/word/fontTable.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.fontTable+xml"/>'
            .'<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    public function footerXml(string $siteName, string $timestamp): string
    {
        $siteName = $this->xml($siteName !== '' ? $siteName : '职路通');
        $timestamp = $this->xml($timestamp);
        $footerFont = $this->clampInt((int) round($this->baseFontSize * 0.75), 14, 22);
        $run = $this->fontRun($footerFont);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:p>'
            .'<w:pPr><w:tabs><w:tab w:val="center" w:pos="4680"/><w:tab w:val="right" w:pos="9360"/></w:tabs><w:spacing w:after="0"/></w:pPr>'
            .'<w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t xml:space="preserve">'.$siteName.'</w:t></w:r>'
            .'<w:r><w:tab/></w:r>'
            .'<w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t xml:space="preserve">导出时间 '.$timestamp.'</w:t></w:r>'
            .'<w:r><w:tab/></w:r>'
            .'<w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t xml:space="preserve">第 </w:t></w:r>'
            .'<w:fldSimple w:instr=" PAGE "><w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t>1</w:t></w:r></w:fldSimple>'
            .'<w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t xml:space="preserve"> / </w:t></w:r>'
            .'<w:fldSimple w:instr=" NUMPAGES "><w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t>1</w:t></w:r></w:fldSimple>'
            .'<w:r><w:rPr><w:color w:val="9CA3AF"/>'.$run.'</w:rPr><w:t xml:space="preserve"> 页</w:t></w:r>'
            .'</w:p>'
            .'</w:ftr>';
    }

    public function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array{id:string,type:string,target:string}>  $relationships
     */
    public function documentRelsXml(array $relationships): string
    {
        $items = array_map(
            fn (array $relationship): string => '<Relationship Id="'.$this->xml($relationship['id']).'" Type="'.$this->xml($relationship['type']).'" Target="'.$this->xml($relationship['target']).'"/>',
            $relationships
        );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .implode('', $items)
            .'</Relationships>';
    }

    public function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Laravel Resume Export</Application>'
            .'</Properties>';
    }

    public function coreXml(string $title, string $createdAt): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            .'xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$title.'</dc:title>'
            .'<dc:creator>职路通</dc:creator>'
            .'<cp:lastModifiedBy>职路通</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$this->xml($createdAt).'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$this->xml($createdAt).'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    /**
     * @param  array<string,string|int>  $variant
     */
    public function stylesXml(array $variant, array $typography = []): string
    {
        $sectionColor = $this->xml(strtoupper((string) ($variant['section_color'] ?? '2563EB')));
        $sectionBorderColor = $this->xml(strtoupper((string) ($variant['section_border_color'] ?? '2563EB')));
        $sectionSize = $this->clampInt((int) ($variant['section_size'] ?? 26), 20, 40);
        $entryTitleColor = $this->xml(strtoupper((string) ($variant['entry_title_color'] ?? '111827')));
        $sectionSpacingBefore = $this->clampInt((int) ($typography['section_spacing_twips'] ?? 260), 120, 760);
        $sectionSpacingAfter = $this->clampInt((int) round($sectionSpacingBefore * 0.45), 80, 360);
        $normalColor = $this->defaultBodyColor !== null ? '<w:color w:val="'.$this->xml($this->defaultBodyColor).'"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/>'
            .'<w:rPr>'.$normalColor.$this->fontRun($this->baseFontSize).'</w:rPr>'
            .'</w:style>'
            .'<w:style w:type="paragraph" w:styleId="ResumeSection"><w:name w:val="Resume Section"/><w:basedOn w:val="Normal"/>'
            .'<w:pPr><w:spacing w:before="'.$sectionSpacingBefore.'" w:after="'.$sectionSpacingAfter.'"/><w:pBdr><w:bottom w:val="single" w:sz="10" w:space="1" w:color="'.$sectionBorderColor.'"/></w:pBdr></w:pPr>'
            .'<w:rPr><w:b/><w:bCs/><w:color w:val="'.$sectionColor.'"/>'.$this->fontRun($sectionSize).'</w:rPr>'
            .'</w:style>'
            .'<w:style w:type="paragraph" w:styleId="ResumeEntryTitle"><w:name w:val="Resume Entry Title"/><w:basedOn w:val="Normal"/>'
            .'<w:pPr><w:spacing w:before="60" w:after="40"/></w:pPr>'
            .'<w:rPr><w:b/><w:bCs/><w:color w:val="'.$entryTitleColor.'"/>'.$this->fontRun($this->clampInt($this->baseFontSize + 2, 20, 34)).'</w:rPr>'
            .'</w:style>'
            .'<w:style w:type="paragraph" w:styleId="ResumeMeta"><w:name w:val="Resume Meta"/><w:basedOn w:val="Normal"/>'
            .'<w:pPr><w:spacing w:after="120"/></w:pPr>'
            .'<w:rPr><w:color w:val="6B7280"/>'.$this->fontRun($this->clampInt($this->baseFontSize - 2, 16, 28)).'</w:rPr>'
            .'</w:style>'
            .'</w:styles>';
    }

    public function fontTableXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:fonts xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:font w:name="'.$this->xml($this->fontName).'"><w:family w:val="auto"/><w:charset w:val="86"/></w:font>'
            .'</w:fonts>';
    }

    private function fontRun(int $fontSize): string
    {
        return '<w:rFonts w:ascii="'.$this->xml($this->fontName).'" w:hAnsi="'.$this->xml($this->fontName).'" w:eastAsia="'.$this->xml($this->fontName).'" w:cs="'.$this->xml($this->fontName).'"/><w:sz w:val="'.$fontSize.'"/><w:szCs w:val="'.$fontSize.'"/>';
    }

    private function sanitizeFontName(string $fontName): string
    {
        $value = trim(preg_replace('/["\']+/', '', $fontName) ?? '');
        if ($value === '') {
            return 'Microsoft YaHei';
        }

        $parts = array_values(array_filter(array_map(static fn (string $item): string => trim($item), explode(',', $value))));

        return $parts[0] ?? 'Microsoft YaHei';
    }

    private function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private function sanitizeHexColor(string $value): ?string
    {
        $hex = trim($value);
        if ($hex === '') {
            return null;
        }

        $hex = ltrim($hex, '#');
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex) !== 1) {
            return null;
        }

        return strtoupper($hex);
    }
}
