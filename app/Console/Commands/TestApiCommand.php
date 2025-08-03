<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\AdministrativeRegion;
use App\Models\PostalCode;

class TestApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:test {--endpoint=all : Specific endpoint to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Indonesian Administrative Regions API endpoints';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $endpoint = $this->option('endpoint');
        $baseUrl = config('app.url') . '/api/v1';

        $this->info('Testing Indonesian Administrative Regions API...');
        $this->info("Base URL: {$baseUrl}");
        $this->newLine();

        if ($endpoint === 'all' || $endpoint === 'regions') {
            $this->testRegionsEndpoint($baseUrl);
        }

        if ($endpoint === 'all' || $endpoint === 'postal-codes') {
            $this->testPostalCodesEndpoint($baseUrl);
        }

        if ($endpoint === 'all' || $endpoint === 'search') {
            $this->testSearchEndpoint($baseUrl);
        }

        if ($endpoint === 'all' || $endpoint === 'stats') {
            $this->testStatsEndpoint($baseUrl);
        }

        $this->info('API testing completed!');
        return 0;
    }

    /**
     * Test regions endpoint
     */
    private function testRegionsEndpoint(string $baseUrl): void
    {
        $this->info('Testing /regions endpoint...');

        try {
            $response = Http::get("{$baseUrl}/regions");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✓ GET /regions - Status: {$response->status()}");
                $this->info("  Total regions: " . ($data['meta']['total'] ?? 'N/A'));
            } else {
                $this->error("✗ GET /regions - Status: {$response->status()}");
                $this->error("  Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /regions - Exception: " . $e->getMessage());
        }

        // Test with filters
        try {
            $response = Http::get("{$baseUrl}/regions", [
                'type' => 'provinsi',
                'per_page' => 5
            ]);
            
            if ($response->successful()) {
                $this->info("✓ GET /regions with filters - Status: {$response->status()}");
            } else {
                $this->error("✗ GET /regions with filters - Status: {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /regions with filters - Exception: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Test postal codes endpoint
     */
    private function testPostalCodesEndpoint(string $baseUrl): void
    {
        $this->info('Testing /postal-codes endpoint...');

        try {
            $response = Http::get("{$baseUrl}/postal-codes");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✓ GET /postal-codes - Status: {$response->status()}");
                $this->info("  Total postal codes: " . ($data['meta']['total'] ?? 'N/A'));
            } else {
                $this->error("✗ GET /postal-codes - Status: {$response->status()}");
                $this->error("  Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /postal-codes - Exception: " . $e->getMessage());
        }

        // Test specific postal code
        try {
            $response = Http::get("{$baseUrl}/postal-codes/10110");
            
            if ($response->successful()) {
                $this->info("✓ GET /postal-codes/10110 - Status: {$response->status()}");
            } else {
                $this->info("ℹ GET /postal-codes/10110 - Status: {$response->status()} (may not exist)");
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /postal-codes/10110 - Exception: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Test search endpoint
     */
    private function testSearchEndpoint(string $baseUrl): void
    {
        $this->info('Testing /search endpoint...');

        try {
            $response = Http::get("{$baseUrl}/search", [
                'q' => 'jakarta'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✓ GET /search - Status: {$response->status()}");
                $this->info("  Total results: " . ($data['meta']['total_results'] ?? 'N/A'));
            } else {
                $this->error("✗ GET /search - Status: {$response->status()}");
                $this->error("  Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /search - Exception: " . $e->getMessage());
        }

        // Test autocomplete
        try {
            $response = Http::get("{$baseUrl}/autocomplete", [
                'q' => 'jak'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✓ GET /autocomplete - Status: {$response->status()}");
                $this->info("  Suggestions: " . ($data['meta']['total_suggestions'] ?? 'N/A'));
            } else {
                $this->error("✗ GET /autocomplete - Status: {$response->status()}");
                $this->error("  Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /autocomplete - Exception: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Test stats endpoint
     */
    private function testStatsEndpoint(string $baseUrl): void
    {
        $this->info('Testing /stats endpoint...');

        try {
            $response = Http::get("{$baseUrl}/stats");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✓ GET /stats - Status: {$response->status()}");
                $this->info("  Total regions: " . ($data['data']['total_regions'] ?? 'N/A'));
                $this->info("  Total postal codes: " . ($data['data']['total_postal_codes'] ?? 'N/A'));
            } else {
                $this->error("✗ GET /stats - Status: {$response->status()}");
                $this->error("  Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("✗ GET /stats - Exception: " . $e->getMessage());
        }

        $this->newLine();
    }
}