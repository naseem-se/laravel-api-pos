<?php

namespace Tests\Feature;

use App\Http\Requests\Printer\StorePrinterRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PrinterRequestTest extends TestCase
{
    public function test_network_printer_requires_a_valid_ip_address(): void
    {
        $rules = (new StorePrinterRequest())->rules();
        $missingIp = Validator::make([
            'name' => 'Counter printer',
            'purpose' => 'both',
            'connection_type' => 'network',
        ], $rules);
        $invalidIp = Validator::make([
            'name' => 'Counter printer',
            'purpose' => 'both',
            'connection_type' => 'network',
            'ip' => 'not-an-ip',
        ], $rules);

        $this->assertTrue($missingIp->fails());
        $this->assertTrue($invalidIp->fails());
    }

    public function test_system_printer_requires_an_installed_printer_name(): void
    {
        $rules = (new StorePrinterRequest())->rules();
        $missingName = Validator::make([
            'name' => 'Receipt printer',
            'purpose' => 'receipt',
            'connection_type' => 'system',
        ], $rules);
        $validPrinter = Validator::make([
            'name' => 'Receipt printer',
            'purpose' => 'receipt',
            'connection_type' => 'system',
            'system_printer_name' => 'Thermal Printer',
        ], $rules);

        $this->assertTrue($missingName->fails());
        $this->assertFalse($validPrinter->fails());
    }
}
