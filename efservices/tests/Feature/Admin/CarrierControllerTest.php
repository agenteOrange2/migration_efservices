<?php

namespace Tests\Feature\Admin;

use App\Models\Carrier;
use App\Models\Membership;
use App\Models\DocumentType;
use App\Models\CarrierDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CarrierControllerTest extends AdminTestCase
{
    protected $membership;
    protected $documentTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->membership = Membership::factory()->create();
        $this->documentTypes = DocumentType::factory()->count(3)->create();
    }

    /** @test */
    public function superadmin_can_access_carriers_index()
    {
        Carrier::factory()->count(3)->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.carrier.index');
    }

    /** @test */
    public function carriers_index_displays_carriers()
    {
        Carrier::factory()->count(5)->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.index'));

        $response->assertStatus(200);
        $response->assertViewHas('carriers');
    }

    /** @test */
    public function superadmin_can_view_carrier_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.carrier.create');
        $response->assertViewHas(['memberships', 'usStates']);
    }

    /** @test */
    public function superadmin_can_create_carrier()
    {
        $carrierData = [
            'name' => 'Test Carrier Company',
            'address' => '123 Test Street',
            'state' => 'CA',
            'zipcode' => '90210',
            'ein_number' => '12-3456789',
            'dot_number' => '123456789',
            'mc_number' => 'MC123456',
            'id_plan' => $this->membership->id,
            'status' => Carrier::STATUS_ACTIVE,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.carrier.store'), $carrierData);

        $response->assertRedirect();
        $this->assertDatabaseHas('carriers', [
            'name' => 'Test Carrier Company',
            'ein_number' => '12-3456789',
        ]);
    }

    /** @test */
    public function carrier_creation_requires_valid_ein_number()
    {
        $carrierData = [
            'name' => 'Test Carrier',
            'address' => '123 Test Street',
            'state' => 'CA',
            'zipcode' => '90210',
            'ein_number' => '',
            'id_plan' => $this->membership->id,
            'status' => Carrier::STATUS_ACTIVE,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.carrier.store'), $carrierData);

        $response->assertSessionHasErrors('ein_number');
    }

    /** @test */
    public function superadmin_can_view_carrier_details()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.show', $carrier));

        $response->assertStatus(200);
        $response->assertViewIs('admin.carrier.show');
    }

    /** @test */
    public function superadmin_can_view_carrier_edit_form()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.edit', $carrier));

        $response->assertStatus(200);
        $response->assertViewIs('admin.carrier.edit');
    }

    /** @test */
    public function superadmin_can_update_carrier()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $updateData = [
            'name' => 'Updated Carrier Name',
            'address' => '456 Updated Street',
            'state' => 'TX',
            'zipcode' => '75001',
            'ein_number' => $carrier->ein_number,
            'id_plan' => $this->membership->id,
            'status' => Carrier::STATUS_ACTIVE,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('admin.carrier.update', $carrier), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('carriers', [
            'id' => $carrier->id,
            'name' => 'Updated Carrier Name',
        ]);
    }

    /** @test */
    public function superadmin_can_delete_carrier()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.carrier.destroy', $carrier));

        $response->assertRedirect(route('admin.carrier.index'));
        $this->assertDatabaseMissing('carriers', ['id' => $carrier->id]);
    }

    /** @test */
    public function superadmin_can_view_carrier_documents()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.documents', $carrier));

        $response->assertStatus(200);
        $response->assertViewIs('admin.carrier.documents');
    }

    /** @test */
    public function superadmin_can_generate_missing_documents()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.carrier.generate-missing-documents', $carrier));

        $response->assertRedirect();
        $response->assertSessionHas('notification');
    }

    /** @test */
    public function carrier_show_displays_all_details()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.show', $carrier));

        $response->assertStatus(200);
        $response->assertViewHasAll([
            'carrier',
            'userCarriers',
            'drivers',
            'documents',
            'stats'
        ]);
    }

    /** @test */
    public function carrier_edit_form_has_required_data()
    {
        $carrier = Carrier::factory()->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.edit', $carrier));

        $response->assertStatus(200);
        $response->assertViewHasAll([
            'carrier',
            'memberships',
            'usStates',
            'referralUrl'
        ]);
    }

    /** @test */
    public function guest_cannot_access_carriers()
    {
        $response = $this->get(route('admin.carrier.index'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function carrier_export_excel_works()
    {
        // TODO: Implementar el método exportToExcel en CarrierController
        $this->markTestSkipped('El método exportToExcel no está implementado en el CarrierController.');

        Carrier::factory()->count(3)->create(['id_plan' => $this->membership->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.export.excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
