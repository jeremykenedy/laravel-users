<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Actions\BulkUsers;
use jeremykenedy\laravelusers\Actions\CreateUser;
use jeremykenedy\laravelusers\App\Http\Requests\BulkUsersRequest;
use jeremykenedy\laravelusers\App\Http\Requests\CreateUserRequest;
use jeremykenedy\laravelusers\Rules\PlainTextName;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\DeletedUsers;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\UserActivity;

class UsersManagementController extends Controller
{
    private bool $_authEnabled;

    private bool $_rolesEnabled;

    private string $_rolesMiddlware;

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

        if ($this->_rolesEnabled && $this->_rolesMiddleWareEnabled) {
            $this->middleware($this->_rolesMiddlware);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $pagintaionEnabled = config('laravelusers.enablePagination', true);
        $userModel = config('laravelusers.defaultUserModel');

        if ($pagintaionEnabled) {
            $users = $userModel::paginate(config('laravelusers.paginateListSize', 25));
        } else {
            $users = $userModel::all();
        }

        $data = [
            'users'             => $users,
            'pagintaionEnabled' => $pagintaionEnabled,
        ];

        return view(Frontend::view(config('laravelusers.showUsersBlade')), $data);
    }

    public function bulk(BulkUsersRequest $request, BulkUsers $bulk): RedirectResponse
    {
        $data = $request->validated();
        $bulk->handle($data);

        return redirect()->route($data['action'] === 'delete' ? 'users' : 'users.deleted')->with('success', trans('laravelusers::ui.bulk_success'));
    }

    public function deleted(DeletedUsers $deleted): View
    {
        $query = $deleted->query();
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
        $roles = [];

        if ($this->_rolesEnabled) {
            $roleModel = config('laravelusers.roleModel');
            $roles = $roleModel::all();
        }

        $data = [
            'rolesEnabled' => $this->_rolesEnabled,
            'roles'        => $roles,
        ];

        return view(Frontend::view(config('laravelusers.createUserBlade')), $data);
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

        return view(Frontend::view(config('laravelusers.showIndividualUserBlade')), ['user' => $user]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $userModel = config('laravelusers.defaultUserModel');
        $user = $userModel::findOrFail($id);
        $roles = [];
        $currentRole = [];

        if ($this->_rolesEnabled) {
            $roleModel = config('laravelusers.roleModel');
            $roles = $roleModel::all();

            foreach ($user->roles as $user_role) {
                $currentRole[] = $user_role->id;
            }
        }

        $data = [
            'user'         => $user,
            'rolesEnabled' => $this->_rolesEnabled,
        ];

        if ($this->_rolesEnabled) {
            $data['roles'] = $roles;
            $data['currentRole'] = $currentRole;
        }

        return view(Frontend::view(config('laravelusers.editIndividualUserBlade')), $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $userModel = config('laravelusers.defaultUserModel');
        $user = $userModel::findOrFail($id);
        $emailCheck = ($request->input('email') !== '') && ($request->input('email') !== $user->email);
        $passwordCheck = $request->filled('password');

        $rules = [
            'name' => ['required', 'string', 'max:255', new PlainTextName()],
        ];

        if ($emailCheck) {
            $table = ($user->getConnectionName() ? $user->getConnectionName().'.' : '').$user->getTable();
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique($table)];
        }

        if ($passwordCheck) {
            $rules['password'] = 'required|string|min:6|max:20|confirmed';
            $rules['password_confirmation'] = 'required|string|same:password';
        }

        if ($this->_rolesEnabled) {
            $rules['role'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except(['password', 'password_confirmation']));
        }

        $user->getConnection()->transaction(function () use ($user, $request, $emailCheck, $passwordCheck) {
            $user->name = strip_tags($request->input('name'));

            if ($emailCheck) {
                $user->email = $request->input('email');
            }

            if ($passwordCheck) {
                $user->password = Hash::make($request->input('password'));
            }

            if ($this->_rolesEnabled) {
                $user->detachAllRoles();
                $user->attachRole($request->input('role'));
            }

            $user->save();
        });

        return back()->with('success', trans('laravelusers::laravelusers.messages.update-user-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $currentUser = Auth::user();
        $userModel = config('laravelusers.defaultUserModel');
        $user = $userModel::findOrFail($id);

        if (!$currentUser || (string) $currentUser->getAuthIdentifier() !== (string) $user->getKey()) {
            $user->delete();

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
        $results = $userModel::where('id', 'like', $searchTerm.'%')
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
                $data['activity'] = $activity->listing($results);
            }
            if ($request->boolean('include_avatar')) {
                $data['avatars'] = $avatar->listing($results);
            }
        }

        return response()->json($data, 200);
    }
}
