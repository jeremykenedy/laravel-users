<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use jeremykenedy\laravelusers\Actions\AvatarPreview;
use jeremykenedy\laravelusers\Actions\BulkUsers;
use jeremykenedy\laravelusers\Actions\CreateUser;
use jeremykenedy\laravelusers\Actions\EmailUsers;
use jeremykenedy\laravelusers\Actions\PreviewUserEmail;
use jeremykenedy\laravelusers\Actions\SendGoodbye;
use jeremykenedy\laravelusers\Actions\UpdateUser;
use jeremykenedy\laravelusers\Actions\UpdateUserSettings;
use jeremykenedy\laravelusers\App\Http\Middleware\PrepareNativeSearch;
use jeremykenedy\laravelusers\App\Http\Middleware\UserAccessMiddleware;
use jeremykenedy\laravelusers\App\Http\Requests\BulkUsersRequest;
use jeremykenedy\laravelusers\App\Http\Requests\CreateUserRequest;
use jeremykenedy\laravelusers\App\Http\Requests\DeleteUserRequest;
use jeremykenedy\laravelusers\App\Http\Requests\EmailUsersRequest;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateSettingsRequest;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateUserRequest;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\DeletedUsers;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageOperations;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\RoleAccess;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Support\UserPermissions;
use jeremykenedy\laravelusers\Support\UserRoles;
use jeremykenedy\laravelusers\Support\UserSettings;
use RuntimeException;

class UsersManagementController extends Controller
{
    private bool $_authEnabled;

    private bool $_rolesEnabled;

    private string|array $_rolesMiddlware;

    private bool $_rolesMiddleWareEnabled;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_authEnabled = config('laravelusers.authEnabled', true);
        $this->_rolesEnabled = config('laravelusers.rolesEnabled', false);
        $this->_rolesMiddlware = config('laravelusers.rolesMiddlware', 'role:admin');
        $this->_rolesMiddleWareEnabled = config('laravelusers.rolesMiddlwareEnabled', true);

        if ($this->_authEnabled) {
            $this->middleware('auth');
        }

        $middleware = config('laravelusers.middleware', []);
        if ($middleware) {
            $this->middleware($middleware);
        }

        if ($this->_rolesEnabled && $this->_rolesMiddleWareEnabled) {
            $this->middleware($this->_rolesMiddlware);
        }
        $this->middleware(UserAccessMiddleware::class);
        $this->middleware(PrepareNativeSearch::class)->only('index');
    }

    public function settings(UserSettings $settings, ManagedPackages $packages, PackageRequirements $requirements, AvatarPreview $preview): View
    {
        abort_unless(config('laravelusers.settings.enabled', false), 404);
        $user = Auth::user();
        $accessAvailable = $user instanceof Model && RoleAccess::available($user);
        $roles = $accessAvailable ? RoleAccess::query($user, 'role')->get() : collect();
        $permissions = $accessAvailable ? RoleAccess::query($user, 'permission')->get() : collect();
        $packageManagementAllowed = $user instanceof Model && $packages->allowed($user);
        $packageRequirements = $packageManagementAllowed ? $requirements->status($packages) : null;

        return view(Frontend::framework() === 'bootstrap4' ? 'laravelusers::usersmanagement.settings' : 'laravelusers::modern.settings', ['settingsAvailable' => $settings->available(), 'accessAvailable' => $accessAvailable, 'levelsAvailable' => $accessAvailable && method_exists($user, 'level'), 'roles' => $roles, 'permissions' => $permissions, 'packageManagementAllowed' => $packageManagementAllowed, 'managedPackages' => $packages->listing(), 'managedPackageSetup' => ['toast' => $packages->toastSetupComplete()], 'packageQueueReady' => $packageRequirements['queue_ready'] ?? false, 'packageRequirements' => $packageRequirements, 'packageOperation' => $packageManagementAllowed ? PackageOperations::latest($user) : null, 'appearancePreviewAvatars' => UserAccess::allows('edit_appearance') ? $preview->handle(config('laravelusers.avatar.source', 'initials')) : [], 'impersonationEnabled' => config('laravelusers.impersonation.enabled', false)]);
    }

    public function updateSettings(UpdateSettingsRequest $request, UpdateUserSettings $update): RedirectResponse
    {
        $update->handle($request->validated());

        return back()->with('success', trans('laravelusers::ui.settings_saved'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $pagintaionEnabled = config('laravelusers.enablePagination', true);
        $userModel = config('laravelusers.defaultUserModel');

        if ($pagintaionEnabled) {
            $users = $userModel::when($this->_rolesEnabled, fn ($query) => $query->with('roles'))->paginate(config('laravelusers.paginateListSize', 25));
        } else {
            $users = $userModel::when($this->_rolesEnabled, fn ($query) => $query->with('roles'))->get();
        }

        $data = [
            'users'               => $users,
            'pagintaionEnabled'   => $pagintaionEnabled,
            'canImpersonateUsers' => UserAccess::canImpersonate(),
        ];

        return view(Frontend::view(config('laravelusers.showUsersBlade')), $data);
    }

    public function bulk(BulkUsersRequest $request, BulkUsers $bulk): RedirectResponse
    {
        $data = $request->validated();
        $bulk->handle($data);

        return redirect()->route($data['action'] === 'delete' ? 'users' : 'users.deleted')->with('success', trans('laravelusers::ui.bulk_success'));
    }

    public function email(EmailUsersRequest $request, EmailUsers $emails): RedirectResponse
    {
        $sent = $emails->handle($request->validated());

        return back()->with('success', trans_choice('laravelusers::ui.email_sent', $sent, ['count' => $sent]));
    }

    public function previewEmail(EmailUsersRequest $request, PreviewUserEmail $preview): JsonResponse
    {
        return response()->json($preview->handle($request->validated()))->header('Cache-Control', 'no-store, private');
    }

    public function deleted(DeletedUsers $deleted): View
    {
        $query = $deleted->query();
        $query->when($this->_rolesEnabled, fn ($query) => $query->with('roles'));
        $query->orderBy('deleted_at', 'desc')->orderBy($query->getModel()->getKeyName(), 'desc');
        $pagintaionEnabled = config('laravelusers.enablePagination', true);
        $users = $pagintaionEnabled ? $query->paginate(config('laravelusers.paginateListSize', 25)) : $query->get();

        return view(Frontend::view(config('laravelusers.showDeletedUsersBlade', 'laravelusers::usersmanagement.deleted-users')), compact('users', 'pagintaionEnabled'));
    }

    public function restore(int $id, DeletedUsers $deleted): RedirectResponse
    {
        $deleted->query()->findOrFail($id)->restore();

        return redirect()->route('users.deleted')->with('success', trans('laravelusers::ui.restored'));
    }

    public function forceDestroy(int $id, DeletedUsers $deleted): RedirectResponse
    {
        $user = $deleted->query()->findOrFail($id);
        if (Auth::check() && (string) Auth::id() === (string) $user->getKey()) {
            return back()->with('error', trans('laravelusers::laravelusers.messages.cannot-delete-yourself'));
        }
        $user->forceDelete();

        return redirect()->route('users.deleted')->with('success', trans('laravelusers::ui.permanently_deleted'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $userModel = config('laravelusers.defaultUserModel');
        $model = new $userModel();
        $roles = [];

        if ($this->_rolesEnabled) {
            $userModel = config('laravelusers.defaultUserModel');
            $roles = UserRoles::query(new $userModel())->get();
        }

        $data = [
            'rolesEnabled' => $this->_rolesEnabled,
            'roles'        => $roles,
        ];

        return view(Frontend::view(config('laravelusers.createUserBlade')), array_merge($data, UserPermissions::formData($model), AvatarPreferences::formData($model), AppearancePreferences::formData($model), AccountPreferences::formData($model)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request, CreateUser $create): RedirectResponse
    {
        $sent = $create->handle($request->validated());
        $response = redirect('users')->with('success', trans('laravelusers::laravelusers.messages.user-creation-success'));

        return $sent ? $response : $response->with('error', trans('laravelusers::ui.welcome_failed'));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): View
    {
        $userModel = config('laravelusers.defaultUserModel');
        $user = $userModel::findOrFail($id);

        return view(Frontend::view(config('laravelusers.showIndividualUserBlade')), array_merge(['user' => $user, 'canImpersonateUsers' => UserAccess::canImpersonate($user)], UserRoles::viewData($user), UserPermissions::displayData($user)));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $userModel = config('laravelusers.defaultUserModel');
        $user = $userModel::findOrFail($id);

        return $this->editForm($user);
    }

    public function editDeleted(int $id, DeletedUsers $deleted): View
    {
        abort_unless(config('laravelusers.settings.enabled', false), 404);

        return $this->editForm($deleted->query()->findOrFail($id), true);
    }

    private function editForm(Model $user, bool $deletedUser = false): View
    {
        $roles = [];
        $currentRole = [];

        if ($this->_rolesEnabled) {
            $roles = UserRoles::query($user)->get();

            foreach ($user->roles as $user_role) {
                $currentRole[] = $user_role->id;
            }
        }

        $data = [
            'user'         => $user,
            'deletedUser'  => $deletedUser,
            'rolesEnabled' => $this->_rolesEnabled,
        ];

        if ($this->_rolesEnabled) {
            $data['roles'] = $roles;
            $data['currentRole'] = $currentRole;
        }

        return view(Frontend::view(config('laravelusers.editIndividualUserBlade')), array_merge($data, UserRoles::viewData($user), UserPermissions::formData($user), AvatarPreferences::formData($user), AppearancePreferences::formData($user), AccountPreferences::formData($user)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, int $id, UpdateUser $update): RedirectResponse
    {
        $update->handle($request->target($id), $request->validated());

        return back()->with('success', trans('laravelusers::laravelusers.messages.update-user-success'));
    }

    public function updateDeleted(UpdateUserRequest $request, int $id, UpdateUser $update): RedirectResponse
    {
        abort_unless(config('laravelusers.settings.enabled', false), 404);

        return $this->update($request, $id, $update);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        return $this->deleteUser($id);
    }

    public function destroyWithEmail(int $id, DeleteUserRequest $request, SendGoodbye $goodbye): RedirectResponse
    {
        return $this->deleteUser($id, $request->validated(), $goodbye);
    }

    private function deleteUser(int $id, array $data = [], ?SendGoodbye $goodbye = null): RedirectResponse
    {
        $currentUser = Auth::user();
        $userModel = config('laravelusers.defaultUserModel');
        $user = $userModel::findOrFail($id);

        if (!$currentUser || (string) $currentUser->getAuthIdentifier() !== (string) $user->getKey()) {
            $user->getConnection()->transaction(function () use ($user, $data, $goodbye) {
                if (!$user->delete()) {
                    throw new RuntimeException('User deletion was rejected.');
                }
                if ($goodbye) {
                    $goodbye->handle($user, $data);
                }
            });

            return redirect('users')->with('success', trans('laravelusers::laravelusers.messages.delete-success'));
        }

        return back()->with('error', trans('laravelusers::laravelusers.messages.cannot-delete-yourself'));
    }

    /**
     * Method to search the users.
     */
    public function search(Request $request, UserActivity $activity, Avatar $avatar): JsonResponse
    {
        $searchTerm = $request->input('user_search_box');
        $searchRules = [
            'user_search_box' => 'required|string|max:255',
        ];
        $searchMessages = [
            'user_search_box.required' => 'Search term is required',
            'user_search_box.string'   => 'Search term has invalid characters',
            'user_search_box.max'      => 'Search term has too many characters - 255 allowed',
        ];

        $validator = Validator::make($request->all(), $searchRules, $searchMessages);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $userModel = config('laravelusers.defaultUserModel');
        $results = $userModel::when($this->_rolesEnabled, fn ($query) => $query->with('roles'))->where('id', 'like', $searchTerm.'%')
            ->orWhere('name', 'like', $searchTerm.'%')
            ->orWhere('email', 'like', $searchTerm.'%')
            ->get();

        if ($this->_rolesEnabled) {
            $results->each(function ($result) {
                $result->setAttribute('roles', $result->roles);
            });
        }

        $data = $results->toArray();
        if ($request->boolean('include_activity') || $request->boolean('include_avatar')) {
            $data = ['users' => $data];
            if ($request->boolean('include_activity')) {
                $data['activity'] = $activity->listing($results, $request->boolean('include_login_details') && (bool) config('laravelusers.showLastLoginDetailsColumn', false));
            }
            if ($request->boolean('include_avatar')) {
                $data['avatars'] = $avatar->listing($results);
                if (config('laravelusers.appearance.per_user', false)) {
                    $data['appearance'] = AppearancePreferences::colors($results);
                }
            }
        }

        return response()->json($data, 200);
    }
}
