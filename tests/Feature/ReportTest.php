<?php

namespace Tests\Feature;

use App\Services\ReportService;
use Tests\Support\Make;

class ReportTest extends FeatureTestCase
{
    public function test_the_daily_report_counts_the_night_of_the_day(): void
    {
        $hotel = Make::hotel();
        $rooms = Make::rooms($hotel, 101, 110);
        $company = Make::company();
        $today = today()->toDateString();

        Make::booking($hotel, $company, today()->subDay()->toDateString(), today()->addDay()->toDateString(), $rooms->slice(0, 4)->pluck('id')->all()); // staying
        Make::booking($hotel, $company, $today, today()->addDays(2)->toDateString(), $rooms->slice(4, 2)->pluck('id')->all());                   // arriving
        Make::booking($hotel, $company, today()->subDays(2)->toDateString(), $today, $rooms->slice(6, 3)->pluck('id')->all());                   // leaving

        $row = app(ReportService::class)->daily($today)['hotels'][0];

        $this->assertSame(10, $row['total']);
        $this->assertSame(6, $row['occupied']);
        $this->assertSame(4, $row['vacant']);
        $this->assertEquals(60.0, $row['percent']);
        $this->assertSame(2, $row['arrivals']);
        $this->assertSame(3, $row['departures']);
    }

    public function test_a_report_cannot_cover_more_than_93_days(): void
    {
        $this->actingAs(Make::user('admin'))
            ->get('/reports?report=occupancy&from=2030-01-01&to=2030-12-31')
            ->assertSessionHasErrors('to');
    }

    public function test_the_csv_download_opens_correctly_in_excel_and_is_safe(): void
    {
        Make::company('=cmd|calc');

        $response = $this->actingAs(Make::user('admin'))->get('/reports/csv?report=balances');
        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Company,"Total charged USD"', $content);
        // text that starts with = would run as a formula in a spreadsheet, so it gets a leading quote mark
        $this->assertStringContainsString("'=cmd|calc", $content);
        $this->assertStringNotContainsString(",=cmd", $content);
    }

    public function test_a_representative_cannot_download_reports(): void
    {
        $hotel = Make::hotel();
        $rep = Make::user('representative', ['hotel_id' => $hotel->id]);

        $this->actingAs($rep)->get('/reports/csv?report=balances')->assertForbidden();
    }
}
