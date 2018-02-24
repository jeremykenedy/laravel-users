<?php

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Validator;

class UsersManagementController extends Controller
{
    private $_authEnabled;
    private $_rolesEnabled;
    private $_rolesMiddlware;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_authEnabled = config('laravelusers.authEnabled');
        $this->_rolesEnabled = config('laravelusers.rolesEnabled');
        $this->_rolesMiddlware = config('laravelusers.rolesMiddlware');

        if ($this->_authEnabled) {
            $this->middleware('auth');
        }

        if ($this->_rolesEnabled) {
            $this->middleware($this->_rolesMiddlware);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pagintaionEnabled = config('laravelusers.enablePagination');

        if ($pagintaionEnabled) {
            $users = config('laravelusers.defaultUserModel')::paginate(config('laravelusers.paginateListSize'));
        } else {
            $users = config('laravelusers.defaultUserModel')::all();
        }

        $data = [
            'users'             => $users,
            'pagintaionEnabled' => $pagintaionEnabled,
        ];

        return view('laravelusers::usersmanagement.show-users', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = [];

        if ($this->_rolesEnabled) {
            $roles = config('laravelusers.roleModel')::all();
        }

        $data = [
            'rolesEnabled'  => $this->_rolesEnabled,
            'roles'         => $roles,
        ];

        return view('laravelusers::usersmanagement.create-user')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name'                  => 'required|string|max:255|unique:users',
            'email'                 => 'required|email|max:255|unique:users',
            'password'              => 'required|string|confirmed|min:6',
            'password_confirmation' => 'required|string|same:password',
        ];

        if ($this->_rolesEnabled) {
            $rules['role'] = 'required';
        }

        $messages = [
            'name.unique'         => trans('laravelusers::laravelusers.messages.userNameTaken'),
            'name.required'       => trans('laravelusers::laravelusers.messages.userNameRequired'),
            'email.required'      => trans('laravelusers::laravelusers.messages.emailRequired'),
            'email.email'         => trans('laravelusers::laravelusers.messages.emailInvalid'),
            'password.required'   => trans('laravelusers::laravelusers.messages.passwordRequired'),
            'password.min'        => trans('laravelusers::laravelusers.messages.PasswordMin'),
            'password.max'        => trans('laravelusers::laravelusers.messages.PasswordMax'),
            'role.required'       => trans('laravelusers::laravelusers.messages.roleRequired'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = config('laravelusers.defaultUserModel')::create([
            'name'             => $request->input('name'),
            'email'            => $request->input('email'),
            'password'         => bcrypt($request->input('password')),
        ]);

        if ($this->_rolesEnabled) {
            $user->attachRole($request->input('role'));
            $user->save();
        }

        return redirect('users')->with('success', trans('laravelusers::laravelusers.messages.user-creation-success'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = config('laravelusers.defaultUserModel')::find($id);

        return view('laravelusers::usersmanagement.show-user')->withUser($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = config('laravelusers.defaultUserModel')::findOrFail($id);
        $roles = [];
        $currentRole = '';

        if ($this->_rolesEnabled) {
            $roles = config('laravelusers.roleModel')::all();

            foreach ($user->roles as $user_role) {
                $currentRole = $user_role;
            }
        }

        $data = [
            'user'          => $user,
            'rolesEnabled'  => $this->_rolesEnabled,
        ];

        if ($this->_rolesEnabled) {
            $data['roles'] = $roles;
            $data['currentRole'] = $currentRole;
        }

        return view('laravelusers::usersmanagement.edit-user')->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = config('laravelusers.defaultUserModel')::find($id);
        $emailCheck = ($request->input('email') != '') && ($request->input('email') != $user->email);
        $passwordCheck = $request->input('password') != null;

        $rules = [
            'name' => 'required|max:255',
        ];

        if ($emailCheck) {
            $rules['email'] = 'required|email|max:255|unique:users';
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
            return back()->withErrors($validator)->withInput();
        }

        $user->name = $request->input('name');

        if ($emailCheck) {
            $user->email = $request->input('email');
        }

        if ($passwordCheck) {
            $user->password = bcrypt($request->input('password'));
        }

        if ($this->_rolesEnabled) {
            $user->detachAllRoles();
            $user->attachRole($request->input('role'));
        }

        $user->save();

        return back()->with('success', trans('laravelusers::laravelusers.messages.update-user-success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        $user = config('laravelusers.defaultUserModel')::findOrFail($id);

        if ($currentUser != $user) {
            $user->delete();

            return redirect('users')->with('success', trans('laravelusers::laravelusers.messages.delete-success'));
        }

        return back()->with('error', trans('laravelusers::laravelusers.messages.cannot-delete-yourself'));
    }
}
