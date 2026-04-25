<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

/**
 * Build a minimal xlsx binary for a sheet named "in" with the given rows.
 *
 * @param  array<int, array{0: string, 1: string}>  $rows
 */
function makePastorFeiXlsx(array $rows, string $sheetName = 'in'): string
{
    $strings = ['时间', '标题'];
    foreach ($rows as $row) {
        $strings[] = $row[1];
    }
    $strings = array_values(array_unique($strings));
    $stringIndex = array_flip($strings);

    $rowsXml = '<row r="1"><c r="A1" t="s"><v>'.$stringIndex['时间'].'</v></c>'
        .'<c r="B1" t="s"><v>'.$stringIndex['标题'].'</v></c></row>';
    $rowNum = 2;
    foreach ($rows as $row) {
        $rowsXml .= '<row r="'.$rowNum.'">'
            .'<c r="A'.$rowNum.'"><v>'.$row[0].'</v></c>'
            .'<c r="B'.$rowNum.'" t="s"><v>'.$stringIndex[$row[1]].'</v></c>'
            .'</row>';
        $rowNum++;
    }

    $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
        .count($strings).'" uniqueCount="'.count($strings).'">';
    foreach ($strings as $s) {
        $sharedStringsXml .= '<si><t>'.htmlspecialchars($s, ENT_XML1).'</t></si>';
    }
    $sharedStringsXml .= '</sst>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        .'<sheetData>'.$rowsXml.'</sheetData></worksheet>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        .'<sheets><sheet name="'.$sheetName.'" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        .'</Relationships>';

    $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        .'</Types>';

    $rootRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        .'</Relationships>';

    $tmp = tempnam(sys_get_temp_dir(), 'xfxlsx_');
    $zip = new ZipArchive;
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $rootRelsXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
    $zip->close();

    $binary = file_get_contents($tmp);
    @unlink($tmp);

    return $binary;
}

it('resolves keyword 805 to today PastorFei audio with xlsx title (UTC+8)', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260417', '第0课 测试标题'],
            ['260418', '第1课 我灵魂兴盛身体健康'],
        ])),
    ]);

    $expected = config('x-resources.r2_share_audio').'/boteli/260417.MP3';

    $response = $this->getJson('/resources/805');

    $response->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.url', $expected)
        ->assertJsonPath('data.title', '第0课 测试标题')
        ->assertJsonPath('data.description', '260417')
        ->assertJsonMissingPath('data.vid')
        ->assertJsonPath('statistics.metric', 'PastorFei')
        ->assertJsonPath('statistics.keyword', '805')
        ->assertJsonPath('statistics.type', 'audio');
});

it('uses UTC+8 when server is UTC', function () {
    // 2026-04-17 23:30 UTC === 2026-04-18 07:30 Shanghai (Saturday)
    Carbon::setTestNow(Carbon::parse('2026-04-17 23:30:00', 'UTC'));

    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260418', '第1课 我灵魂兴盛身体健康'],
        ])),
    ]);

    $expected = config('x-resources.r2_share_audio').'/boteli/260418.MP3';

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.url', $expected)
        ->assertJsonPath('data.title', '第1课 我灵魂兴盛身体健康')
        ->assertJsonPath('data.description', '260418');
});

it('falls back to default title when xlsx has no matching date', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260418', '第1课 我灵魂兴盛身体健康'],
        ])),
    ]);

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.title', '伯特利每日灵修')
        ->assertJsonPath('data.description', '260417');
});

it('falls back to default title when xlsx fetch fails', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response('', 500),
    ]);

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.title', '伯特利每日灵修')
        ->assertJsonPath('data.description', '260417');
});

it('caches xlsx until end of Shanghai day', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 10:00:00', 'Asia/Shanghai'));
    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260417', 'Day17'],
        ])),
    ]);

    $this->getJson('/resources/805')->assertOk()->assertJsonPath('data.title', 'Day17');

    // Same day → serve from cache (swap fake to prove no refetch).
    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260417', 'Changed'],
        ])),
    ]);
    $this->getJson('/resources/805')->assertOk()->assertJsonPath('data.title', 'Day17');

    // Verify TTL was set to end of Shanghai day (allow sub-second rounding).
    $endOfDay = Carbon::parse('2026-04-17', 'Asia/Shanghai')->endOfDay()->getTimestamp();
    $store = Cache::store()->getStore();
    $entries = (new ReflectionClass($store))->getProperty('storage')->getValue($store);
    $expiresAt = $entries['xbot.keyword.pastorfei.titles.260417']['expiresAt'];
    expect(abs($expiresAt - $endOfDay))->toBeLessThanOrEqual(1);
});

it('also serves on Sunday (Asia/Shanghai)', function () {
    // 2026-04-19 is a Sunday
    Carbon::setTestNow(Carbon::parse('2026-04-19 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260419', '主日灵修'],
        ])),
    ]);

    $expected = config('x-resources.r2_share_audio').'/boteli/260419.MP3';

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.url', $expected)
        ->assertJsonPath('data.title', '主日灵修')
        ->assertJsonPath('data.description', '260419');
});

it('treats Saturday UTC evening as Sunday Shanghai and still serves', function () {
    // 2026-04-18 17:00 UTC === 2026-04-19 01:00 Shanghai (Sunday)
    Carbon::setTestNow(Carbon::parse('2026-04-18 17:00:00', 'UTC'));

    Http::fake([
        '*boteli/xf2.xlsx*' => Http::response(makePastorFeiXlsx([
            ['260419', '主日灵修'],
        ])),
    ]);

    $expected = config('x-resources.r2_share_audio').'/boteli/260419.MP3';

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.url', $expected)
        ->assertJsonPath('data.title', '主日灵修')
        ->assertJsonPath('data.description', '260419');
});

it('returns 404 for non-matching keywords on PastorFei', function () {
    $this->getJson('/resources/8050')->assertNotFound();
});
