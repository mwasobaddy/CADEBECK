<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 */
class Employee extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id', 'date_of_birth', 'gender', 'mobile_number', 'home_address', 'staff_number', 'location_id', 'branch_id', 'department_id', 'designation_id', 'date_of_join', 'contract_type_id'
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
}
