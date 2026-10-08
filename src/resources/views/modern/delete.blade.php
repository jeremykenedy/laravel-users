@if(\jeremykenedy\laravelusers\Support\UserAccess::allows('delete_users') && (!Auth::check() || (string) Auth::id() !== (string) $user->id))
    <form method="POST" action="{{ route('user.destroy', $user->id) }}" data-lu-delete-action @if(config('laravelusers.confirmDelete', true)) data-lu-confirm="{{ __('laravelusers::ui.confirm_delete', ['name' => $user->name]) }}" @endif>
        @csrf
        @method('DELETE')
        <button class="lu-button lu-danger" type="submit">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.delete') }}</button>
    </form>
@endif
