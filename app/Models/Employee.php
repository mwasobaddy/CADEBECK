<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $date_of_birth
 * @property string|null $gender
 * @property string|null $mobile_number
 * @property string|null $home_address
 * @property string $staff_number
 * @property int $location_id
 * @property int $branch_id
 * @property int $department_id
 * @property int $designation_id
 * @property string|null $date_of_join
 * @property int $contract_type_id
 * @property float $basic_salary
 */
class Employee extends Model
{
    use SoftDeletes, Notifiable;
    protected $fillable = [
        'user_id', 'date_of_birth', 'gender', 'mobile_number', 'home_address', 'staff_number', 'location_id', 'branch_id', 'department_id', 'designation_id', 'date_of_join', 'contract_type_id', 'supervisor_id', 'basic_salary'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function wellBeingResponses()
    {
        return $this->hasMany(WellBeingResponse::class);
    }

    // Payroll relationships
    public function payrollAllowances()
    {
        return $this->hasMany(PayrollAllowance::class);
    }

    public function payrollDeductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function employeeLoans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail($notification)
    {
        // Ensure user relationship is loaded
        if (!$this->relationLoaded('user')) {
            $this->load('user');
        }

        return $this->user?->email;
    }

    /**
     * Scope for active employees (not soft deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
