<?php

namespace Tests\Unit;

use App\Services\HomeContentRenderer;
use PHPUnit\Framework\TestCase;

class HomeContentRendererTest extends TestCase
{
    private HomeContentRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new HomeContentRenderer;
    }

    public function test_it_resolves_stat_placeholders(): void
    {
        $html = '<p>ROI this year: {{stat:thisYearROI}}% on {{ stat:thisYearProfit }} units.</p>';

        $result = $this->renderer->resolvePlaceholders($html, [
            'thisYearROI' => 18,
            'thisYearProfit' => 240,
        ]);

        $this->assertStringContainsString('ROI this year: 18%', $result);
        $this->assertStringContainsString('on 240 units', $result);
    }

    public function test_it_leaves_unknown_placeholders_untouched(): void
    {
        $html = 'Value: {{stat:doesNotExist}}';

        $result = $this->renderer->resolvePlaceholders($html, ['thisYearROI' => 18]);

        $this->assertSame('Value: {{stat:doesNotExist}}', $result);
    }

    public function test_it_splits_html_into_ordered_widget_segments(): void
    {
        $html = '<h1>Hi</h1>[pricing]<p>mid</p>[testimonials]<footer>bye</footer>';

        $segments = $this->renderer->segments($html);

        $this->assertCount(5, $segments);
        $this->assertSame(['type' => 'html', 'value' => '<h1>Hi</h1>'], $segments[0]);
        $this->assertSame(['type' => 'widget', 'name' => 'pricing'], $segments[1]);
        $this->assertSame(['type' => 'html', 'value' => '<p>mid</p>'], $segments[2]);
        $this->assertSame(['type' => 'widget', 'name' => 'testimonials'], $segments[3]);
        $this->assertSame(['type' => 'html', 'value' => '<footer>bye</footer>'], $segments[4]);
    }

    public function test_render_resolves_then_segments(): void
    {
        $html = '<p>{{stat:winRatio}}% win rate</p>[pricing]';

        $segments = $this->renderer->render($html, ['winRatio' => 62]);

        $this->assertSame('html', $segments[0]['type']);
        $this->assertStringContainsString('62% win rate', $segments[0]['value']);
        $this->assertSame(['type' => 'widget', 'name' => 'pricing'], $segments[1]);
    }

    public function test_plain_html_is_a_single_segment(): void
    {
        $segments = $this->renderer->segments('<p>just text</p>');

        $this->assertCount(1, $segments);
        $this->assertSame('html', $segments[0]['type']);
    }
}
