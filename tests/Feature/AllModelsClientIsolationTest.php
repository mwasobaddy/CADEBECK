<?php

use App\Models\Application;
use App\Models\Attendance;
use App\Models\Audit;
use App\Models\Branch;
use App\Models\Client;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\JobAdvert;
use App\Models\LaunchSubscriber;
use App\Models\LeaveRequest;
use App\Models\LoanRepayment;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\Payslip;
use App\Models\User;
use App\Models\WellBeingResponse;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * Data-level isolation coverage for every model carrying the client scope.
 *
 * The other client tests prove the mechanism on a handful of models; this one
 * walks the full set so a model that lost its trait, or a table that never got
 * client_id, fails loudly.
 */

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    // The multitenancy package binds the current tenant in the container, which
    // outlives a single test in the same process.
    Client::forgetCurrent();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/** Create a client with a full, connected set of rows. */
function buildClientFixture(string $slug): Client
{
    $client = Client::create(['name' => ucfirst($slug), 'slug' => $slug]);
    ClientContext::override($client->id);

    $location = Location::create([
        'name' => 'HQ', 'code' => 'LOC', 'city' => 'London', 'country' => 'UK',
    ]);
    $branch = Branch::create(['name' => 'Main', 'code' => 'BR', 'location_id' => $location->id]);
    $department = Department::create(['name' => 'People', 'code' => 'DEP', 'branch_id' => $branch->id]);
    $designation = Designation::create(['name' => 'Analyst', 'code' => 'DES']);
    $contractType = ContractType::create(['name' => 'Full Time', 'code' => 'CT']);

    $user = User::factory()->create();
    $employee = Employee::create([
        'user_id' => $user->id, 'staff_number' => 'SN-1',
        'location_id' => $location->id, 'branch_id' => $branch->id,
        'department_id' => $department->id, 'designation_id' => $designation->id,
        'contract_type_id' => $contractType->id,
        'date_of_join' => now(), 'basic_salary' => 50000, 'salary_frequency' => 'monthly',
    ]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id, 'payroll_period' => '2026-01', 'pay_date' => now(),
        'basic_salary' => 50000, 'gross_pay' => 50000, 'net_pay' => 40000, 'status' => 'processed',
    ]);

    Payslip::create([
        'employee_id' => $employee->id, 'payroll_id' => $payroll->id,
        'payslip_number' => 'PSL-'.$slug, 'payroll_period' => '2026-01', 'pay_date' => now(),
        'file_name' => 'payslip-'.$slug.'.pdf', 'payslip_data' => 'x',
        'is_emailed' => false, 'is_downloaded' => false,
    ]);

    PayrollAllowance::create([
        'payroll_id' => $payroll->id, 'employee_id' => $employee->id,
        'allowance_type' => 'House', 'description' => 'x', 'amount' => 1000,
        'is_recurring' => true, 'effective_date' => now(), 'status' => 'active',
    ]);
    PayrollDeduction::create([
        'payroll_id' => $payroll->id, 'employee_id' => $employee->id,
        'deduction_type' => 'PAYE', 'description' => 'x', 'amount' => 500,
        'is_recurring' => true, 'effective_date' => now(), 'status' => 'active',
    ]);

    $loan = EmployeeLoan::create([
        'employee_id' => $employee->id, 'loan_type' => 'Advance', 'loan_number' => 'LN-'.$slug,
        'principal_amount' => 2000, 'interest_rate' => 5, 'term_months' => 12,
        'monthly_installment' => 170, 'total_amount' => 2040, 'remaining_balance' => 2000,
        'start_date' => now(), 'end_date' => now()->addYear(), 'status' => 'active',
    ]);
    LoanRepayment::create([
        'employee_loan_id' => $loan->id, 'installment_number' => 1, 'amount' => 170,
        'principal_amount' => 162, 'interest_amount' => 8,
        'balance_before' => 2000, 'balance_after' => 1838,
        'payment_date' => now(), 'status' => 'paid',
    ]);

    Attendance::create([
        'employee_id' => $employee->id, 'user_id' => $user->id,
        'date' => now()->toDateString(), 'status' => 'present',
    ]);

    LeaveRequest::create([
        'employee_id' => $employee->id, 'user_id' => $user->id,
        'leave_type' => 'annual', 'start_date' => now(), 'end_date' => now()->addDay(),
        'days_requested' => 1, 'reason' => 'x', 'status' => 'pending',
    ]);

    WellBeingResponse::create([
        'employee_id' => $employee->id, 'user_id' => $user->id,
        'assessment_type' => 'weekly',
        'period_start_date' => now()->startOfWeek(), 'period_end_date' => now()->endOfWeek(),
        'frequency' => 'weekly', 'stress_level' => 5, 'work_life_balance' => 6,
        'job_satisfaction' => 7, 'support_level' => 8,
    ]);

    $advert = JobAdvert::create([
        'title' => 'Analyst', 'slug' => 'analyst-'.$slug, 'description' => 'x',
        'deadline' => now()->addDays(30), 'status' => 'Published', 'posted_by' => $user->id,
    ]);

    Application::create([
        'job_advert_id' => $advert->id, 'name' => 'Applicant', 'email' => $slug.'@example.test',
        'phone' => '0700', 'cv_blob' => 'x', 'cover_letter' => 'x', 'status' => 'Pending',
    ]);

    Audit::create(['actor_id' => $user->id, 'action' => 'create', 'target_type' => User::class]);

    return $client;
}

it('scopes every client owned model to the current client', function () {
    $acme = buildClientFixture('acme');
    $globex = buildClientFixture('globex');

    $models = [
        Application::class, Attendance::class, Audit::class, Branch::class,
        ContractType::class, Department::class, Designation::class, Employee::class,
        EmployeeLoan::class, JobAdvert::class, LeaveRequest::class, LoanRepayment::class,
        Location::class, Payroll::class, PayrollAllowance::class, PayrollDeduction::class,
        Payslip::class, User::class, WellBeingResponse::class,
    ];

    $acmeUser = User::withoutGlobalScopes()->where('client_id', $acme->id)->first();

    // Act as a client-1 user.
    $this->actingAs($acmeUser);
    ClientContext::override($acme->id);

    foreach ($models as $model) {
        $seen = $model::query()->pluck('client_id')->unique()->all();

        expect($seen)->toBe([$acme->id], class_basename($model).' leaked another client');
    }
});

it('gives an access_all_clients holder rows from every client', function () {
    $acme = buildClientFixture('acme');
    $globex = buildClientFixture('globex');

    Permission::firstOrCreate(['name' => 'access_all_clients', 'guard_name' => 'web']);
    $role = Role::create([
        'name' => 'Developer', 'guard_name' => 'web', 'client_id' => null,
    ]);
    $role->givePermissionTo('access_all_clients');

    // Inserted directly so no client hook or auth fallback can attach a client:
    // platform staff belong to no client by definition.
    $developerId = DB::table('users')->insertGetId([
        'first_name' => 'Dev',
        'other_names' => 'User',
        'email' => 'dev@example.test',
        'password' => bcrypt('Password123!'),
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $developer = User::withoutGlobalScopes()->findOrFail($developerId);
    expect($developer->client_id)->toBeNull();

    ClientContext::override(null);
    $developer->assignRole($role);

    $this->actingAs($developer);
    ClientContext::override(null);

    // Clients stay visible to platform staff.
    expect(Client::count())->toBe(2);


    foreach ([Employee::class, Payroll::class, Payslip::class, LeaveRequest::class] as $model) {
        $clients = $model::query()->pluck('client_id')->unique()->sort()->values()->all();

        expect($clients)->toBe([$acme->id, $globex->id], class_basename($model).' should span clients');
    }
});

it('returns no rows to an authenticated user with no client and no cross client permission', function () {
    buildClientFixture('acme');
    buildClientFixture('globex');

    // Present as a platform user with no client and no cross-client permission.
    // The fixtures left a client override in place, so clear it explicitly.
    ClientContext::override(null);

    $platform = User::factory()->create(['client_id' => null]);
    ClientContext::override(null);

    $this->actingAs($platform);
    ClientContext::override(null);

    expect(Employee::count())->toBe(0)
        ->and(Payroll::count())->toBe(0)
        ->and(Location::count())->toBe(0);
});

it('leaves launch subscribers unassigned because they are platform level', function () {
    buildClientFixture('acme');
    buildClientFixture('globex');

    // launch_subscribers has a nullable client_id by design, so it is created
    // outside any client context, the way the public signup form does.
    ClientContext::override(null);
    LaunchSubscriber::create(['email' => 'lead@example.test']);

    expect(DB::table('launch_subscribers')->whereNull('client_id')->count())->toBe(1);
});

it('leaves an unscoped context working for console and queue work', function () {
    buildClientFixture('acme');
    buildClientFixture('globex');

    // No authenticated user and no override: system tooling still sees
    // everything, which is what seeders and scheduled jobs rely on.
    ClientContext::override(null);
    auth()->logout();

    expect(Employee::acrossClients()->count())->toBe(2);
});