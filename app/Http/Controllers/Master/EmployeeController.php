<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('user.roles');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $employees   = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Employee::count();
        $activeCount = Employee::where('status', 1)->count();
        $roles       = Role::orderBy('name')->get();

        return view('master.employee.index', compact(
            'employees', 'totalCount', 'activeCount', 'roles'
        ));
    }

    /**
     * Tạo nhân viên (chưa có tài khoản)
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'      => 'required|string|max:20|unique:employees,code',
            'full_name' => 'required|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'status'    => 'required|in:0,1',
        ], [
            'code.required'      => 'Vui lòng nhập mã nhân viên.',
            'code.unique'        => 'Mã nhân viên đã tồn tại.',
            'full_name.required' => 'Vui lòng nhập họ tên.',
        ]);

        Employee::create($request->only('code', 'full_name', 'phone', 'email', 'status'));

        return redirect()->route('master.employee.index')
            ->with('success', "Đã thêm nhân viên \"{$request->full_name}\" thành công.");
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'code'      => "required|string|max:20|unique:employees,code,{$employee->id}",
            'full_name' => 'required|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'status'    => 'required|in:0,1',
        ], [
            'code.required'      => 'Vui lòng nhập mã nhân viên.',
            'code.unique'        => 'Mã nhân viên đã tồn tại.',
            'full_name.required' => 'Vui lòng nhập họ tên.',
        ]);

        $employee->update($request->only('code', 'full_name', 'phone', 'email', 'status'));

        return redirect()->route('master.employee.index')
            ->with('success', "Đã cập nhật nhân viên \"{$employee->full_name}\" thành công.");
    }

    public function destroy(Employee $employee)
    {
        if ($employee->hasAccount()) {
            return redirect()->route('master.employee.index')
                ->with('error', "Không thể xóa \"{$employee->full_name}\" vì đang có tài khoản đăng nhập. Hãy xóa tài khoản trước.");
        }

        $name = $employee->full_name;
        $employee->delete();

        return redirect()->route('master.employee.index')
            ->with('success', "Đã xóa nhân viên \"{$name}\" thành công.");
    }

    /**
     * Tạo tài khoản đăng nhập cho nhân viên
     */
    public function createAccount(Request $request, Employee $employee)
    {
        if ($employee->hasAccount()) {
            return redirect()->route('master.employee.index')
                ->with('error', 'Nhân viên này đã có tài khoản.');
        }

        $request->validate([
            'login_email' => 'required|email|unique:users,email',
            'login_name'  => 'required|string|max:100',
            'password'    => ['required', 'confirmed', Password::min(8)],
            'role'        => 'required|exists:roles,name',
        ], [
            'login_email.required' => 'Vui lòng nhập email đăng nhập.',
            'login_email.unique'   => 'Email này đã được dùng bởi tài khoản khác.',
            'password.required'    => 'Vui lòng nhập mật khẩu.',
            'password.confirmed'   => 'Xác nhận mật khẩu không khớp.',
            'password.min'         => 'Mật khẩu tối thiểu 8 ký tự.',
            'role.required'        => 'Vui lòng chọn vai trò.',
        ]);

        DB::transaction(function () use ($request, $employee) {
            $user = User::create([
                'name'     => $request->login_name,
                'email'    => $request->login_email,
                'password' => Hash::make($request->password),
            ]);
            $user->assignRole($request->role);
            $employee->update(['user_id' => $user->id]);
        });

        return redirect()->route('master.employee.index')
            ->with('success', "Đã tạo tài khoản cho nhân viên \"{$employee->full_name}\" thành công.");
    }

    /**
     * Đổi role hoặc reset mật khẩu tài khoản
     */
    public function updateAccount(Request $request, Employee $employee)
    {
        if (!$employee->hasAccount()) {
            return redirect()->route('master.employee.index')
                ->with('error', 'Nhân viên này chưa có tài khoản.');
        }

        $request->validate([
            'role'             => 'required|exists:roles,name',
            'new_password'     => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'role.required'        => 'Vui lòng chọn vai trò.',
            'new_password.min'     => 'Mật khẩu tối thiểu 8 ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        DB::transaction(function () use ($request, $employee) {
            $user = $employee->user;
            $user->syncRoles([$request->role]);

            if ($request->filled('new_password')) {
                $user->update(['password' => Hash::make($request->new_password)]);
            }
        });

        return redirect()->route('master.employee.index')
            ->with('success', "Đã cập nhật tài khoản của \"{$employee->full_name}\" thành công.");
    }

    /**
     * Xóa tài khoản đăng nhập (giữ lại hồ sơ nhân viên)
     */
    public function deleteAccount(Employee $employee)
    {
        if (!$employee->hasAccount()) {
            return redirect()->route('master.employee.index')
                ->with('error', 'Nhân viên này chưa có tài khoản.');
        }

        DB::transaction(function () use ($employee) {
            $user = $employee->user;
            $employee->update(['user_id' => null]);
            $user->delete();
        });

        return redirect()->route('master.employee.index')
            ->with('success', "Đã xóa tài khoản của \"{$employee->full_name}\". Hồ sơ nhân viên vẫn được giữ lại.");
    }
}