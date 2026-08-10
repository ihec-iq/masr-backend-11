<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateMyPasswordRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Resources\User\UserLiteResource;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserResourceCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $data = UserResource::collection(User::orderByDesc('updated_at')->get());

        return $this->ok($data);
    }
    public function getLite()
    {
        $data = UserLiteResource::collection(User::get());

        return $this->ok($data);
    }
    public function filter(Request $request)
    {
        // بدون سقف: limit سالب يُلغي الترقيم كليًا ويعيد كل الجدول في استجابة واحدة
        $request->validate(['limit' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $limit = $request->integer('limit', 10) ?: 10;

        $query = User::query();

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('name')) {
            $name = $request->name;
            $query->where(function ($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%')
                  ->orWhere('email', 'like', '%' . $name . '%');
            });
        }
        if ($request->filled('sectionId')) {
            $query->where('section_id', $request->sectionId);
        }

        $data = $query->orderBy('updated_at', 'desc')->paginate($limit);
        return $this->ok(new UserResourceCollection($data));
    }

    public function store(UserStoreRequest $request)
    {
        try {
            $creatorId = Auth::id() ?? 1;
            $validate = $request->validated();
            $userData = [
                'name'        => $validate['name'],
                'user_name'   => $validate['user_name'],
                'email'       => $validate['email'],
                'password'    => Hash::make($validate['password']),
                // الحقول الاختيارية تُقرأ من الطلب لأن validated() لا تُرجع المفاتيح الغائبة
                'any_device'  => $request->boolean('any_device'),
                // الافتراضي مفعّل: login يرفض active = 0 فإغفال الحقل كان ينشئ حسابًا معطّلًا صامتًا
                'active'      => $request->boolean('active', true),
                'window_id'   => $request->integer('window_id', 1),
                'user_id'     => $creatorId,
            ];

            $user = User::create($userData);

            $accessToken = $user->createToken($user->email)->plainTextToken;

            if (!empty($request->roles)) {
                $roles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
                $user->syncRoles($roles);
            }

            $user->refresh();

            return $this->ok([
                'user'  => new UserResource($user),
                'token' => $accessToken,
            ]);
        } catch (\Throwable $e) {
            Log::error('User Store Error: ' . $e->getMessage());

            return $this->error(__('general.saveUnsuccessfully'));
        }
    }

    public function show(string $id)
    {
        $data = User::find($id);

        return $this->ok(new UserResource($data));
    }

    public function update(Request $request, $user_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return $this->error(__('general.saveUnsuccessfully'));
        }

        $request->validate([
            'name'      => ['required', 'string', 'min:2', 'max:255'],
            // user_name صار معرّف دخول في AuthController::login فلا بد أن يكون فريدًا
            'user_name' => ['required', 'string', 'min:2', 'max:255', 'unique:users,user_name,' . $user->id],
            'email'     => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            // نفس سياسة الإنشاء: لا يجوز أن يتجاوز مسار التعديل قواعد كلمة المرور
            'password'  => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'window_id' => ['nullable', 'integer'],
            'roles'     => ['nullable', 'array'],
            'roles.*'   => ['integer', 'exists:roles,id'],
        ]);

        $user->name = $request->name;
        $user->user_name = $request->user_name;
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        // نحافظ على القيمة الحالية عند غياب المفتاح حتى لا يُعطَّل الحساب بتعديل جزئي
        $user->any_device = $request->boolean('any_device', $user->any_device);
        $user->active = $request->boolean('active', $user->active);
        // filled() وليس input(): window_id عمود NOT NULL، وإرسال null صراحةً كان يكسر الحفظ
        if ($request->filled('window_id')) {
            $user->window_id = $request->integer('window_id');
        }

        $user->save();

        // has() وليس empty() حتى يتمكن العميل من مسح كل الأدوار بإرسال roles: []
        if ($request->has('roles')) {
            $roles = Role::whereIn('id', (array) $request->input('roles', []))->pluck('name')->toArray();
            $user->syncRoles($roles);
        }

        $payload = ['user' => new UserResource($user)];

        // نُصدر توكن فقط عندما يعدّل المستخدم بياناته بنفسه (توافق مع العميل القديم)،
        // فإصدار توكن لمستخدم آخر يعني تسليم المدير جلسةً تنتحل شخصيته.
        if (Auth::id() === $user->id) {
            $payload['token'] = $user->createToken($user->email ?: $user->user_name)->plainTextToken;
        }

        return $this->ok($payload, __('general.saveSuccessfully'));
    }

    public function updateMyPassword(UpdateMyPasswordRequest $request)
    {
        $user = Auth::user();
        if (!$user || !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password does not match.'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // إبطال كل التوكنات السابقة بعد تغيير كلمة المرور (تطرد أي جلسة مسروقة)
        // ثم إصدار توكن جديد ليواصل العميل الحالي عمله كما كان سابقًا.
        $user->tokens()->delete();
        $token = $user->createToken($user->email ?: $user->user_name)->plainTextToken;

        return $this->ok([
            'user'  => new UserResource($user),
            'token' => $token,
        ], __('general.saveSuccessfully'));
    }

    public function active($id)
    {
        $user = User::find($id);
        if (!isset($user) || $user == null || $user == '') {
            return $this->error(__('general.saveUnsuccessfully'));
        }
        $user->active = true;
        $user->save();

        return $this->ok(new UserResource($user), __('general.saveSuccessfully'));
    }

    public function disActive($id)
    {
        $user = User::find($id);
        if (!isset($user) || $user == null || $user == '') {
            return $this->error(__('general.saveUnsuccessfully'));
        }
        $user->active = false;
        $user->save();

        return $this->ok(new UserResource($user), __('general.saveSuccessfully'));
    }

    public function destroy(string $id)
    {
        $data = User::find($id);
        $data->delete();

        return $this->ok(null);
    }
}
