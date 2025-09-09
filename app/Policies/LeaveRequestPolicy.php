<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any leave requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_all_leaves') ||
               $user->hasPermissionTo('manage_my_leave');
    }

    /**
     * Determine whether the user can view the leave request.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        // Developer and Executive can see all requests
        if ($user->hasRole(['Developer', 'Executive'])) {
            return true;
        }

        // Users can always see their own requests
        if ($leaveRequest->employee->user_id === $user->id) {
            return true;
        }

        // Manager N-1 can see requests from employees under their supervision
        if ($user->hasRole('Manager N-1')) {
            return $this->isManagerN1SupervisorOf($user, $leaveRequest->employee);
        }

        // Manager N-2 can see requests from employees they supervise
        if ($user->hasRole('Manager N-2')) {
            return $this->isManagerN2SupervisorOf($user, $leaveRequest->employee);
        }

        return false;
    }

    /**
     * Determine whether the user can create leave requests.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_my_leave');
    }

    /**
     * Determine whether the user can update the leave request.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        // Developer and Executive can update any request
        if ($user->hasRole(['Developer', 'Executive'])) {
            return true;
        }

        // Users can update their own pending requests
        if ($leaveRequest->employee->user_id === $user->id && $leaveRequest->isPending()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve/reject the leave request.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        // Developer and Executive can approve any request
        if ($user->hasRole(['Developer', 'Executive'])) {
            return true;
        }

        // Users cannot approve their own requests
        if ($leaveRequest->employee->user_id === $user->id) {
            return false;
        }

        // Manager N-1 can approve requests from employees under their supervision
        if ($user->hasRole('Manager N-1')) {
            return $this->isManagerN1SupervisorOf($user, $leaveRequest->employee);
        }

        // Manager N-2 can approve requests from employees they supervise
        if ($user->hasRole('Manager N-2')) {
            return $this->isManagerN2SupervisorOf($user, $leaveRequest->employee);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the leave request.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        // Developer and Executive can delete any request
        if ($user->hasRole(['Developer', 'Executive'])) {
            return true;
        }

        // Users can delete their own pending requests
        if ($leaveRequest->employee->user_id === $user->id && $leaveRequest->isPending()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the leave request.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole(['Developer', 'Executive']);
    }

    /**
     * Determine whether the user can permanently delete the leave request.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole(['Developer', 'Executive']);
    }

    /**
     * Check if Manager N-1 is supervisor of the employee
     */
    private function isManagerN1SupervisorOf(User $managerN1, Employee $employee): bool
    {
        // Check if employee is supervised by a Manager N-2 who is supervised by this Manager N-1
        if ($employee->supervisor && $employee->supervisor->user->hasRole('Manager N-2')) {
            return $employee->supervisor->supervisor &&
                   $employee->supervisor->supervisor->user_id === $managerN1->id;
        }

        // Check if employee is a Manager N-2 supervised by this Manager N-1
        if ($employee->user->hasRole('Manager N-2')) {
            return $employee->supervisor &&
                   $employee->supervisor->user_id === $managerN1->id;
        }

        return false;
    }

    /**
     * Check if Manager N-2 is supervisor of the employee
     */
    private function isManagerN2SupervisorOf(User $managerN2, Employee $employee): bool
    {
        // Manager N-2 can only see requests from employees they directly supervise
        return $employee->supervisor &&
               $employee->supervisor->user_id === $managerN2->id;
    }

    /**
     * Get the query for leave requests the user can view
     */
    public static function scopeViewableBy($query, User $user)
    {
        // Developer and Executive can see all requests
        if ($user->hasRole(['Developer', 'Executive'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // Always include own requests
            $q->whereHas('employee', function ($employeeQuery) use ($user) {
                $employeeQuery->where('user_id', $user->id);
            });

            // Manager N-1 can see requests from employees under their supervision
            if ($user->hasRole('Manager N-1')) {
                $q->orWhereHas('employee', function ($employeeQuery) use ($user) {
                    $employeeQuery->where(function ($subQuery) use ($user) {
                        // Employees supervised by Manager N-2 who is supervised by this Manager N-1
                        $subQuery->whereHas('supervisor', function ($supervisorQuery) use ($user) {
                            $supervisorQuery->whereHas('user', function ($userQuery) use ($user) {
                                $userQuery->where('id', $user->id);
                            })->whereHas('user.roles', function ($roleQuery) {
                                $roleQuery->where('name', 'Manager N-2');
                            });
                        });

                        // OR Manager N-2 supervised by this Manager N-1
                        $subQuery->orWhere(function ($managerQuery) use ($user) {
                            $managerQuery->whereHas('user.roles', function ($roleQuery) {
                                $roleQuery->where('name', 'Manager N-2');
                            })->whereHas('supervisor', function ($supervisorQuery) use ($user) {
                                $supervisorQuery->whereHas('user', function ($userQuery) use ($user) {
                                    $userQuery->where('id', $user->id);
                                });
                            });
                        });
                    });
                });
            }

            // Manager N-2 can see requests from employees they supervise
            elseif ($user->hasRole('Manager N-2')) {
                $q->orWhereHas('employee', function ($employeeQuery) use ($user) {
                    $employeeQuery->whereHas('supervisor', function ($supervisorQuery) use ($user) {
                        $supervisorQuery->where('user_id', $user->id);
                    });
                });
            }
        });
    }
}
